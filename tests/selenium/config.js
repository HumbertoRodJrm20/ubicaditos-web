/**
 * Utilidades compartidas de las pruebas E2E de Ubicaditos (Selenium WebDriver).
 *
 * Variables de entorno:
 *   SELENIUM_BASE_URL  URL base del servidor Laravel (defecto: http://127.0.0.1:8000)
 *
 * Requiere un servidor levantado con datos sembrados:
 *   php artisan serve
 *   php artisan migrate:fresh --seed   (recomendado antes de una corrida completa)
 */
import { Builder, By, until } from 'selenium-webdriver';
import chrome from 'selenium-webdriver/chrome.js';

export const BASE_URL = (process.env.SELENIUM_BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/+$/, '');

/** Credenciales del seeder (database/seeders/DatabaseSeeder.php). */
export const USERS = {
    admin: { email: 'admin@ubicaditos.com', password: 'password' },
    driver: { email: 'chofer1@ubicaditos.com', password: 'password' },
    passenger: { email: 'pasajero1@ubicaditos.com', password: 'password' },
};

/** Viewports exigidos por la iteración 3. */
export const VIEWPORTS = {
    mobile: { width: 375, height: 667 },
    desktop: { width: 1366, height: 768 },
};

/**
 * Crea un controlador Chrome headless con el viewport indicado.
 * `unhandledPromptBehavior: 'accept'` hace que los confirm() de la interfaz
 * (finalizar viaje, resolver incidencia, eliminar registros) se acepten solos.
 */
export async function buildDriver(viewport = VIEWPORTS.desktop) {
    const options = new chrome.Options()
        .addArguments(
            '--headless=new',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-notifications',
            `--window-size=${viewport.width},${viewport.height}`,
        )
        .setAlertBehavior('accept');

    const driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(options)
        .build();

    await driver.manage().window().setRect({ x: 0, y: 0, width: viewport.width, height: viewport.height });
    await driver.manage().setTimeouts({ implicit: 0, pageLoad: 45000, script: 30000 });

    return driver;
}

export async function goto(driver, path) {
    await driver.get(`${BASE_URL}${path}`);
}

export async function currentUrl(driver) {
    return driver.getCurrentUrl();
}

/** Inicia sesión y espera a caer en el panel del rol correspondiente. */
export async function login(driver, user) {
    // Limpia la sesión anterior para que /login sea accesible aunque ya haya un usuario autenticado.
    await driver.manage().deleteAllCookies();

    await goto(driver, '/login');

    const email = await driver.wait(until.elementLocated(By.css('input[name="email"]')), 15000);
    await email.clear();
    await email.sendKeys(user.email);

    const password = await driver.findElement(By.css('input[name="password"]'));
    await password.clear();
    await password.sendKeys(user.password);

    await driver.findElement(By.css('button[type="submit"]')).click();

    await driver.wait(until.urlMatches(/\/(admin|driver|passenger)/), 15000);
}

export async function bodyText(driver) {
    return driver.findElement(By.css('body')).getText();
}

/** Devuelve el texto del primer [role="alert"] visible (flash de sesión) o null. */
export async function firstAlertText(driver) {
    const alerts = await driver.findElements(By.css('[role="alert"]'));
    for (const alert of alerts) {
        if (await alert.isDisplayed()) {
            const text = (await alert.getText()).trim();
            if (text) return text;
        }
    }
    return null;
}

/**
 * Espera un flash de sesión (éxito o error) y devuelve `{ kind, text }` donde
 * kind es 'success' | 'error'. Útil para validar respuestas de POST con
 * redirección, y para diagnosticar fallos mostrando el texto real del error.
 */
export async function waitForFlash(driver, timeout = 20000) {
    const matches = async () => {
        const found = [];
        const alerts = await driver.findElements(By.css('[role="alert"]'));
        for (const alert of alerts) {
            if (!(await alert.isDisplayed())) continue;
            const css = (await alert.getAttribute('class')) ?? '';
            if (css.includes('bg-emerald')) found.push({ kind: 'success', text: (await alert.getText()).trim() });
            else if (css.includes('bg-rose')) found.push({ kind: 'error', text: (await alert.getText()).trim() });
        }
        return found;
    };

    await driver.wait(async () => (await matches()).length > 0, timeout);

    const found = await matches();
    return found[0] ?? null;
}

/** Busca un botón por su texto visible (coincidencia exacta, normalizada). */
export async function buttonByText(driver, text) {
    const buttons = await driver.findElements(By.css('button, a[role="button"]'));
    for (const button of buttons) {
        if (await button.isDisplayed() && (await button.getText()).trim() === text) {
            return button;
        }
    }
    throw new Error(`No se encontró el botón visible: "${text}"`);
}

/** Cuenta tarjetas de autobús visibles en la lista del pasajero. */
export async function countVisibleBusCards(driver) {
    return driver.executeScript(
        "return [...document.querySelectorAll('.bus-card')].filter(card => !card.classList.contains('hidden')).length;",
    );
}

/**
 * Diferencia de ancho horizontal (scroll) de la página.
 * Un valor > 4 px indica contenido que desborda el viewport en móvil.
 */
export async function horizontalOverflow(driver) {
    return driver.executeScript(
        'return document.documentElement.scrollWidth - document.documentElement.clientWidth;',
    );
}
