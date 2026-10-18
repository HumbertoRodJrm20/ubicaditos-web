"""Flujos del pasajero: lista de autobuses, búsqueda, filtros, modal y mapa."""

import pytest
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from config import (
    USERS,
    WAIT,
    count_visible_bus_cards,
    goto,
    login,
    pause,
    wait_for_element,
)


@pytest.fixture(autouse=True)
def passenger_session(driver):
    """Cada prueba del pasajero arranca con una sesión de pasajero limpia."""
    login(driver, USERS["passenger"])
    return driver


def clear_search_input(driver, selector):
    # Limpia y dispara el evento input para que filterBuses() se ejecute.
    driver.execute_script(
        """
        const input = document.querySelector(arguments[0]);
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        """,
        selector,
    )


def test_la_lista_de_autobuses_muestra_tarjetas_y_la_busqueda_filtra(driver):
    goto(driver, "/passenger/buses")
    wait_for_element(driver, By.CSS_SELECTOR, ".bus-card")

    total = count_visible_bus_cards(driver)
    assert total > 0

    plate = driver.find_element(By.CSS_SELECTOR, ".bus-card").get_attribute("data-plate")
    assert plate

    search = driver.find_element(By.CSS_SELECTOR, "#bus-search")
    search.send_keys(plate)

    # Filtrado client-side: solo debe quedar la tarjeta de esa placa.
    pause(0.3)
    assert count_visible_bus_cards(driver) == 1

    clear_search_input(driver, "#bus-search")
    pause(0.3)
    assert count_visible_bus_cards(driver) == total


def test_el_filtro_por_estado_navega_al_servidor_con_query_string(driver):
    goto(driver, "/passenger/buses")
    wait_for_element(driver, By.CSS_SELECTOR, "#status-chips a")

    maintenance_chip = next(
        (
            chip
            for chip in driver.find_elements(By.CSS_SELECTOR, "#status-chips a")
            if chip.text.strip() == "Mantenimiento"
        ),
        None,
    )
    assert maintenance_chip is not None

    maintenance_chip.click()
    WebDriverWait(driver, WAIT).until(EC.url_contains("status=maintenance"))

    assert "status=maintenance" in driver.current_url


def test_el_chip_de_una_ruta_filtra_la_lista_por_route_id(driver):
    goto(driver, "/passenger/buses")
    wait_for_element(driver, By.CSS_SELECTOR, "#route-chips a")

    # El primer chip es "Todas las rutas"; se toma el segundo (una ruta concreta).
    chips = driver.find_elements(By.CSS_SELECTOR, "#route-chips a")
    assert len(chips) > 1

    chips[1].click()
    WebDriverWait(driver, WAIT).until(EC.url_contains("route_id="))

    assert "route_id=" in driver.current_url


def test_el_modal_de_detalles_muestra_la_disponibilidad_de_asientos(driver):
    goto(driver, "/passenger/buses")
    wait_for_element(driver, By.CSS_SELECTOR, ".bus-card")

    details_button = driver.find_element(
        By.CSS_SELECTOR, 'button[onclick^="showBusDetails"]'
    )
    details_button.click()

    modal = WebDriverWait(driver, 10).until(
        EC.element_to_be_located((By.CSS_SELECTOR, "#bus-details-modal"))
    )
    assert modal.is_displayed() is True

    seats = driver.find_element(By.CSS_SELECTOR, "#bd-seats")
    assert "asiento" in seats.text.lower()

    # Cerrar el modal con Escape.
    ActionChains(driver).send_keys(Keys.ESCAPE).perform()
    pause(0.3)
    assert modal.is_displayed() is False


def test_el_mapa_del_pasajero_carga_la_flota_con_el_contador_de_unidades(driver):
    # El mapa del pasajero es la página principal de su prefijo.
    goto(driver, "/passenger")

    wait_for_element(driver, By.CSS_SELECTOR, "#map")
    counter = wait_for_element(driver, By.CSS_SELECTOR, "#fleet-counter")

    assert "autobus" in counter.text.lower()
