/**
 * Popup compartido para las paradas del mapa.
 *
 * Se usa desde las vistas de pasajero, administrador y conductor. El marcado
 * vive aquí para que las tres pantallas tengan exactamente el mismo estilo.
 */

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function safeColor(value) {
    return /^#[0-9a-f]{3,8}$/i.test(String(value ?? '')) ? value : '#4b5a85';
}

const ICONS = {
    pin: '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
    bus: '<rect x="4" y="3" width="16" height="14" rx="3"/><path d="M4 11h16M8 21v-4M16 21v-4"/><circle cx="8.5" cy="14" r=".6"/><circle cx="15.5" cy="14" r=".6"/>',
    route: '<circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="6" r="2.2"/><path d="M8.2 18H14a3 3 0 0 0 0-6h-4a3 3 0 0 1 0-6h5.8"/>',
    star: '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>',
    arrow: '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
};

function icon(name, className = 'ub-stop-popup__svg') {
    return `<svg class="${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name] || ICONS.pin}</svg>`;
}

function routeChips(routes) {
    if (!Array.isArray(routes) || routes.length === 0) {
        return `<span class="ub-stop-popup__chip ub-stop-popup__chip--neutral">${icon('pin')} Parada activa</span>`;
    }

    const visible = routes.slice(0, 4).map((route) => {
        const label = route.code || route.name || 'Ruta';
        return `
            <span class="ub-stop-popup__chip" style="--route-color:${safeColor(route.color)}">
                <span class="ub-stop-popup__chip-dot"></span>
                ${escapeHtml(label)}
            </span>
        `;
    });

    if (routes.length > 4) {
        visible.push(`<span class="ub-stop-popup__chip ub-stop-popup__chip--more">+${routes.length - 4}</span>`);
    }

    return visible.join('');
}

function actionButton(action) {
    if (!action || !action.label) {
        return '';
    }

    const variant = action.variant === 'primary'
        ? 'ub-stop-popup__action--primary'
        : 'ub-stop-popup__action--ghost';
    const active = action.active ? ' is-active' : '';
    const pressed = action.active === undefined ? '' : ` aria-pressed="${action.active ? 'true' : 'false'}"`;
    const onclick = action.onClick ? ` onclick="${escapeHtml(action.onClick)}"` : '';

    return `
        <button type="button" class="ub-stop-popup__action ${variant}${active}"${onclick}${pressed}>
            ${icon(action.icon || 'arrow')}
            <span>${escapeHtml(action.label)}</span>
            ${action.variant === 'primary' ? icon('arrow', 'ub-stop-popup__svg ub-stop-popup__svg--arrow') : ''}
        </button>
    `;
}

/**
 * @param {object} stop
 * @param {object} [options]
 * @param {Array} [options.routes] Rutas que pasan por la parada.
 * @param {boolean} [options.isFavorite]
 * @param {string} [options.eyebrow]
 * @param {Array} [options.actions]
 * @param {string} [options.hint]
 */
export function renderStopPopup(stop, options = {}) {
    const {
        routes = [],
        isFavorite = false,
        eyebrow = 'Parada',
        actions = [],
        hint = '',
    } = options;

    const favoriteChip = isFavorite
        ? `<span class="ub-stop-popup__chip ub-stop-popup__chip--favorite">${icon('heart')} En tus favoritos</span>`
        : '';

    const actionsHtml = actions.map(actionButton).join('');

    return `
        <div class="ub-stop-popup">
            <div class="ub-stop-popup__hero">
                <span class="ub-stop-popup__glow"></span>
                <div class="ub-stop-popup__eyebrow-row">
                    <span class="ub-stop-popup__icon">${icon('pin')}</span>
                    <span class="ub-stop-popup__eyebrow">${escapeHtml(eyebrow)}</span>
                </div>
                <h3 class="ub-stop-popup__title">${escapeHtml(stop.name || 'Parada')}</h3>
                <p class="ub-stop-popup__address">${escapeHtml(stop.address || 'Parada del recorrido')}</p>
            </div>

            <div class="ub-stop-popup__body">
                <div class="ub-stop-popup__chips">
                    ${routeChips(routes)}
                    ${favoriteChip}
                </div>

                ${actionsHtml ? `<div class="ub-stop-popup__actions">${actionsHtml}</div>` : ''}

                ${hint ? `<p class="ub-stop-popup__hint">${escapeHtml(hint)}</p>` : ''}
            </div>
        </div>
    `;
}

if (typeof window !== 'undefined') {
    window.UbicaditosStopPopup = { render: renderStopPopup };
}
