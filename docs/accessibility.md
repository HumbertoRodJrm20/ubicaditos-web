# Accesibilidad y modo oscuro

Estado del 2026-11-25 para RNF-15 y HU-033. La evidencia automatizada cubre estructura HTML; **no sustituye** una auditoría visual con lector de pantalla, teclado real ni capturas en 375×667 y 1366 px.

## Verificación automática ejecutada

`tests/Feature/Accessibility/KeyPagesAccessibilityTest.php` comprueba en vistas renderizadas:

- `lang="es"` en la página de acceso.
- Etiquetas `for="email"`, `for="password"`, `for="route-filter"` y `for="locale"`.
- `aria-label` en el conmutador de tema, filtros, acercar/alejar, ubicación y compartir ubicación.
- Clases `dark:` presentes en login, mapa del pasajero y perfil.
- Confirmación visible para el cierre de sesión.

Suite enfocada: 3 pruebas / 18 aserciones, 0 fallos. No se ejecutaron Selenium ni navegador.

## Checklist pendiente de auditoría manual

| Ítem | Alcance | Estado |
|------|---------|--------|
| Contraste AA en modo claro y oscuro | Textos, botones, badges y popups de mapas | Sin medir; requiere herramienta de contraste sobre capturas reales. |
| Foco visible y orden de tabulación | Modales, paneles plegables, mapas y formularios | Sin verificar con teclado real. |
| Equivalentes accesibles del mapa | Filtros, zoom y ubicación ya tienen `aria-label`; falta validar el orden y el anuncio de cambios dinámicos | Parcial. |
| Etiquetas e `aria-*` en todos los formularios | Inventario automatizado cubre claves; faltan vistas administrativas y de incidentes | Parcial. |
| Texto alternativo de imágenes e iconos | SVG decorativos con `aria-hidden` en el componente de tema; revisar el resto | Sin auditoría completa. |
| Capturas a 375×667 y 1366 px | Todas las vistas clave | No generadas en este entorno; no se afirma evidencia visual. |

## Cómo continuar

1. Ejecutar la lista de verificación con navegador y registrar hallazgos por vista, modo de color y ancho.
2. Corregir los problemas confirmados con pruebas de regresión cuando sean representables en Blade.
3. Adjuntar capturas fechadas a este documento sólo después de generarlas.
