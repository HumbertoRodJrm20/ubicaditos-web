{{-- Aplica el tema antes del primer pintado para evitar el destello de modo claro. --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('ubicaditos-theme')
                || localStorage.getItem('darkMode');

            var prefersDark = window.matchMedia
                && window.matchMedia('(prefers-color-scheme: dark)').matches;

            var isDark = stored === null || stored === ''
                ? prefersDark
                : (stored === 'dark' || stored === 'true');

            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        } catch (error) {
            // Si localStorage no está disponible, se conserva el tema claro.
        }
    })();
</script>
