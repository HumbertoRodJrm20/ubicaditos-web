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


def test_el_me_gusta_de_una_ruta_actualiza_el_estado_y_el_contador(driver):
    goto(driver, "/passenger/routes")
    like_button = wait_for_element(driver, By.CSS_SELECTOR, "[data-like-route]")

    initial_liked = like_button.get_attribute("data-liked")
    initial_count = int(like_button.find_element(By.CSS_SELECTOR, "[data-like-count]").text)

    like_button.click()

    WebDriverWait(driver, 10).until(
        lambda d: d.find_element(By.CSS_SELECTOR, "[data-like-route]").get_attribute("data-liked")
        != initial_liked
    )

    like_button = driver.find_element(By.CSS_SELECTOR, "[data-like-route]")
    new_count = int(like_button.find_element(By.CSS_SELECTOR, "[data-like-count]").text)

    assert like_button.get_attribute("aria-pressed") == like_button.get_attribute("data-liked")
    assert new_count != initial_count


def test_el_favorito_de_una_ruta_se_refleja_en_el_filtro_favoritas(driver):
    goto(driver, "/passenger/routes")
    favorite_button = wait_for_element(driver, By.CSS_SELECTOR, "[data-favorite-route]")
    was_favorite = favorite_button.get_attribute("data-favorite")

    # Nos aseguramos de que la ruta esté marcada como favorita.
    if was_favorite != "true":
        favorite_button.click()
        WebDriverWait(driver, 10).until(
            lambda d: d.find_element(By.CSS_SELECTOR, "[data-favorite-route]").get_attribute("data-favorite")
            == "true"
        )

    next(button for button in driver.find_elements(By.TAG_NAME, "button") if button.text.strip() == "Favoritas").click()
    pause(0.4)

    visible_favorites = driver.execute_script(
        """
        return [...document.querySelectorAll('article')].filter(
            (article) => article.offsetParent !== null
                && article.querySelector('[data-favorite="true"]') !== null
        ).length;
        """
    )

    assert visible_favorites >= 1


def test_el_panel_de_ubicacion_compartida_crea_y_detiene_el_enlace(driver):
    goto(driver, "/passenger")
    wait_for_element(driver, By.CSS_SELECTOR, "#map")

    driver.find_element(By.CSS_SELECTOR, "#share-location-btn").click()
    panel = wait_for_element(driver, By.CSS_SELECTOR, "#share-location-panel")
    assert panel.is_displayed() is True

    driver.find_element(By.CSS_SELECTOR, "#share-start-btn").click()

    share_link = wait_for_element(driver, By.CSS_SELECTOR, "#share-link")
    WebDriverWait(driver, 15).until(lambda d: "/share/" in (share_link.get_attribute("value") or ""))
    assert wait_for_element(driver, By.CSS_SELECTOR, "#share-active").is_displayed() is True

    driver.find_element(By.CSS_SELECTOR, "#share-stop-btn").click()
    WebDriverWait(driver, 15).until(
        lambda d: d.find_element(By.CSS_SELECTOR, "#share-inactive").is_displayed()
    )
    assert (share_link.get_attribute("value") or "") == ""
