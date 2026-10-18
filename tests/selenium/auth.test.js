import { afterAll, beforeAll, expect, test } from 'bun:test';
import { until } from 'selenium-webdriver';
import { By } from 'selenium-webdriver';
import {
    buildDriver,
    currentUrl,
    goto,
    login,
    USERS,
} from './config.js';

let driver;

beforeAll(async () => {
    driver = await buildDriver();
});

afterAll(async () => {
    await driver?.quit();
});

test('login válido de administrador entra al panel de administración', async () => {
    await login(driver, USERS.admin);

    expect(await currentUrl(driver)).toContain('/admin');
}, 60000);

test('credenciales inválidas permanecen en el inicio de sesión', async () => {
    await goto(driver, '/login');

    const email = await driver.wait(
        until.elementLocated(By.css('input[name="email"]')),
        15000,
    );
    await email.sendKeys(USERS.admin.email);
    await driver.findElement(By.css('input[name="password"]')).sendKeys('clave-equivocada');
    await driver.findElement(By.css('button[type="submit"]')).click();

    // No debe navegar: sigue en /login con el formulario visible.
    await driver.wait(until.elementLocated(By.css('input[name="password"]')), 15000);

    expect(await currentUrl(driver)).toContain('/login');
}, 60000);

test('registro crea una cuenta de pasajero y entra al menú', async () => {
    await goto(driver, '/register');

    const fields = {
        first_name: 'Selenium',
        last_name: 'Prueba',
        maternal_last_name: 'E2E',
        phone: '9511234567',
        email: `selenium.${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123',
    };

    for (const [name, value] of Object.entries(fields)) {
        const input = await driver.wait(
            until.elementLocated(By.css(`input[name="${name}"]`)),
            15000,
        );
        await input.sendKeys(value);
    }

    await driver.findElement(By.css('button[type="submit"]')).click();

    // El registro redirige al menú del pasajero.
    await driver.wait(until.urlMatches(/\/passenger/), 15000);

    expect(await currentUrl(driver)).toContain('/passenger');
}, 60000);
