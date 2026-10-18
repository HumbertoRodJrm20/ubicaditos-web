import { afterAll, beforeAll, expect, test } from 'bun:test';
import { By, until } from 'selenium-webdriver';
import { buildDriver, buttonByText, goto, login, USERS, waitForFlash } from './config.js';

let driver;

beforeAll(async () => {
    driver = await buildDriver();
    await login(driver, USERS.driver);
});

afterAll(async () => {
    await driver?.quit();
});

test('el panel del conductor muestra unidad, viaje y acceso a estadísticas', async () => {
    await goto(driver, '/driver');

    // Acceso a estadísticas.
    const statsLink = await driver.wait(
        until.elementLocated(By.css('a[href*="driver/statistics"]')),
        15000,
    );
    expect(await statsLink.isDisplayed()).toBe(true);
    expect((await statsLink.getText()).trim()).toBe('Mis estadísticas');

    // La placa de la unidad aparece en el panel.
    const plate = await driver.findElement(
        By.xpath('//span[normalize-space()="Placa:"]/following-sibling::span[1]'),
    );
    expect((await plate.getText()).trim()).toMatch(/OAX/);
}, 60000);

test('el conductor inicia y finaliza un viaje desde el mapa', async () => {
    await goto(driver, '/driver/map');
    await driver.wait(until.elementLocated(By.css('form[action*="trip"]')), 15000);

    // Limpieza idempotente: si quedó un viaje activo de una corrida anterior, se finaliza.
    const leftover = await driver.findElements(
        By.xpath('//button[normalize-space()="Finalizar Viaje"]'),
    );
    if (leftover.length > 0) {
        await leftover[0].click();
        const cleanupFlash = await waitForFlash(driver);
        expect(cleanupFlash.kind).toBe('success');
    }

    // Iniciar viaje.
    const startButton = await buttonByText(driver, 'Iniciar Viaje');
    await startButton.click();

    const startFlash = await waitForFlash(driver);
    expect(startFlash.text).toContain('Viaje iniciado');

    // Finalizar viaje (el confirm() se acepta solo por unhandledPromptBehavior).
    const endButton = await buttonByText(driver, 'Finalizar Viaje');
    await endButton.click();

    const endFlash = await waitForFlash(driver);
    expect(endFlash.kind).toBe('success');
    expect(endFlash.text).toContain('Viaje finalizado');
}, 90000);

test('las estadísticas del conductor muestran KPIs y desglose', async () => {
    await goto(driver, '/driver/statistics');

    const text = await (await driver.findElement(By.css('body'))).getText();

    expect(text).toContain('Mis estadísticas');
    expect(text).toContain('Viajes completados');
}, 60000);
