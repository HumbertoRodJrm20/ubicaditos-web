"""Pruebas de autenticación: login válido/inválido y registro de pasajero."""

import time

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from config import USERS, WAIT, goto, login, wait_for_element


def test_login_valido_de_administrador_entra_al_panel(driver):
    login(driver, USERS["admin"])

    assert "/admin" in driver.current_url


def test_credenciales_invalidas_permanecen_en_el_inicio_de_sesion(driver):
    # Solo un visitante sin sesión puede ver /login: se limpia la sesión
    # anterior (por si el test anterior dejó al administrador dentro).
    driver.delete_all_cookies()
    goto(driver, "/login")

    email = wait_for_element(driver, By.CSS_SELECTOR, 'input[name="email"]')
    email.send_keys(USERS["admin"]["email"])
    driver.find_element(By.CSS_SELECTOR, 'input[name="password"]').send_keys(
        "clave-equivocada"
    )
    driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()

    # No debe navegar: sigue en /login con el formulario visible.
    wait_for_element(driver, By.CSS_SELECTOR, 'input[name="password"]')

    assert "/login" in driver.current_url


def test_registro_crea_una_cuenta_de_pasajero_y_entra_al_menu(driver):
    goto(driver, "/register")

    fields = {
        "first_name": "Selenium",
        "last_name": "Prueba",
        "maternal_last_name": "E2E",
        "phone": "9511234567",
        "email": f"selenium.{int(time.time() * 1000)}@example.com",
        "password": "password123",
        "password_confirmation": "password123",
    }

    for name, value in fields.items():
        input_el = wait_for_element(driver, By.CSS_SELECTOR, f'input[name="{name}"]')
        input_el.send_keys(value)

    driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()

    # El registro redirige al menú del pasajero.
    WebDriverWait(driver, WAIT).until(EC.url_matches(r"/passenger"))

    assert "/passenger" in driver.current_url
