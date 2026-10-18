/**
 * Pruebas de carga — Iteración 3: Búsqueda/filtros, tiempo real, estadísticas
 * de conductor e incidencias. Proyecto: Ubicaditos (Laravel 12)
 *
 * ALCANCE: únicamente endpoints de LECTURA nuevos o extendidos en la
 * iteración 3. Nada modifica estado:
 *   - Pasajero: lista de autobuses con filtros (q, status, route_id, sort) y
 *     el endpoint de polling en vivo /api/buses/active.
 *   - Conductor: panel, mapa y estadísticas (GET, sin iniciar/finalizar viajes).
 *   - Admin: panel, incidencias (con filtro por estado), lista de autobuses y
 *     detalle de un autobús real.
 *
 * EXCLUIDO A PROPOSITO (nunca se envía): driver/update-location, start-trip,
 * end-trip, update-passengers, emergency, admin/emergencies/{id}/resolve, y
 * cualquier POST/PUT/DELETE. No ejecutar `buses:simulate` durante la prueba.
 *
 * AUTENTICACIÓN: patrón heredado de la iteración 2 — cada VU realiza su propio
 * login (un usuario por rol) y propaga la cookie de sesión de forma EXPLÍCITA
 * en cada petición (la cookie jar automática pierde sesiones con VUs
 * concurrentes, verificado en la iteración 2).
 *
 * ROLES: cada VU se asigna por `__VU % 3` (pasajero, conductor, admin) o se
 * puede fijar todos con -e K6_ROLE=passenger|driver|admin.
 *
 * Uso (desde la raíz del proyecto):
 *   k6 run tests/load/ubicaditos-iteration3-load.js -e K6_VUS=15 -e K6_DURATION=30s
 *   k6 run tests/load/ubicaditos-iteration3-load.js -e K6_ROLE=passenger -e K6_VUS=25
 *
 * Variables:
 *   K6_VUS        usuarios virtuales        (por defecto 15; se reparten entre 3 roles)
 *   K6_DURATION   duración                  (por defecto 30s)
 *   K6_BASE_URL   base                      (por defecto http://127.0.0.1:8000)
 *   K6_ROLE       rol fijo (passenger|driver|admin); vacío = reparto por VU
 *   K6_PASSENGER_EMAIL / K6_DRIVER_EMAIL / K6_ADMIN_EMAIL  usuarios del seeder
 *   K6_PASSWORD   contraseña común          (por defecto password)
 */

import http from 'k6/http';
import { check, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ---------------------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------------------
const BASE = __ENV.K6_BASE_URL || 'http://127.0.0.1:8000';
const VUS = parseInt(__ENV.K6_VUS || '15', 10);
const DURATION = __ENV.K6_DURATION || '30s';
const ROLE_PIN = (__ENV.K6_ROLE || '').toLowerCase();
const PASSWORD = __ENV.K6_PASSWORD || 'password';

const CREDENTIALS = {
  passenger: __ENV.K6_PASSENGER_EMAIL || 'pasajero1@ubicaditos.com',
  driver: __ENV.K6_DRIVER_EMAIL || 'chofer1@ubicaditos.com',
  admin: __ENV.K6_ADMIN_EMAIL || 'admin@ubicaditos.com',
};

// ---------------------------------------------------------------------------
// Métricas propias
// ---------------------------------------------------------------------------
const logins = new Counter('i3_logins');
const authFailures = new Rate('i3_auth_failures');
const readTrend = new Trend('i3_read_duration', true);

export const options = {
  scenarios: {
    load: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  tags: {
    iteration: '3',
  },
  thresholds: {
    'i3_read_duration': ['p(95)<800'],
    'http_req_failed{kind:read}': ['rate<0.01'],
    'checks': ['rate>0.99'],
  },
};

// ---------------------------------------------------------------------------
// Utilidades (mismas que la iteración 2: CSRF de formulario + cookies manuales)
// ---------------------------------------------------------------------------
function extractToken(html) {
  const m = html.match(/name="_token"\s+value="([^"]+)"/);
  return m ? m[1] : '';
}

function sessionCookie(res) {
  const sc = res.headers['Set-Cookie'] || res.headers['set-cookie'] || '';
  const m = sc.match(/laravel-session=([^;,]+)/);
  return m ? m[1] : '';
}

/**
 * Login real (Breeze) con CSRF de formulario y cookies explícitas.
 * Devuelve la cookie de sesión autenticada o cadena vacía si falla.
 */
function doLogin(role) {
  let sess = '';
  const email = CREDENTIALS[role];

  group(`autenticacion[${role}]`, () => {
    const page = http.get(`${BASE}/login`, { tags: { kind: 'auth', name: 'login_form' } });
    check(page, { 'login: formulario disponible (200)': (r) => r.status === 200 });
    const guestCookie = sessionCookie(page);

    const body = [
      `_token=${encodeURIComponent(extractToken(page.body))}`,
      `email=${encodeURIComponent(email)}`,
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

/** GET medido: status + comprobación funcional del contenido. Devuelve la respuesta. */
function readCheck(url, name, expectFn, sess) {
  const res = http.get(url, {
    redirects: 0,
    headers: { 'Cookie': 'laravel-session=' + sess },
    tags: { kind: 'read', name: name },
  });
  readTrend.add(res.timings.duration);

  authFailures.add(res.status === 200 ? 0 : 1);

  check(res, {
    [`${name}: HTTP 200`]: (r) => r.status === 200,
    [`${name}: contenido esperado`]: (r) => expectFn(r),
  }, { endpoint: name });

  return res;
}

const isHtmlList = (r) => /<html/i.test(r.body || '');
const isJsonArray = (r) => (r.body || '').trimStart().startsWith('[');
const isHtmlMap = (r) => /leaflet/i.test(r.body || '');

// Estado por VU (cada VU de k6 tiene su propia copia del init context)
let session = '';
let vuRole = 'passenger';

// ---------------------------------------------------------------------------
// setup(): conectividad + ID real de autobús (nunca se inventan identificadores).
// ---------------------------------------------------------------------------
export function setup() {
  const page = http.get(`${BASE}/login`);
  if (page.status !== 200) {
    throw new Error(`No se pudo obtener /login (status ${page.status}). ¿Está el servidor activo en ${BASE}?`);
  }

  const guestCookie = sessionCookie(page);
  const loginBody = [
    `_token=${encodeURIComponent(extractToken(page.body))}`,
    `email=${encodeURIComponent(CREDENTIALS.admin)}`,
    `password=${encodeURIComponent(PASSWORD)}`,
    'remember=0',
  ].join('&');
  const loginRes = http.post(`${BASE}/login`, loginBody, {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Cookie': 'laravel-session=' + guestCookie,
    },
    redirects: 0,
  });
  const adminSess = sessionCookie(loginRes);

  const busesPage = http.get(`${BASE}/admin/buses`, {
    headers: { 'Cookie': 'laravel-session=' + adminSess },
  });
  const busIdMatch = (busesPage.body || '').match(/\/admin\/buses\/(\d+)/);

  if (!busIdMatch) {
    throw new Error('No se encontró ningún id de autobús en /admin/buses. Ejecuta el seeder (php artisan migrate:fresh --seed).');
  }

  return { busId: parseInt(busIdMatch[1], 10) };
}

// ---------------------------------------------------------------------------
// Flujos por rol (solo lectura)
// ---------------------------------------------------------------------------
function passengerFlow(data, sess) {
  group('pasajero[listado]', () => {
    const base = readCheck(`${BASE}/passenger/buses`, 'buses_base', isHtmlList, sess);

    // El id de ruta se descubre de la propia página (chips de filtro), nunca se inventa.
    const routeIdMatch = ((base && base.body) || '').match(/route_id=(\d+)/);

    readCheck(`${BASE}/passenger/buses?q=oax`, 'buses_search', isHtmlList, sess);
    readCheck(`${BASE}/passenger/buses?status=active`, 'buses_status', isHtmlList, sess);
    readCheck(`${BASE}/passenger/buses?sort=occupancy`, 'buses_sort', isHtmlList, sess);
    if (routeIdMatch) {
      readCheck(`${BASE}/passenger/buses?route_id=${routeIdMatch[1]}`, 'buses_route', isHtmlList, sess);
    }
  });

  group('pasajero[tiempo-real]', () => {
    readCheck(`${BASE}/api/buses/active`, 'api_active_buses', isJsonArray, sess);
  });
}

function driverFlow(data, sess) {
  group('conductor[paneles]', () => {
    readCheck(`${BASE}/driver`, 'driver_dashboard', isHtmlList, sess);
    readCheck(`${BASE}/driver/map`, 'driver_map', isHtmlMap, sess);
    readCheck(`${BASE}/driver/statistics`, 'driver_statistics', isHtmlList, sess);
  });
}

function adminFlow(data, sess) {
  group('admin[lecturas]', () => {
    readCheck(`${BASE}/admin`, 'admin_dashboard', isHtmlMap, sess);
    readCheck(`${BASE}/admin/emergencies`, 'admin_emergencies', isHtmlList, sess);
    readCheck(`${BASE}/admin/emergencies?status=pending`, 'admin_emergencies_pending', isHtmlList, sess);
    readCheck(`${BASE}/admin/buses`, 'admin_buses_index', isHtmlList, sess);
    readCheck(`${BASE}/admin/buses/${data.busId}`, 'admin_bus_show', isHtmlList, sess);
  });
}

const FLOWS = {
  passenger: passengerFlow,
  driver: driverFlow,
  admin: adminFlow,
};

// ---------------------------------------------------------------------------
// Ciclo de vida (k6 no tiene hook por VU: el login se hace perezosamente en la
// primera iteración de cada VU, quedando una sesión por usuario virtual)
// ---------------------------------------------------------------------------
export default function (data) {
  if (!session) {
    const roles = ['passenger', 'driver', 'admin'];
    vuRole = ROLE_PIN && FLOWS[ROLE_PIN] ? ROLE_PIN : roles[(__VU - 1) % roles.length];
    session = doLogin(vuRole);
    if (!session) return;
  }

  FLOWS[vuRole](data, session);
}
