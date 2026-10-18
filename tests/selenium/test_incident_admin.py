"""Ciclo completo de incidencias entre conductor y administrador (iteración 3)."""

import time

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from config import USERS, WAIT, button_by_text, goto, login, wait_for_flash


def test_reporte_de_incidencia_resolucion_administrativa_y_vuelta_al_servicio(driver):
    """
    Ciclo completo de incidencia (iteración 3):
      1. El conductor reporta una avería desde su panel.
      2. El administrador la ve con la placa del autobús y la marca como resuelta.
      3. Al resolverse, la unidad vuelve al estado "En servicio".
    """
    description = f"Incidencia de prueba Selenium {int(time.time() * 1000)}"

    # --- 1) Conductor reporta la incidencia ---
    login(driver, USERS["driver"])
    goto(driver, "/driver")

    plate = driver.find_element(
        By.XPATH, '//span[normalize-space()="Placa:"]/following-sibling::span[1]'
    )
    plate_text = plate.text.strip()
    assert "OAX" in plate_text

    button_by_text(driver, "Reportar Emergencia").click()

    modal = WebDriverWait(driver, 10).until(
        EC.element_to_be_located((By.CSS_SELECTOR, "#emergency-modal"))
    )
    assert modal.is_displayed() is True

    type_select = driver.find_element(By.CSS_SELECTOR, "#emergency-type")
    type_select.find_element(By.CSS_SELECTOR, 'option[value="breakdown"]').click()

    driver.find_element(By.CSS_SELECTOR, "#emergency-description").send_keys(description)
    modal.find_element(By.CSS_SELECTOR, 'form button[type="submit"]').click()

    report_flash = wait_for_flash(driver)
    assert report_flash["kind"] == "success"
    assert "Emergencia reportada" in report_flash["text"]

    # --- 2) Administrador resuelve la incidencia ---
    login(driver, USERS["admin"])
    goto(driver, "/admin/emergencies")

    card = WebDriverWait(driver, WAIT).until(
        EC.element_to_be_located(
            (By.XPATH, f'//article[contains(., "{description}")]')
        )
    )

    # La tarjeta identifica la unidad afectada por placa.
    assert plate_text in card.text

    resolve_button = card.find_element(
        By.XPATH, ".//button[contains(., 'Marcar como resuelta')]"
    )
    resolve_button.click()

    resolve_flash = wait_for_flash(driver)
    assert resolve_flash["kind"] == "success"
    assert "resuelta" in resolve_flash["text"]

    # El filtro por estado la muestra en "Resueltas".
    goto(driver, "/admin/emergencies?status=resolved")
    resolved_card = WebDriverWait(driver, WAIT).until(
        EC.element_to_be_located(
            (By.XPATH, f'//article[contains(., "{description}")]')
        )
    )
    assert resolved_card.is_displayed() is True

    # --- 3) La unidad vuelve al servicio ---
    goto(driver, "/admin/buses")
    row = WebDriverWait(driver, WAIT).until(
        EC.element_to_be_located((By.XPATH, f'//tr[contains(., "{plate_text}")]'))
    )
    assert "En servicio" in row.text
