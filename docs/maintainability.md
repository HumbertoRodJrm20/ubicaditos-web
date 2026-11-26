# Mantenibilidad (RNF-11)

Estado del 2026-11-25. La refactorización se hizo por capas, con caracterización previa y suite en verde; no se eliminó código ni datos sin aprobación.

## (b) Validación en Form Requests — completado

Las validaciones duplicadas de los controladores administrativos de usuarios y autobuses se extrajeron a:

| Form Request | Uso |
|--------------|-----|
| `App\Http\Requests\Admin\StoreUserRequest` | `Admin\UserController@store` |
| `App\Http\Requests\Admin\UpdateUserRequest` | `Admin\UserController@update` |
| `App\Http\Requests\Admin\StoreBusRequest` | `Admin\BusController@store` |
| `App\Http\Requests\Admin\UpdateBusRequest` | `Admin\BusController@update` |

Las reglas compartidas viven en `App\Http\Requests\Admin\Concerns\ValidatesUserData` y `ValidatesBusData`, para que alta y edición no vuelvan a divergir. Se conservaron claves de error, mensajes en español y el comportamiento de `required_unless:role,driver` y `required_if:role,driver`. Ya existían Form Requests para rutas, paradas, sincronización, viajes, emergencias, incidencias, calificaciones y preferencias.

**Prueba de caracterización:** `tests/Feature/Admin/AdminValidationRefactorTest.php` verifica por reflexión que los controladores usan los Form Requests y que los errores de validación siguen con las mismas claves. Fase verde: 4 pruebas / 22 aserciones.

## (a) JavaScript en Blade — inventario, sin extracción

Se detectaron **22 bloques `<script>` inline** y 5 etiquetas `<script src=` para Leaflet en 22 vistas. No se movió ningún bloque a `resources/js/` en este paso, porque la mayoría está acoplado a datos Blade y una extracción sin caracterización de navegador cambiaría comportamiento sin evidencia para verificarlo.

| Categoría | Vistas | Riesgo de extracción |
|-----------|--------|----------------------|
| Tema y navegación | `partials/theme-init.blade.php`, `components/theme-toggle.blade.php`, `components/passenger-nav.blade.php` | Medio: comparten estado `localStorage` y clases `dark`. |
| Mapas Leaflet | `passenger/dashboard.blade.php`, `driver/map.blade.php`, `admin/dashboard.blade.php`, `admin/routes/manage.blade.php`, `share/show.blade.php` | Alto: geometría, popups, capas y datos inyectados por Blade; requiere revisión manual. |
| Formularios y controles | `auth/{login,register,forgot-password,reset-password,confirm-password}.blade.php`, `profile/edit.blade.php`, `admin/{drivers,users/create,users/edit,buses/create,buses/edit,routes/create}.blade.php`, `passenger/{routes,buses}.blade.php`, `driver/dashboard.blade.php` | Medio: alternar contraseña, selects dependientes y envíos; cubierto parcialmente por pruebas Blade. |

Los módulos ya extraídos (`resources/js/realtime.js`, `route-layer.js`, `theme.js` cuando exista) deben reutilizarse; no se agregaron frameworks ni dependencias frontend.

## (c) Seeders — inventario, sin borrado

`DatabaseSeeder` llama actualmente a `AdditionalRouteStopsSeeder`, `FrequentRouteStopsSeeder` y `RouteCorridorSeeder`. Los siguientes seeders no son invocados desde `DatabaseSeeder` y quedan **archivados en el repositorio, sin eliminar**:

| Seeder | Observación |
|--------|-------------|
| `OaxacaRoutesSeeder` | Generador histórico de rutas; no invocado por el seeder principal. |
| `ExtendedRoutesSeeder` | Extensión histórica de catálogo; no invocado. |
| `TransportSystemSeeder` | Poblado alternativo; no invocado. |
| `FixRoutesSeeder` | Corrección puntual; no invocado. |
| `UpdateRoutePathsSeeder` | Actualización puntual de trazados; no invocado. |
| `TestDataSeeder` | Datos de prueba; no invocado. |

Antes de eliminar cualquiera de ellos se necesita una prueba de caracterización que demuestre que ningún flujo documentado depende de él y una aprobación explícita. `DatabaseSeeder` sigue siendo no idempotente y la documentación de base de datos conserva la advertencia de ejecutar `routes:generate-paths --force` después de `migrate:fresh --seed`.
