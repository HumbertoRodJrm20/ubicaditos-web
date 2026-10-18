import { afterAll, beforeAll, expect, test } from 'bun:test';
import { By, Key, until } from 'selenium-webdriver';
import {
    buildDriver,
    countVisibleBusCards,
    currentUrl,
    goto,
    login,
    USERS,
} from './config.js';

let driver;

beforeAll(async () => {
    driver = await buildDriver();
    await login(driver, USERS.passenger);
});

afterAll(async () => {
    await driver?.quit();
});

async function clearSearchInput(selector) {
    // Limpia y dispara el evento input para que filterBuses() se ejecute.
    await driver.executeScript(`
        const input = document.querySelector('${selector}');
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    `);
}

test('la lista de autobuses muestra tarjetas y la búsqueda filtra al instante', async () => {
    await goto(driver, '/passenger/buses');
    await driver.wait(until.elementLocated(By.css('.bus-card')), 15000);

    const total = await countVisibleBusCards(driver);
    expect(total).toBeGreaterThan(0);

    const plate = await driver.findElement(By.css('.bus-card')).getAttribute('data-plate');
    expect(plate).toBeTruthy();

    const search = await driver.findElement(By.css('#bus-search'));
    await search.sendKeys(plate);

    // Filtrado client-side: solo debe quedar la tarjeta de esa placa.
    await driver.sleep(300);
    expect(await countVisibleBusCards(driver)).toBe(1);

    await clearSearchInput('#bus-search');
    await driver.sleep(300);
    expect(await countVisibleBusCards(driver)).toBe(total);
}, 60000);

test('el filtro por estado navega al servidor con query string', async () => {
    await goto(driver, '/passenger/buses');
    await driver.wait(until.elementLocated(By.css('#status-chips a')), 15000);

    const chips = await driver.findElements(By.css('#status-chips a'));
    let maintenanceChip = null;
    for (const chip of chips) {
        if ((await chip.getText()).trim() === 'Mantenimiento') {
            maintenanceChip = chip;
            break;
        }
    }
    expect(maintenanceChip).toBeTruthy();

    await maintenanceChip.click();
    await driver.wait(until.urlContains('status=maintenance'), 15000);

    expect(await currentUrl(driver)).toContain('status=maintenance');
}, 60000);

test('el chip de una ruta filtra la lista por route_id', async () => {
    await goto(driver, '/passenger/buses');
    await driver.wait(until.elementLocated(By.css('#route-chips a')), 15000);

    // El primer chip es "Todas las rutas"; se toma el segundo (una ruta concreta).
    const chips = await driver.findElements(By.css('#route-chips a'));
    expect(chips.length).toBeGreaterThan(1);

    await chips[1].click();
    await driver.wait(until.urlContains('route_id='), 15000);

    expect(await currentUrl(driver)).toContain('route_id=');
}, 60000);

test('el modal de detalles muestra la disponibilidad de asientos', async () => {
    await goto(driver, '/passenger/buses');
    await driver.wait(until.elementLocated(By.css('.bus-card')), 15000);

    const detailsButton = await driver.findElement(
        By.css('button[onclick^="showBusDetails"]'),
    );
    await detailsButton.click();

    const modal = await driver.wait(
        until.elementLocated(By.css('#bus-details-modal')),
        10000,
    );
    expect(await modal.isDisplayed()).toBe(true);

    const seats = await driver.findElement(By.css('#bd-seats'));
    expect((await seats.getText()).toLowerCase()).toContain('asiento');

    // Cerrar el modal con Escape.
    await driver.actions().sendKeys(Key.ESCAPE).perform();
    await driver.sleep(300);
    expect(await modal.isDisplayed()).toBe(false);
}, 60000);

test('el mapa del pasajero carga la flota con el contador de unidades', async () => {
    await goto(driver, '/passenger/dashboard');

    await driver.wait(until.elementLocated(By.css('#map')), 15000);
    const counter = await driver.wait(
        until.elementLocated(By.css('#fleet-counter')),
        15000,
    );

    expect((await counter.getText()).toLowerCase()).toContain('autobus');
}, 60000);
