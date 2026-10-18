/**
 * Pruebas de carga y rendimiento — Iteración 2: Mapa y Rastreo en Tiempo Real
 * Proyecto: Ubicaditos (Laravel 12)
 *
 * ALCANCE: únicamente endpoints de LECTURA + login real (POST /login), que es
 * imprescindible para autenticar las sesiones de los usuarios virtuales.
 *
 * EXCLUIDO A PROPOSITO (nunca se envía): driver/update-location, start-trip,
 * end-trip, update-passengers, emergency, favorites, rate, y cualquier
 * endpoint de administración. No ejecutar `buses:simulate` durante la prueba.
 *
 * AUTENTICACIÓN: cada VU realiza su propio login (sesión por usuario virtual)
 * y propaga la cookie de sesión de forma EXPLÍCITA en cada petición. Motivo:
 * la cookie jar automática de k6 (v2.2.0) pierde la sesión cuando corren VUs
 * concurrentes (verificado: mismas peticiones con Cookie manual → 200/JSON,
 * con jar automática → 302 a /login). El servidor conserva las sesiones
 * correctamente en ambos casos (comprobado en la tabla sessions).
 *
 * Uso (PowerShell, desde la raíz del proyecto):
 *   k6 run tests/load/ubicaditos-load.js -e K6_MODE=api -e K6_VUS=10 -e K6_DURATION=30s
 *   k6 run tests/load/ubicaditos-load.js -e K6_MODE=web -e K6_VUS=25 -e K6_DURATION=30s
 *
 * Variables:
 *   K6_MODE      api | web           (mezcla de endpoints a probar)
 *   K6_VUS       usuarios virtuales  (10, 25, 50)
 *   K6_DURATION  duración            (por defecto 30s)
 *   K6_BASE_URL  base                (por defecto http://127.0.0.1:8000)
 *   K6_EMAIL / K6_PASSWORD  credenciales del entorno local de desarrollo
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ---------------------------------------------------------------------------
// Configuración (todo por variables de entorno; sin valores incrustados)
// ---------------------------------------------------------------------------
const BASE = __ENV.K6_BASE_URL || 'http://127.0.0.1:8000';
const MODE = __ENV.K6_MODE === 'web' ? 'web' : 'api';
const VUS = parseInt(__ENV.K6_VUS || '10', 10);
const DURATION = __ENV.K6_DURATION || '30s';
const EMAIL = __ENV.K6_EMAIL || 'pasajero1@ubicaditos.com';
const PASSWORD = __ENV.K6_PASSWORD || 'password';

// ---------------------------------------------------------------------------
// Métricas propias
// ---------------------------------------------------------------------------
const logins = new Counter('custom_logins');              // sesiones creadas (una por VU)
const authFailures = new Rate('custom_auth_failures');     // respuestas no autenticadas
const readTrend = new Trend('custom_read_duration', true); // duración de los GET medidos

export const options = {
  scenarios: {
    load: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  tags: {
    mode: MODE,
  },
  thresholds: {
    // Objetivos iniciales de la iteración (se reportan con PASS/FAIL real)
    'custom_read_duration': ['p(95)<800'],
    'http_req_failed{kind:read}': ['rate<0.01'],
    'checks': ['rate>0.99'],
  },
};

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------
function extractToken(html) {
  const m = html.match(/name="_token"\s+value="([^"]+)"/);
  return m ? m[1] : '';
}

/** Extrae la cookie de sesión del header Set-Cookie de una respuesta. */
function sessionCookie(res) {
  const sc = res.headers['Set-Cookie'] || res.headers['set-cookie'] || '';
  const m = sc.match(/laravel-session=([^;,]+)/);
  return m ? m[1] : '';
}

/**
 * Login real de Laravel (Breeze) con CSRF de formulario y cookies explícitas.
 * Devuelve la cookie de sesión autenticada o cadena vacía si falla.
 * El POST debe viajar con la cookie de sesión creada por el GET /login
 * (Laravel valida el token CSRF contra esa sesión).
 */
function doLogin() {
  let sess = '';

  group('autenticacion', () => {
    const page = http.get(`${BASE}/login`, { tags: { kind: 'auth', name: 'login_form' } });
    check(page, { 'login: formulario disponible (200)': (r) => r.status === 200 });
    const guestCookie = sessionCookie(page);

    const body = [
      `_token=${encodeURIComponent(extractToken(page.body))}`,
      `email=${encodeURIComponent(EMAIL)}`,
      `password=${encodeURIComponent(PASSWORD)}`,
      'remember=0',
    ].join('&');

    const res = http.post(`${BASE}/login`, body, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cookie': 'laravel-session=' + guestCookie,
      },
      redirects: 0,
      tags: { kind: 'auth', name: 'login_submit' },
    });

    const authed = check(res, {
      'login: autenticación aceptada (302)': (r) => r.status === 302,
      'login: redirige tras iniciar sesión': (r) => (r.headers['Location'] || '').indexOf('/login') === -1,
    });

    logins.add(1);
    authFailures.add(authed ? 0 : 1);
    sess = sessionCookie(res);
    if (!authed) sess = '';
  });

  return sess;
}

/** GET medido: status + comprobación funcional del contenido. */
function readCheck(url, name, expectFn, sess) {
  const res = http.get(url, {
    redirects: 0,
    headers: { 'Cookie': 'laravel-session=' + sess },
    tags: { kind: 'read', name: name },
  });
  readTrend.add(res.timings.duration);

  const isAuthed = res.status === 200;
  authFailures.add(isAuthed ? 0 : 1);

  const passed = check(res, {
    [`${name}: HTTP 200`]: (r) => r.status === 200,
    [`${name}: contenido esperado`]: (r) => expectFn(r),
  }, { endpoint: name });

  return passed;
}

const isJsonArray = (r) => (r.body || '').trimStart().startsWith('[');
const isJsonObj = (r) => (r.body || '').trimStart().startsWith('{');
const isHtmlMap = (r) => /leaflet/i.test(r.body || '');
const isHtmlList = (r) => /<html/i.test(r.body || '');

// Estado por VU (cada VU de k6 tiene su propia copia del init context)
let session = '';

// ---------------------------------------------------------------------------
// setup(): comprobación pequeña de conectividad/autenticación + descubrimiento
// de IDs reales (nunca se inventan identificadores).
// ---------------------------------------------------------------------------
export function setup() {
  const page = http.get(`${BASE}/login`);
  if (page.status !== 200) {
    throw new Error(`No se pudo obtener /login (status ${page.status}). ¿Está el servidor activo en ${BASE}?`);
  }
  const guestCookie = sessionCookie(page);
  const body = [
    `_token=${encodeURIComponent(extractToken(page.body))}`,
    `email=${encodeURIComponent(EMAIL)}`,
    `password=${encodeURIComponent(PASSWORD)}`,
    'remember=0',
  ].join('&');
  const post = http.post(`${BASE}/login`, body, {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Cookie': 'laravel-session=' + guestCookie,
    },
    redirects: 0,
  });
  if (post.status !== 302) {
    throw new Error(`Login rechazado (status ${post.status}). Verifica credenciales/autenticación.`);
  }
  const sess = sessionCookie(post);

  // IDs reales: bus activo con ubicación desde el endpoint JSON y
  // route_id desde la vista real de rutas del pasajero.
  let busId = null;
  let routeId = null;

  const active = http.get(`${BASE}/api/buses/active`, {
    redirects: 0,
    headers: { 'Cookie': 'laravel-session=' + sess },
  });
  if (active.status === 200) {
    try {
      const arr = JSON.parse(active.body);
      if (Array.isArray(arr) && arr.length > 0 && arr[0].id != null) busId = arr[0].id;
    } catch (e) { /* se reporta abajo */ }
  }

  const routesPage = http.get(`${BASE}/passenger/routes`, {
    redirects: 0,
    headers: { 'Cookie': 'laravel-session=' + sess },
  });
  if (routesPage.status === 200) {
    const m = routesPage.body.match(/route_id=(\d+)/);
    if (m) routeId = parseInt(m[1], 10);
  }

  console.log(`[setup] login OK · busId=${busId ?? 'NO'} · routeId=${routeId ?? 'NO'} · conectividad verificada`);
  console.log(`[setup] modo=${MODE} · VUs=${VUS} · duración=${DURATION} · base=${BASE}`);

  if (!busId || !routeId) {
    throw new Error(`No se pudieron descubrir IDs reales (busId=${busId}, routeId=${routeId}).`);
  }

  return { busId: busId, routeId: routeId, checkedAt: new Date().toISOString() };
}

// ---------------------------------------------------------------------------
// Escenario de ejecución por usuario virtual
// ---------------------------------------------------------------------------
export default function (data) {
  // Una sesión real por usuario virtual
  if (!session) {
    session = doLogin();
    if (!session) {
      return; // sin autenticación no tiene sentido continuar
    }
  }

  if (MODE === 'api') {
    // Mezcla de lectura de API: 70% lista activa, 20% ubicación puntual, 10% buses por ruta
    const p = Math.random();
    if (p < 0.70) {
      readCheck(`${BASE}/api/buses/active`, 'api_buses_active', isJsonArray, session);
    } else if (p < 0.90) {
      readCheck(`${BASE}/api/buses/${data.busId}/location`, 'api_bus_location', isJsonObj, session);
    } else {
      readCheck(`${BASE}/api/routes/${data.routeId}/buses`, 'api_route_buses', isJsonArray, session);
    }
    sleep(0.5);
  } else {
    // Navegación del pasajero: 50% mapa, 30% autobuses, 20% rutas
    const p = Math.random();
    if (p < 0.50) {
      readCheck(`${BASE}/passenger`, 'web_mapa_pasajero', isHtmlMap, session);
    } else if (p < 0.80) {
      readCheck(`${BASE}/passenger/buses`, 'web_lista_buses', isHtmlList, session);
    } else {
      readCheck(`${BASE}/passenger/routes`, 'web_lista_rutas', isHtmlList, session);
    }
    sleep(1);
  }
}
