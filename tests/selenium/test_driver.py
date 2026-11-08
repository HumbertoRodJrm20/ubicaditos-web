"""Panel del conductor: unidad asignada, ciclo de viaje y estadísticas."""

import pytest
from selenium.webdriver.common.by import By

from config import USERS, button_by_text, goto, login, wait_for_element, wait_for_flash


@pytest.fixture(autouse=True)
def driver_session(driver):
    """Cada prueba del conductor arranca con una sesión de conductor limpia."""
    login(driver, USERS["driver"])
    return driver


def test_el_panel_del_conductor_muestra_unidad_viaje_y_estadisticas(driver):
    goto(driver, "/driver")

    # Acceso a estadísticas.
    stats_link = wait_for_element(driver, By.CSS_SELECTOR, 'a[href*="driver/statistics"]')
    assert stats_link.is_displayed() is True
    assert stats_link.text.strip() == "Mis estadísticas"

    # La placa de la unidad aparece en el panel.
    plate = driver.find_element(
        By.XPATH, '//span[normalize-space()="Placa:"]/following-sibling::span[1]'
    )
    assert "OAX" in plate.text.strip()


def test_el_conductor_inicia_y_finaliza_un_viaje_desde_el_mapa(driver):
    goto(driver, "/driver/map")
    wait_for_element(driver, By.CSS_SELECTOR, 'form[action*="trip"]')

    # Limpieza idempotente: si quedó un viaje activo de una corrida anterior,
    # se finaliza.
    leftover = driver.find_elements(
        By.XPATH, '//button[normalize-space()="Finalizar Viaje"]'
    )
    if leftover:
        leftover[0].click()
        cleanup_flash = wait_for_flash(driver)
        assert cleanup_flash["kind"] == "success"

    # Iniciar viaje.
    button_by_text(driver, "Iniciar Viaje").click()

    start_flash = wait_for_flash(driver)
    assert "Viaje iniciado" in start_flash["text"]

    # Finalizar viaje (el confirm() se acepta solo por unhandledPromptBehavior).
    button_by_text(driver, "Finalizar Viaje").click()

    end_flash = wait_for_flash(driver)
    assert end_flash["kind"] == "success"
    assert "Viaje finalizado" in end_flash["text"]


def test_las_estadisticas_del_conductor_muestran_kpis_y_desglose(driver):
    goto(driver, "/driver/statistics")

    text = driver.find_element(By.TAG_NAME, "body").text

    assert "Mis estadísticas" in text
    assert "Viajes completados" in text
