"""
Utilidades compartidas de las pruebas E2E de Ubicaditos (Selenium WebDriver + pytest).

Variables de entorno:
    SELENIUM_BASE_URL  URL base del servidor Laravel (defecto: http://127.0.0.1:8000)

Requiere un servidor levantado con datos sembrados:
    php artisan serve
    php artisan migrate:fresh --seed   (recomendado antes de una corrida completa)
"""

import os
import time

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

BASE_URL = os.environ.get("SELENIUM_BASE_URL", "http://127.0.0.1:8000").rstrip("/")

#: Espera estándar para localizar elementos (segundos).
WAIT = 15

#: Credenciales del seeder (database/seeders/DatabaseSeeder.php).
USERS = {
    "admin": {"email": "admin@ubicaditos.com", "password": "password"},
    "driver": {"email": "chofer1@ubicaditos.com", "password": "password"},
    "passenger": {"email": "pasajero1@ubicaditos.com", "password": "password"},
}

#: Viewports exigidos por la iteración 3.
VIEWPORTS = {
    "mobile": {"width": 375, "height": 667},
    "desktop": {"width": 1366, "height": 768},
}


def build_driver(viewport=None):
    """Crea un controlador Chrome headless con el viewport indicado.

    ``unhandledPromptBehavior: 'accept'`` hace que los ``confirm()`` de la
    interfaz (finalizar viaje, resolver incidencia, eliminar registros) se
    acepten solos.
    """
    viewport = viewport or VIEWPORTS["desktop"]

    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--disable-notifications")
    options.add_argument(f"--window-size={viewport['width']},{viewport['height']}")
    options.set_capability("unhandledPromptBehavior", "accept")

    driver = webdriver.Chrome(options=options)
    driver.set_window_rect(0, 0, viewport["width"], viewport["height"])
    driver.implicitly_wait(0)
    driver.set_page_load_timeout(45)
    driver.set_script_timeout(30)
    return driver


def goto(driver, path):
    driver.get(BASE_URL + path)


def wait_for_element(driver, by, value, timeout=None):
    """Espera a que un elemento exista y lo devuelve."""
    return WebDriverWait(driver, timeout or WAIT).until(EC.element_to_be_located((by, value)))


def login(driver, user):
    """Inicia sesión y espera a caer en el panel del rol correspondiente."""
    # Limpia la sesión anterior para que /login sea accesible aunque ya haya
    # un usuario autenticado.
    driver.delete_all_cookies()

    goto(driver, "/login")

    email = wait_for_element(driver, By.CSS_SELECTOR, 'input[name="email"]')
    email.clear()
    email.send_keys(user["email"])

    password = driver.find_element(By.CSS_SELECTOR, 'input[name="password"]')
    password.clear()
    password.send_keys(user["password"])

    driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()

    WebDriverWait(driver, WAIT).until(EC.url_matches(r"/(admin|driver|passenger)"))


def body_text(driver):
    return driver.find_element(By.TAG_NAME, "body").text


def first_alert_text(driver):
    """Devuelve el texto del primer ``[role="alert"]`` visible o ``None``."""
    for alert in driver.find_elements(By.CSS_SELECTOR, '[role="alert"]'):
        if alert.is_displayed():
            text = alert.text.strip()
            if text:
                return text
    return None


def _visible_flashes(driver):
    found = []
    for alert in driver.find_elements(By.CSS_SELECTOR, '[role="alert"]'):
        if not alert.is_displayed():
            continue
        css = alert.get_attribute("class") or ""
        text = alert.text.strip()
        if "bg-emerald" in css:
            found.append({"kind": "success", "text": text})
        elif "bg-rose" in css:
            found.append({"kind": "error", "text": text})
    return found


def wait_for_flash(driver, timeout=20):
    """Espera un flash de sesión (éxito o error) y devuelve ``{kind, text}``.

    ``kind`` es ``'success'`` o ``'error'``. Útil para validar respuestas de
    POST con redirección, y para diagnosticar fallos mostrando el texto real
    del error.
    """
    WebDriverWait(driver, timeout).until(lambda d: len(_visible_flashes(d)) > 0)
    return _visible_flashes(driver)[0]


def button_by_text(driver, text):
    """Busca un botón por su texto visible (coincidencia exacta, normalizada)."""
    for button in driver.find_elements(By.CSS_SELECTOR, 'button, a[role="button"]'):
        if button.is_displayed() and button.text.strip() == text:
            return button
    raise AssertionError(f'No se encontró el botón visible: "{text}"')


def count_visible_bus_cards(driver):
    """Cuenta tarjetas de autobús visibles en la lista del pasajero."""
    return driver.execute_script(
        "return [...document.querySelectorAll('.bus-card')]"
        ".filter(card => !card.classList.contains('hidden')).length;"
    )


def horizontal_overflow(driver):
    """Diferencia de ancho horizontal (scroll) de la página.

    Un valor > 4 px indica contenido que desborda el viewport en móvil.
    """
    return driver.execute_script(
        "return document.documentElement.scrollWidth - document.documentElement.clientWidth;"
    )


def pause(seconds):
    """Pausa breve (p. ej. para que un filtrado client-side se asiente)."""
    time.sleep(seconds)
