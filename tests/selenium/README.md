# Pruebas E2E con Selenium WebDriver (Ubicaditos)

Scripts de Selenium WebDriver para verificar los flujos principales de la aplicación en navegador real (Chrome headless). Están pensados para ejecutarse en la **rama de pruebas** contra un servidor local levantado; no corren como parte de `php artisan test`.

## Requisitos

- Node.js 20+ con **bun** (`curl -fsSL https://bun.sh/install | bash`).
- Google Chrome instalado. Selenium Manager (incluido en `selenium-webdriver` 4.6+) descarga chromedriver automáticamente la primera vez.
- Proyecto Laravel levantado con datos sembrados:

```bash
php artisan migrate:fresh --seed
php artisan serve          # http://127.0.0.1:8000
# opcional, para el WebSocket en vivo:
php artisan reverb:start
```

## Instalación y ejecución

```bash
cd tests/selenium
bun install

bun test                    # toda la suite
bun run test:auth           # solo inicio de sesión/registro
bun run test:passenger      # solo flujos del pasajero
bun run test:driver         # solo viaje/estadísticas del conductor
bun run test:incident       # solo ciclo de incidencias
bun run test:responsive     # solo verificación responsive
```

La URL base por defecto es `http://127.0.0.1:8000`; se puede cambiar con `SELENIUM_BASE_URL`:

```bash
SELENIUM_BASE_URL=http://127.0.0.1:8080 bun test
```

## Viewports

Los scripts usan los viewports exigidos por la iteración 3:

| Dispositivo | Resolución |
|-------------|------------|
| Móvil       | 375×667    |
| Escritorio  | 1366×768   |

`responsive.test.js` recorre las vistas clave de pasajero, conductor y administrador en ambos viewports y falla si alguna genera scroll horizontal (> 4 px de tolerancia).

## Credenciales (del seeder)

| Rol         | Email                  | Contraseña |
|-------------|------------------------|------------|
| Admin       | admin@ubicaditos.com   | password   |
| Conductor   | chofer1@ubicaditos.com | password   |
| Pasajero    | pasajero1@ubicaditos.com | password |

## Archivos

| Archivo | Cubre |
|---------|-------|
| `config.js` | Utilidades: build del driver, login, flashes de sesión, overflow horizontal, viewport. |
| `auth.test.js` | Login válido/inválido, registro de pasajero. |
| `passenger.test.js` | Lista de autobuses, búsqueda instantánea, filtros por estado/ruta, modal de detalles con asientos, mapa con contador de flota. |
| `driver.test.js` | Panel del conductor, ciclo iniciar/finalizar viaje (idempotente), estadísticas con KPIs. |
| `incident-admin.test.js` | Ciclo completo: conductor reporta → administrador resuelve → unidad vuelve a servicio. |
| `responsive.test.js` | Sin scroll horizontal en móvil y escritorio para las vistas clave. |

## Notas

- Los `confirm()` de la interfaz (finalizar viaje, resolver incidencia, eliminar registros) se aceptan automáticamente mediante la capacidad `unhandledPromptBehavior: 'accept'`.
- `incident-admin.test.js` muta datos reales: deja una incidencia resuelta. La resolución restaura la unidad a "En servicio", por lo que la suite es re-ejecutable; aun así se recomienda `php artisan migrate:fresh --seed` antes de una corrida completa.
- Los flujos que dependen de la ubicación GPS del autobús usan la posición sembrada (cerca de la primera parada de su ruta), por lo que el inicio de viaje funciona sin GPS real.
