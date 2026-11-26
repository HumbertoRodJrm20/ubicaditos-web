# AGENTS.md — Ubicaditos · Route & Stop Management Studio

Context for the Junie agent. Read it fully before touching code. It is ordered: identity → stack → architecture → verified domain facts → what exists → known defects → design rules → feature spec → decisions → TDD plan → status → working rules → deployment.

Legend: **[V]** verified against source files; **[D]** taken from `docs/` and not re-verified; **[?]** unknown, inspect before relying on it.

Language: user-facing strings, validation messages and code docblocks are **Spanish**; identifiers and test method names are **English** (`test_snake_case`).

---

## 1. Project identity

Ubicaditos is a public-transport monitoring platform for **Oaxaca, México**. Laravel monolith, MVC, server-rendered Blade. Roles in `users.role`: `admin`, `driver`, `passenger`.

- Passenger: map of routes/stops/buses, nearby routes, likes, favorites, shared live location, incident reports.
- Driver: starts/ends trips, shares GPS, reports emergencies.
- Admin: users, buses, routes, reports, emergencies, incidents.

**Current feature:** an administrative, map-centered **Route & Stop Management Studio** so an admin can manage routes and stops visually instead of through checkbox forms.

Documentation lives in `docs/` (Spanish): `03-arquitectura`, `04-modelos`, `05-base-de-datos`, `06-controladores`, `07-vistas`, `08-rutas`, `09-comandos`, `10-pruebas-y-estado`, `14-diagnostico-y-optimizacion-rutas`, `_devlog`. **When docs and source disagree, source wins; report the discrepancy.**

## 2. Stack [D]

- PHP ^8.2, Laravel 12, Breeze 2.3, Eloquent, Reverb (broadcasting), queues + scheduler.
- DB: SQLite in development and tests (`:memory:` in `phpunit.xml`); PostgreSQL only for production/staging. Do not migrate development to PostgreSQL.
- Frontend: Blade, Tailwind 3 (`darkMode: 'class'`), Alpine.js 3, Axios, Leaflet 1.9.4, OpenStreetMap tiles, OSRM routing, Chart.js, Figtree font (Bunny Fonts).
- Build/quality: Vite 7, **Bun 1.3.5**, PHPUnit 11.5, Mockery, Laravel Pint.
- Livewire is installed but unused. **Do not introduce React, Vue, Inertia, Livewire or any new frontend framework.**

Common commands:

```bash
composer run dev                         # server + queue + Vite
php artisan test                         # full suite
php artisan test --filter=RouteStop      # by name
php artisan route:list --except-vendor
php artisan routes:diagnose
php artisan routes:generate-paths --force            # needs internet (OSRM)
php artisan routes:generate-paths --route=OAX-101 --force
./vendor/bin/pint                        # format PHP
```

## 3. Architecture [D]

- Routes: `routes/web.php`, `routes/auth.php`. Middleware aliases `admin`, `driver`, `passenger` (403 on wrong role, guests redirect to `login`).
- Controllers by role under `app/Http/Controllers/{Admin,Driver,Passenger,Auth}`. Validation is mostly inline `$request->validate()`; Form Requests also cover route and stop CRUD/synchronization. Flash messages via `->with('success'|'error', ...)`.
- Admin group **[V]**: `Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')`. `Route::resource('buses'|'routes'|'stops'|'users')` are registered with **no `except`**.
- `/api/*` JSON endpoints are `auth` only (any role). **Studio JSON endpoints must live inside the admin group, never under `/api`.**
- Real-time: `Bus::updateLocation()` dispatches `BusLocationUpdated` on private channel `buses` (Reverb); `resources/js/realtime.js` falls back to polling `GET /api/buses/active`.
- `docs/03-arquitectura.md` still says "no WebSockets": stale. Reverb is real.

## 4. Domain model — verified facts

### routes **[V]**
`id, name (string, NOT unique in DB), code (unique), description?, color string(7) default #3B82F6, estimated_duration int?, path_coordinates json? (array of {lat,lng}), is_active bool default true, timestamps`.
`Route` model: fillable = name, code, description, color, estimated_duration, path_coordinates, is_active. No `HasFactory`.
Relations: `buses()` (bus_route, pivot assigned_at), `stops()` (route_stop, `withPivot('order','estimated_time_from_previous','is_frequent')`, ordered by `route_stop.order` then `route_stop.id`, `withTimestamps()`), `trips()`, `likes()` (morph), `favoritedByUsers()`.
`getOrderedStops(bool $includeFrequent = true)` orders by `route_stop.order`, then `route_stop.id`; `false` filters `is_frequent = false`.

### stops **[V]**
`id, name, address?, latitude decimal(10,7), longitude decimal(10,7), description?, is_active bool default true, timestamps`. **No unique constraint on coordinates.** `Stop` casts lat/lng as `decimal:7` (strings!); `$stop->coordinates` returns `['lat'=>float,'lng'=>float]`. Relations: `routes()`, `favoritedByUsers()`, `likes()`. No `HasFactory`.

### route_stop **[V]**
`id, route_id FK cascade, stop_id FK cascade, order int default 0, estimated_time_from_previous int?, is_frequent bool default false, timestamps`; unique `(route_id, stop_id)` added by `2026_11_25_000001_add_unique_route_stop_pair_index` after duplicate preflight.
- `is_frequent` is **per route–stop pair**, not per stop: 36 stops are main on one route and frequent on another.
- `order` is **ONE combined sequence per route** that interleaves main and frequent stops. Verified on all 18 routes: first frequent stop at order 1–2, last main at 76–180.
- Main stops define the route and are the only OSRM waypoints. Frequent stops are secondary stops sampled every ~500 m on the stored geometry (static seed data in `database/data/*.json`, **not** recomputed when geometry changes).
- For main stops `estimated_time_from_previous` = minutes from the previous **main** stop (seeder heuristic: Haversine km × 4, first = 0). Frequent stops keep their own seed values.

### Dev DB counts (historical snapshot, may drift)
819 stops. 346 main pivots over 113 distinct stops; 1,866 frequent pivots over 742 distinct stops. 18 routes `OAX-101`…`OAX-118`: 18–21 main + 58–162 frequent each. Frequent stops are shared across routes (~2.5 routes each).

### trips **[V]**
`route_id`, `bus_id` and `driver_id` foreign keys still specify **cascade**; controllers therefore block active use and deactivate route/bus/driver records with history instead of hard-deleting them. `likes` are polymorphic with no deletion hook (orphans possible) **[?]**.

## 5. What already exists

### Routes admin — `Admin\RouteController` **[V]**
`index` (search, paginate 15, withCount buses/stops), `create`, `store`, `edit`, `update`, `destroy` (no `show`). The `admin/routes/create|edit.blade.php` forms use `<x-app-layout>` and are metadata-only fallbacks; they no longer query or render the stop catalog and link to the Route Studio for stop assignment/order. `store/update` still pass submitted `stops[]` through `RouteStopSyncService::syncMainStops()` for compatibility.
`StoreRouteRequest` and `UpdateRouteRequest` require a unique route name (update ignores the current route), unique code, and a valid six-digit hex color; estimated duration is nullable integer 40–90; `is_active` is boolean.

### Stops admin — `Admin\StopController` **[V]**
CRUD, coordinate validation through Form Requests, status toggle, JSON stop catalog, and safe deletion are implemented. The resource's read actions redirect to the Route Studio. Stops attached to a route or referenced by favorites/likes are deactivated instead of deleted. `GET /admin/stops/data` supports bounded search/pagination; the Route Studio loads selected-route frequent stops through its dedicated admin JSON endpoint.

### Geometry **[V]**
`php artisan routes:generate-paths` (`App\Console\Commands\GenerateRoutePaths`) is a thin wrapper around `App\Services\RouteGeometryService`. The service uses main stops only, skips routes with fewer than two main stops and routes with an existing valid path unless forced, reads the OSRM URL/timeout from `config/services.php`, and preserves the previous geometry on any invalid response. `tests/Feature/Services/RouteGeometryServiceTest.php` covers the service with `Http::fake()`; the Artisan wrapper itself has no direct test. The command supports `--sleep` between requests (default 1 s).
`routes:diagnose` reports missing paths and shared segments.
`resources/js/route-layer.js` → `window.UbicaditosRouteLayer` **[D]**: dedupes shared segments (one polyline per unique stop-pair segment; shared ones grey), caches lines per route id, `showNetwork`, `showOnlyRoute`, `showAllRoutes`, `fitRoute`. Historical optimization: 18 routes, 257 → 90 unique segments, 25,292 stored points, 0 OSRM calls on map load. **Reuse; do not redraw routes one by one.**

### Seeders **[V/D]**
`RouteCorridorSeeder` (main corridors, resets everything else to frequent, appends them after the mains), `AdditionalRouteStopsSeeder` / `FrequentRouteStopsSeeder` (splice frequent stops into the combined order using `database/data/*.json`, dedupe stops by exact lat/lng). `DatabaseSeeder` is not idempotent; after `migrate:fresh --seed` run `routes:generate-paths --force`.

### Maps and layouts
- Maps: `passenger/dashboard.blade.php`, `driver/map.blade.php`, `admin/dashboard.blade.php` (live monitoring; **do not interfere with it**). Much JS is inline.
- Admin route forms use `<x-app-layout>`; admin dashboard/reports and the Route Studio use `<x-passenger-layout>` **[V]**.
- Components to reuse: `x-input-label`, `x-text-input`, `x-input-error`, `x-primary-button`, `x-secondary-button`, `x-danger-button`, `x-dropdown`, `x-modal`, `x-passenger-nav`. Assets: `public/images/{autobus,autobusfblanco,bus-stop-logo,parada-de-autobus}.svg`. Do not invent branding.

## 6. Known defects (do not silently "fix" outside the plan)

1. **Legacy route forms are metadata-only fallback pages.** Create/edit no longer query or render the stop catalog; both link to the Route Studio for stop assignment and ordering. The forms still accept submitted `stops[]` for compatibility, and omitting it on update preserves assignments. Covered by `test_legacy_route_forms_do_not_load_or_render_the_stop_catalog`.
2. `update` without `stops` leaves assignments untouched, so a route cannot be emptied (pinned).
3. Route deletion is guarded: routes with trips, bus assignments, likes, or favorites are deactivated; only routes without those references are hard-deleted. Covered by `test_destroy_deactivates_a_route_with_trips`.
4. Route color is validated as a six-digit hex value in both Form Requests; covered by `test_color_must_be_a_valid_hex_value`.
5. Route name uniqueness is enforced on update by `UpdateRouteRequest`; the database column itself is still not unique.
6. Seeders write `estimated_duration` outside the 40–90 form limit **[?]**: such routes would fail validation on unrelated edits. Verify with a query before relying on it.
7. `ReportController` uses SQLite `strftime()`; breaks on PostgreSQL (`EXTRACT(HOUR ...)` needed).
8. The `GenerateRoutePaths` Artisan wrapper has no direct test; the extracted geometry service and its OSRM failure handling are covered.
9. `getOrderedStops()` orders by `route_stop.order` and then `route_stop.id` for deterministic ties.
10. `/api/*` open to any authenticated role.
11. `users.work_shift` enum has only `morning`/`afternoon`, but `DriverController@startTrip` also handles `night` **[D]**. More pre-existing issues are listed in `docs/10-pruebas-y-estado.md`.

## 7. Design system

The brief describes tokens `primary #06b6d4`, `accent #a855f7`, `success #22c55e`, `warning #f59e0b`, `danger #ef4444`. **The admin Blade views actually render a slate/indigo glass style [V]**; verify `tailwind.config.js` before using the named tokens. Follow what existing admin pages render:

- Panel: `rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75`
- Inputs: `rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm ... focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20`
- Heading `text-[#273252] dark:text-gray-100`; muted `text-slate-500 dark:text-gray-400`; primary button `bg-gradient-to-b from-[#7b8db8] to-[#53658f]` pill.
- Success `emerald`, error `rose`, warning amber. Page background gradient `from-[#eef2f9] via-[#e8edf7] to-[#f7f9fc]` (dark `slate-900/800`).
- Pill buttons, rounded cards, soft shadows, minimal noise; **the map is the main visual**.
- Selected route in its own color; other routes muted, low opacity; stops as compact circular markers; selected stop clear cyan highlight. Route colors are independent from UI palette.
- **Dark mode** (`localStorage['darkMode']`, `dark` class on `<html>`) must work in everything new.
- Responsive: usable at **375×667** and comfortable at **1366 px**; no horizontal overflow; sidebar becomes drawer/collapsible; touch targets ≥ 44 px. Desktop is the primary editing environment.

## 8. Feature spec — Route & Stop Studio

Dedicated page, **not** the live dashboard. Two panels: sidebar (route list/search, selected-route form, ordered stops, add stop) + large Leaflet map, with a bottom status/validation/save bar.

**Route ops:** create, edit metadata, change color, activate/deactivate, safe delete (deactivate when history exists), select, assign existing stops, remove stops, reorder, preview geometry, regenerate geometry.
**Stop ops:** create, edit name/address/description/coordinates, activate/deactivate, safe delete, assign to several routes. Validate server-side: latitude −90…90, longitude −180…180.
**Map flow:** open → load network → select route → geometry highlighted + ordered stops in sidebar → click map to propose a stop → drag marker → save stop → assign to route → reorder (up/down controls first; drag-and-drop only if no heavy dependency) → preview road-following geometry → **explicit save**.
Never persist marker moves automatically; clearly separate unsaved frontend state from persisted data. Show `estimated_time_from_previous`.

**Performance (hard constraints):** eager-load, no N+1; load only needed geometry; reuse stored `path_coordinates`; dedupe shared segments; few Leaflet layers; **never call OSRM on page load or editor open**. Default map layer = main stops only (113 in dev); load a selected route's frequent stops (~100) on demand, never all 819 as DOM markers.
Regenerate geometry only on explicit request or when the main sequence/coordinates change. Keep previous geometry on OSRM failure.

## 9. Decisions made

- **URL/name/controller:** `GET /admin/routes/manage`, name `admin.routes.manage`, `Admin\RouteManagementController@index`, view `admin.routes.manage`. Declare it **before** `Route::resource('routes')` or the resource's `routes/{route}` captures `manage`.
- **Frequent stops: "keep, anchored".** When main stops change, frequent stops stay attached, anchored after the main stop that precedes them (blocks move with their anchor; if the anchor is removed they go to the nearest earlier surviving main, or to the front). Nothing is auto-deleted. Route is flagged "frequent stops need review". Implemented as `App\Services\RouteStopSyncService` (see §11).
- Studio edits **main** stops only; frequent stops are displayed/managed separately and are per route–stop.
- **Geometry extraction delivered:** `RouteGeometryService` owns generation; `routes:generate-paths` is a thin wrapper and the OSRM URL/timeout are configurable.
- Keep the old `RouteController` forms as metadata-only fallbacks; stop assignment and ordering belong in the bounded Studio. The legacy actions still accept `stops[]` through `RouteStopSyncService` for compatibility.

### Pending decisions (ask the user before creating migrations)
1. Unique index `route_stop(route_id, stop_id)`: **approved 2026-11-25** and added with duplicate preflight; local development DB was not migrated.
2. Persist the "needs review" flag (e.g. `routes.frequent_review_needed` boolean).
3. Geometry staleness: `routes.path_stops_hash` + `path_generated_at`.
4. Verify whether seeded `estimated_duration` values fall outside the form's 40–90 range; route colors currently require six-digit hex values.

## 10. Security rules

All admin endpoints inside `auth` + `admin`; never trust frontend role checks; CSRF on every write; Form Requests for validation; validate coordinates and stop/route existence server-side; protect against mass assignment (explicit `$fillable`, validated input only); **transactions for any multi-row route–stop change**; correct HTTP codes (401/302 guest, 403 wrong role, 404, 422). Never expose private data in public endpoints. No secrets in the repo.

## 11. TDD plan and status

Red → Green → Refactor. Tests first. External services (OSRM) are always faked (`Http::fake()`); the suite must pass offline.

Phases: **A** authorization · **B** routes · **C** stops · **D** route–stop relations · **E** geometry · **F** frontend (manual checklist; never claim browser tests ran unless they did).

| Item | File | Status |
|---|---|---|
| Phase A authorization (guest 302 → login, driver/passenger 403, admin OK, `manage` not captured by resource) | `tests/Feature/Admin/RouteStudioAuthorizationTest.php`, `tests/Feature/Admin/StopCatalogTest.php`, `web.php` routes | Delivered; full suite passes |
| Characterization of `RouteController` fallback and route-stop ordering | `tests/Feature/Admin/RouteCrudCharacterizationTest.php` | Passing; forms do not query the stop catalog and the selected-route endpoint preserves combined order |
| Sequence planning + sync (anchored frequent stops, minutes keep/recompute/override, shared stops untouched, atomic on unknown id, review flag, promotion without duplicates) | `tests/Unit/Services/RouteStopSequencePlanTest.php`, `tests/Feature/Admin/RouteStopSyncServiceTest.php`, `app/Services/RouteStopSyncService.php` | Delivered; a helper-name fatal (`seed()` clashed with `TestCase::seed`) was fixed by renaming to `attachSequence`. **Run `php artisan test --filter=RouteStop`; expect 15 passing** |
| Full-suite verification | `php artisan test` | **Passed 2026-11-25: 250 tests / 1,038 assertions** |
| Stop CRUD tests + `StopController` + Form Requests | `tests/Feature/Admin/StopManagementTest.php` | Delivered; coordinate validation, authorization, status and safe delete covered |
| Safe delete rules (deactivate vs delete) | `RouteController`, `StopController`, `BusController`, `UserController`, `DriverDeactivationService`, lifecycle tests | Routes, stops, buses and drivers preserve history; active bus/driver use blocks deactivation |
| `RouteGeometryService` extraction + `Http::fake()` tests | `tests/Feature/Services/RouteGeometryServiceTest.php` | Delivered; network calls are faked and existing geometry is preserved on failure |
| Studio JSON endpoints and bounded stop catalog | `tests/Feature/Admin/StopCatalogTest.php`, `RouteStudioManagementTest.php`, `RouteCrudCharacterizationTest.php` | Studio uses main stops initially, selected-route frequent stops on demand, and stop search/pagination; legacy forms no longer query the catalog. RNF-05 remains partial pending query evidence for other main lists |
| Regression (existing maps, route listing, driver trip validation) + responsive | Feature suite; manual browser checklist | Automated suite passed; responsive/browser review remains pending |

`RouteStopSyncService` public API:

```php
syncMainStops(Route $route, array $mainStopIds, array $minutes = []): array
// ['added'=>int[], 'removed'=>int[], 'main_sequence_changed'=>bool, 'frequent_review_needed'=>bool]
// DB::transaction; throws InvalidArgumentException on duplicate or unknown ids (before any write).
planSequence(array $rows, array $mainStopIds): array   // pure; rows = [['stop_id'=>int,'is_frequent'=>bool], ...]
```

Phase C targets: create/update stop, invalid lat/lng rejected (422), activate/deactivate, safe delete, shared stops stay on other routes, warn (advisory) on near-duplicate coordinates, no hard delete when the stop is on any route or has favorites/likes history. CRUD, coordinate validation and safe-delete rules are covered; advisory near-duplicate warnings remain pending.

Test conventions **[V]** (from `BusShowTest`): namespace `Tests\Feature\Admin`, `use RefreshDatabase`, `User::factory()->create(['role' => 'admin'])` (only `UserFactory` exists), models built with `::create()`, loops over roles for 403 checks, guest → `assertRedirect(route('login'))`, Spanish class docblock. **Do not name test helpers after `TestCase` methods** (`seed`, `call`, `get`, `post`, `put`, `delete`, `json`, `be`, `actingAs`, `artisan`, `travel`, ...): PHP fatals on signature clash.

## 12. Milestones

0. Reconnaissance — **done** (see §5).
1. Backend tests + CRUD (stops, sync service, safe delete, route Form Requests) — **in progress**.
2. Map editor layout (Blade, glass style, dark mode, drawer sidebar).
3. Interactive map (Leaflet + Alpine; unsaved state only in the frontend).
4. Geometry integration (`RouteGeometryService`, preview, explicit regenerate, preserve on failure) — backend delivered; manual frontend review remains.
5. Regression + responsive pass.
6. Production deployment — **do not start until the feature works locally.**

## 13. Working rules for the agent

- You *can* read and run things, but still: inspect the real source before assuming; do not invent existing code; prefer small, independent changes; one milestone slice at a time.
- Order of work per change: understand → propose → write the failing test → implement → run `php artisan test` (targeted, then full) → `./vendor/bin/pint` on touched files → report results honestly.
- Preserve existing naming and conventions. Do not change unrelated functionality. No placeholder code. Include imports. Add migrations only when necessary, **and ask the user first**.
- Never delete route/stop data in code paths without the safe-delete rules; never hard-delete anything with trip history.
- Do not regenerate geometry or call OSRM from tests, page loads or seeders you didn't intend to touch.
- Never run destructive commands (`migrate:fresh`, `db:wipe`, force pushes) on the user's dev DB without asking.
- When something in this file contradicts the code, trust the code, fix this file, and tell the user.
- The user wants to understand the engineering decisions: explain the "why" briefly with each change.

## 14. Production context (Milestone 6 only)

IONOS Linux VPS + Docker Compose: Nginx, PHP-FPM (8.4 subject to dependency compatibility), Laravel 12, PostgreSQL, Reverb (WSS behind Nginx), queue worker, scheduler, persistent volumes, HTTPS reverse proxy (needed for browser geolocation), automated backups. Multi-stage builds, Bun 1.3.5 for assets, non-root runtime, secrets outside the image, never `php artisan serve`, PostgreSQL and internal ports private. The public OSRM demo server is not production infrastructure: document usage limits and alternatives (own OSRM instance). Do not invent VPS prices or claim a plan is sufficient without checking specs. Fix the SQLite-only `strftime()` before moving to PostgreSQL.
