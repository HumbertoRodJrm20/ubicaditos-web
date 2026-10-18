import { afterAll, beforeAll, expect, test } from 'bun:test';
import { By, until } from 'selenium-webdriver';
import {
    buildDriver,
    goto,
    horizontalOverflow,
    login,
    USERS,
    VIEWPORTS,
} from './config.js';

/**
 * Verificación responsive de las vistas clave en móvil (375×667) y
 * escritorio (1366×768): ninguna página debe generar scroll horizontal
 * (se toleran 4 px por redondeos de subpíxel).
 */
const TOLERANCE = 4;

const PAGES = [
    { path: '/passenger/dashboard', role: 'passenger', label: 'Mapa del pasajero' },
    { path: '/passenger/buses', role: 'passenger', label: 'Autobuses del pasajero' },
    { path: '/passenger/routes', role: 'passenger', label: 'Rutas del pasajero' },
    { path: '/driver', role: 'driver', label: 'Panel del conductor' },
    { path: '/driver/statistics', role: 'driver', label: 'Estadísticas del conductor' },
    { path: '/admin', role: 'admin', label: 'Panel de administración' },
    { path: '/admin/emergencies', role: 'admin', label: 'Incidencias (admin)' },
    { path: '/admin/buses', role: 'admin', label: 'Autobuses (admin)' },
    { path: '/admin/routes', role: 'admin', label: 'Rutas (admin)' },
    { path: '/profile/edit', role: 'admin', label: 'Perfil de usuario' },
];

async function runViewportSuite(driver, viewport) {
    let currentRole = null;

    for (const page of PAGES) {
        if (currentRole !== page.role) {
            await login(driver, USERS[page.role]);
            currentRole = page.role;
        }

        await goto(driver, page.path);
        await driver.wait(until.elementLocated(By.css('body')), 15000);
        // Pequeña pausa para que Leaflet/Alpine terminen de componer el layout.
        await driver.sleep(800);

        const overflow = await horizontalOverflow(driver);
        expect(
            overflow,
            `${page.label} (${page.path}) desborda ${overflow}px en ${viewport.width}px de ancho`,
        ).toBeLessThanOrEqual(TOLERANCE);
    }
}

test('las vistas clave no desbordan horizontalmente en móvil (375×667)', async () => {
    const driver = await buildDriver(VIEWPORTS.mobile);
    try {
        await runViewportSuite(driver, VIEWPORTS.mobile);
    } finally {
        await driver.quit();
    }
}, 180000);

test('las vistas clave no desbordan horizontalmente en escritorio (1366×768)', async () => {
    const driver = await buildDriver(VIEWPORTS.desktop);
    try {
        await runViewportSuite(driver, VIEWPORTS.desktop);
    } finally {
        await driver.quit();
    }
}, 180000);
