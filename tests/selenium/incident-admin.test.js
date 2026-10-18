import { afterAll, beforeAll, expect, test } from 'bun:test';
import { By, until } from 'selenium-webdriver';
import {
    buildDriver,
    buttonByText,
    goto,
    login,
    USERS,
    waitForFlash,
} from './config.js';

let driver;

beforeAll(async () => {
    driver = await buildDriver();
});

afterAll(async () => {
    await driver?.quit();
});

/**
 * Ciclo completo de incidencia (iteración 3):
 *  1. El conductor reporta una avería desde su panel.
 *  2. El administrador la ve con la placa del autobús y la marca como resuelta.
 *  3. Al resolverse, la unidad vuelve al estado "En servicio".
 */
test('reporte de incidencia, resolución administrativa y vuelta al servicio', async () => {
    const description = `Incidencia de prueba Selenium ${Date.now()}`;

    // --- 1) Conductor reporta la incidencia ---
    await login(driver, USERS.driver);
    await goto(driver, '/driver');

    const plate = await driver.findElement(
        By.xpath('//span[normalize-space()="Placa:"]/following-sibling::span[1]'),
    );
    const plateText = (await plate.getText()).trim();
    expect(plateText).toMatch(/OAX/);

    await (await buttonByText(driver, 'Reportar Emergencia')).click();

    const modal = await driver.wait(
        until.elementLocated(By.css('#emergency-modal')),
        10000,
    );
    expect(await modal.isDisplayed()).toBe(true);

    const typeSelect = await driver.findElement(By.css('#emergency-type'));
    await typeSelect.findElement(By.css('option[value="breakdown"]')).click();

    await driver.findElement(By.css('#emergency-description')).sendKeys(description);
    await modal.findElement(By.css('form button[type="submit"]')).click();

    const reportFlash = await waitForFlash(driver);
    expect(reportFlash.kind).toBe('success');
    expect(reportFlash.text).toContain('Emergencia reportada');

    // --- 2) Administrador resuelve la incidencia ---
    await login(driver, USERS.admin);
    await goto(driver, '/admin/emergencies');

    const card = await driver.wait(
        until.elementLocated(By.xpath(`//article[contains(., "${description}")]`)),
        15000,
    );

    // La tarjeta identifica la unidad afectada por placa.
    expect(await card.getText()).toContain(plateText);

    const resolveButton = await card.findElement(
        By.xpath(".//button[contains(., 'Marcar como resuelta')]"),
    );
    await resolveButton.click();

    const resolveFlash = await waitForFlash(driver);
    expect(resolveFlash.kind).toBe('success');
    expect(resolveFlash.text).toContain('resuelta');

    // El filtro por estado la muestra en "Resueltas".
    await goto(driver, '/admin/emergencies?status=resolved');
    const resolvedCard = await driver.wait(
        until.elementLocated(By.xpath(`//article[contains(., "${description}")]`)),
        15000,
    );
    expect(await resolvedCard.isDisplayed()).toBe(true);

    // --- 3) La unidad vuelve al servicio ---
    await goto(driver, '/admin/buses');
    const row = await driver.wait(
        until.elementLocated(By.xpath(`//tr[contains(., "${plateText}")]`)),
        15000,
    );
    expect(await row.getText()).toContain('En servicio');
}, 120000);
