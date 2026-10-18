# Pruebas E2E con Selenium WebDriver + pytest (Ubicaditos)

Scripts de Selenium WebDriver en **Python** para verificar los flujos principales de la aplicación en navegador real (Chrome headless). Están pensados para ejecutarse en la **rama de pruebas** contra un servidor local levantado; no corren como parte de `php artisan test`.

## Requisitos

- Python 3.10 o superior.
- Google Chrome instalado. Selenium Manager (incluido en `selenium` 4.6+) descarga chromedriver automáticamente la primera vez.
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

# Opcional pero recomendado: entorno virtual
python3 -m venv .venv && source .venv/bin/activate

pip install -r requirements.txt

python3 -m pytest                       # toda la suite
python3 -m pytest test_auth.py          # solo inicio de sesión/registro
python3 -m pytest test_passenger.py     # solo flujos del pasajero
python3 -m pytest test_driver.py        # solo viaje/estadísticas del conductor
python3 -m pytest test_incident_admin.py  # solo ciclo de incidencias
python3 -m pytest test_responsive.py    # solo verificación responsive
```

Opciones útiles de pytest:

```bash
python3 -m pytest -v                    # nombre de cada prueba
python3 -m pytest -k "mapa or viaje"    # filtra por palabra clave
python3 -m pytest --collect-only        # solo lista las pruebas (no abre el navegador)
```

La URL base por defecto es `http://127.0.0.1:8000`; se puede cambiar con `SELENIUM_BASE_URL`:

```bash
SELENIUM_BASE_URL=http://127.0.0.1:8080 python3 -m pytest
```

## Viewports

Los scripts usan los viewports exigidos por la iteración 3:

| Dispositivo | Resolución |
|-------------|------------|
| Móvil       | 375×667    |
| Escritorio  | 1366×768   |

`test_responsive.py` recorre las vistas clave de pasajero, conductor y administrador en ambos viewports y falla si alguna genera scroll horizontal (> 4 px de tolerancia).

## Credenciales (del seeder)

| Rol         | Email                  | Contraseña |
|-------------|------------------------|------------|
| Admin       | admin@ubicaditos.com   | password   |
| Conductor   | chofer1@ubicaditos.com | password   |
| Pasajero    | pasajero1@ubicaditos.com | password |

## Archivos

| Archivo | Cubre |
|---------|-------|
| `config.py` | Utilidades: build del driver, login, flashes de sesión, overflow horizontal, viewport. |
| `conftest.py` | Fixtures de pytest: navegador compartido por corrida y fábrica de navegadores por viewport. |
| `test_auth.py` | Login válido/inválido, registro de pasajero. |
| `test_passenger.py` | Lista de autobuses, búsqueda instantánea, filtros por estado/ruta, modal de detalles con asientos, mapa con contador de flota. |
| `test_driver.py` | Panel del conductor, ciclo iniciar/finalizar viaje (idempotente), estadísticas con KPIs. |
| `test_incident_admin.py` | Ciclo completo: conductor reporta → administrador resuelve → unidad vuelve a servicio. |
| `test_responsive.py` | Sin scroll horizontal en móvil y escritorio para las vistas clave. |
| `requirements.txt` | Dependencias (`selenium`, `pytest`). |

## Notas

- Los `confirm()` de la interfaz (finalizar viaje, resolver incidencia, eliminar registros) se aceptan automáticamente mediante la capacidad `unhandledPromptBehavior: 'accept'` del navegador.
- `test_incident_admin.py` muta datos reales: deja una incidencia resuelta. La resolución restaura la unidad a "En servicio", por lo que la suite es re-ejecutable; aun así se recomienda `php artisan migrate:fresh --seed` antes de una corrida completa.
- Los flujos que dependen de la ubicación GPS del autobús usan la posición sembrada (cerca de la primera parada de su ruta), por lo que el inicio de viaje funciona sin GPS real.
- El navegador se crea una sola vez por corrida (fixture de sesión en `conftest.py`); `config.login()` limpia las cookies antes de cada inicio de sesión, así que el orden de las pruebas no afecta el resultado.
- Los localizadores usan siempre la API `By.*` de Selenium (`By.CSS_SELECTOR`, `By.XPATH`, `By.TAG_NAME`); los localizadores de tipo cadena (`"css=…"`) no son válidos en la API de Python y habrían fallado.
