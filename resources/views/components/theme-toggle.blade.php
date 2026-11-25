@props([
    'label' => true,
])

<button
    type="button"
    data-theme-toggle
    aria-label="Cambiar entre modo claro y oscuro"
    aria-pressed="false"
    title="Cambiar tema"
    {{ $attributes->merge([
        'class' => 'inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200/70 bg-white/80 text-slate-600 shadow-sm transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
    ]) }}
>
    {{-- Luna: visible en modo claro, invita a activar el modo oscuro. --}}
    <svg data-theme-icon="moon" class="h-4 w-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20.4 15.4A9 9 0 0 1 8.6 3.6 9 9 0 1 0 20.4 15.4Z" />
    </svg>

    {{-- Sol: visible en modo oscuro, invita a volver al modo claro. --}}
    <svg data-theme-icon="sun" class="hidden h-4 w-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    </svg>
</button>

<script>
    (function () {
        if (window.UbicaditosTheme) {
            return;
        }

        var root = document.documentElement;

        function storedTheme() {
            try {
                return localStorage.getItem('ubicaditos-theme')
                    || localStorage.getItem('darkMode');
            } catch (error) {
                return null;
            }
        }

        function sync(theme) {
            var isDark = theme === 'dark';
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = theme;

            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                button.setAttribute('title', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            });
        }

        function apply(theme, persist) {
            sync(theme);

            if (persist) {
                try {
                    localStorage.setItem('ubicaditos-theme', theme);
                    // Compatibilidad con la preferencia usada antes de la iteración 7.
                    localStorage.setItem('darkMode', theme === 'dark' ? 'true' : 'false');
                } catch (error) {
                    // La preferencia no se puede persistir; el tema sigue funcionando en esta página.
                }
            }
        }

        var stored = storedTheme();
        var initial = stored === 'dark' || stored === 'true'
            ? 'dark'
            : (stored === 'light' || stored === 'false' ? 'light' : null);

        if (initial) {
            sync(initial);
        } else {
            sync(root.classList.contains('dark') ? 'dark' : 'light');
        }

        window.UbicaditosTheme = {
            current: function () {
                return root.classList.contains('dark') ? 'dark' : 'light';
            },
            set: function (theme) {
                apply(theme === 'dark' ? 'dark' : 'light', true);
            },
            toggle: function () {
                apply(root.classList.contains('dark') ? 'light' : 'dark', true);
            },
        };

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-theme-toggle]');
            if (!button) {
                return;
            }

            event.preventDefault();
            window.UbicaditosTheme.toggle();
        });

        // Si el usuario no eligió un tema manualmente, se respeta el cambio del sistema.
        if (window.matchMedia) {
            var media = window.matchMedia('(prefers-color-scheme: dark)');
            var onChange = function (event) {
                if (!storedTheme()) {
                    apply(event.matches ? 'dark' : 'light', false);
                }
            };

            if (typeof media.addEventListener === 'function') {
                media.addEventListener('change', onChange);
            } else if (typeof media.addListener === 'function') {
                media.addListener(onChange);
            }
        }
    })();
</script>
