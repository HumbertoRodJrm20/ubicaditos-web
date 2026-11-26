# Rendimiento y latencia

Estado del 2026-11-25 para RNF-06 y RNF-14.

## Evidencia ejecutada

`tests/Feature/Performance/HotEndpointQueryTest.php` mide el número de consultas de `GET /api/buses/active` con 2 y 12 autobuses activos. El endpoint mantiene el conteo casi constante gracias a `with(['driver','routes'])` y `withCount([...])`: la diferencia observada en la suite es de 0 consultas al pasar de 2 a 12 unidades. La prueba fallaría si se reintrodujera un N+1 por autobús.

`tests/Feature/RealTime/BusLocationUpdatedTest.php` cubre que `update-location` despacha `BusLocationUpdated` en el canal privado `buses`; `tests/Feature/Passenger/BusLocationFreshnessTest.php` cubre el payload de sondeo con la misma definición de frescura. Son pruebas funcionales, **no medidas de latencia**.

## Pendiente de ejecución

| Requisito | Herramienta | Estado |
|-----------|-------------|--------|
| RNF-06 p95 de actualización → evento en cliente | k6 + cliente Reverb o navegador instrumentado | No ejecutado. No se dispone de umbral confirmado en la sección 4.2 de requisitos; no se inventa un valor. |
| RNF-14 carga sostenida y rendimiento | `tests/load/ubicaditos-iteration4-load.js` con k6 | No ejecutado. No hay registro de VUs, duración, p50/p95 ni tasa de error en esta sesión. |
| RNF-06/RNF-14 en infraestructura similar a producción | PostgreSQL + Reverb + cola | No ejecutado. El entorno verificado usa SQLite en memoria y cola síncrona. |

## Cómo documentar una ejecución futura

Registrar fecha, versión de k6, perfil (`K6_VUS`, duración), entorno (local o producción-like), p50/p95, tasa de error y cuello de botella detectado. Sólo entonces se podrá marcar RNF-06/RNF-14 como completos. La ausencia de umbral numérico y de lista de navegadores acordada debe resolverse con el propietario antes de declarar aprobación.
