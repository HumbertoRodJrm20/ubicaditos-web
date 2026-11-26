# Ubicaditos — Open Requirements Backlog (Partial, Pending, No Evidence)

> Audience: Junie (autonomous agent). Goal: verify each item against the real source, then complete it by **TDD** (Red → Green → Refactor) and **document** it.
> Source of truth: the code. If this file disagrees with the repo, the repo wins; report the discrepancy.
> Assessment date: 2026-11-25. Verdicts come from docs and shared files; the full suite baseline was **not** run by the assessor.

## 0. Scope and exclusions

- **Excluded: HU-003** (passenger login with JWT). This is a monolith with session auth (Breeze). Do **not** add JWT, Sanctum tokens or token-based login anywhere. Record this as an accepted deviation in `docs/requirements-deviations.md`.
- Items marked **Decision needed** have an ambiguity or conflict with the current implementation. Do not guess: implement the recommended default only if no answer is available, state the assumption in the PR/notes, and document it.
- Counts: 4 RF + 10 RNF + 4 HU (master list 4.5) + 28 HU (detailed list 4.6) = **46 items**. Several overlap (see section 6), so the real work is fewer features.

## 1. Rules for every item (mandatory)

1. **Verify first.** Read the cited files. Write a *characterization test* of the current behavior before changing anything. Confirm the "Current state" column; if wrong, update it.
2. **Red first.** Write failing tests that encode the acceptance criteria. Run them and confirm they fail for the right reason. Never write production code before a failing test.
3. **Green, then refactor.** Minimal code to pass; refactor with the suite green.
4. **Run the full suite** (`php artisan test`) before and after. No regressions. Format with `vendor/bin/pint`.
5. **Migrations need owner approval** (marked 🗄️). Propose the migration in the notes and wait. All migrations must work on **SQLite (tests, `:memory:`) and PostgreSQL (production)**.
6. **Never name test helpers after `TestCase` methods** (`seed`, `call`, `get`, `post`, …). It causes a fatal error.
7. **Stack constraints:** Laravel 12, PHP ^8.2, Blade, Tailwind 3 (`darkMode: 'class'`), Alpine, Leaflet 1.9.4, Reverb, PHPUnit 11.5. **No new frontend framework.** Reuse existing components and the slate/indigo glass style (`rounded-[24px]`, `backdrop-blur-xl`). Dark mode, 375px and 1366px must work.
8. **Authorization:** role middleware aliases `admin`, `driver`, `passenger`. Every new endpoint needs tests for guest (redirect/401), wrong role (403) and right role (200). Admin JSON endpoints live under `/admin/...`, **not** `/api`.
9. **Privacy:** public/shared-location responses must never expose name, email or user id (RNF-03/04).
10. **Document** each completed item: update `docs/` (behavior, endpoints, schema changes, how to test), add a line to `docs/requirements-status.md` (ID, status, test file(s)), and add a short changelog entry. Use the test names as the traceability link.
11. **Do not call OSRM on page load.** Preserve existing geometry on OSRM failure.
12. Prefer **deactivation over deletion** for anything with history (trips, ratings, reports).

**Status legend:** `Partial` = works but misses criteria · `Pending` = not implemented · `No evidence` = implemented or scripted but never verified/measured.
**Suggested test paths** are proposals; follow the repo's existing naming.

---

## 2. Functional requirements (RF)

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| RF-10 | Driver starts a trip | Partial | Trip start uses the driver's **assigned bus** and the **first route** of that bus. The driver cannot choose the unit or the route. | Driver may choose the **bus** (only buses assigned to or available for them) and a **route** linked to that bus. Defaults to the assigned bus/first route when omitted (backward compatible). Reject: route not linked to bus (422), bus already in an active trip (422), driver already has an active trip (422). Trip stores bus, route, driver, start time, status. | Tests `tests/Feature/Driver/StartTripSelectionTest.php`: default behavior unchanged; chosen route persisted; foreign route rejected; busy bus rejected; second active trip rejected; passenger/admin get 403. Keep existing driver tests green. Docs: driver trip flow + request contract. |
| RF-12 | Trip data recorded | Partial | Stores bus, route, driver, start, end, status. Unconfirmed whether the route actually traveled (`path_taken`) and `distance_km` are written. | Ending a trip persists final occupancy and end time; **distance_km** computed from recorded positions (Haversine sum) and the **path** stored if the columns exist. If columns are missing → propose 🗄️ migration first. | Step 1: characterization test of the trips columns/fillables. Step 2 tests: `EndTripComputesDistanceTest` (2–3 recorded positions → expected km within tolerance; no positions → 0/null, no crash). Reuse the Haversine helper already used by `RouteStopSyncService` if reusable. Docs: trip lifecycle and which fields are authoritative for driver stats (RF-24). |
| RF-19 | Emergency report | Partial | Only the **driver** can create emergency reports. Passengers can only send incidents (no bus, no coordinates). | Passenger (and driver) can submit an **emergency** with optional `bus_id`, `lat`, `lng`, description. Admin sees emergencies first/highlighted and can change status (existing flow). Validation: coordinates in range, bus exists, description required. Rate-limit to prevent spam (e.g. 5/hour; confirm value). | Tests `tests/Feature/Emergency/PassengerEmergencyTest.php`: passenger creates with and without bus/coords; invalid lat/lng rejected; guest redirected; admin list shows emergencies ordered first; status change sets resolution date; rate-limit returns 429. 🗄️ migration only if the table lacks `bus_id`/`lat`/`lng`. Shares work with **HU-17**. |
| RF-21 | Messaging between users | Pending | `Message` model and table exist. **No controllers, routes or views.** | Conversation list, thread view, send message, unread count, mark as read. Only the recipient can mark as read. A user can never read others' messages. Allowed sender/recipient pairs: **Decision needed** — default: passenger↔driver and any user↔admin. | Tests `tests/Feature/Messaging/MessageTest.php`: send (valid/invalid), cannot message self, cannot read foreign thread (403/404), mark-read only by recipient, unread badge count, guest redirected, XSS-escaped body. Optional realtime via Reverb **after** the HTTP flow is green. Shares work with **HU-19, HU-20**. Docs: pairing rules, routes, schema. |

---

## 3. Non-functional requirements (RNF)

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| RNF-02 | Server-side authorization | Partial | Middleware guards every protected endpoint. Gaps: ratings do not require a trip with that driver; bus assignment does not check the user is a driver; `/api` is reachable by any authenticated role. | (a) Rating a driver requires a **completed trip** with that driver. (b) Assigning a driver to a bus rejects non-driver users. (c) Each `/api` endpoint declares and enforces its role(s). Use Policies/Gates or Form Request `authorize()`. | Write a **role × endpoint matrix test** (data provider) for all `/api` routes: guest, passenger, driver, admin. Then tests for (a) and (b). Update the existing rating tests deliberately (they assume no trip is needed: document the behavior change). Docs: authorization matrix table. |
| RNF-05 | Performance and pagination | Partial | Admin lists paginate. Maps and the route form load everything, including all **819 stops**. | No page loads all stops. Provide a searchable/paginated or bounding-box stops endpoint (admin JSON under `/admin`). Map pages cap results. Budget: no N+1 on the main lists. | Tests: endpoint returns ≤ N items and supports `q`, `page`; query-count assertions (`DB::enableQueryLog`) on route form, bus list, dashboard. Coordinate with Route Studio milestones. Docs: limits and params. |
| RNF-06 | Real-time latency | No evidence | Reverb broadcast + 5 s polling fallback are implemented. Latency **never measured**; k6 script exists but is not run. | A measured p95 latency (location update → client event) documented against the threshold in requirements section 4.2 (**confirm the value**). | Not PHPUnit-measurable. TDD part: tests that a location update dispatches the broadcast event and that the polling endpoint returns the latest position. Then run the k6/script and record results in `docs/performance.md` (date, env, p50/p95, tool version). State clearly whether it ran locally or in production-like infra. |
| RNF-08 | GPS accuracy and timestamp | Partial | Shared-location links store accuracy; **bus positions only store a timestamp**. | Bus position updates also store **accuracy (meters)** and expose it in the position payload. Optional; null when the client doesn't send it. | 🗄️ migration (nullable column). Tests: accepts and stores accuracy; rejects negative/non-numeric; payload includes `accuracy`; old clients without the field still work. Docs: payload contract. |
| RNF-10 | Browser compatibility | No evidence | Selenium scripts written, **never executed**. | Run on the browsers named in the requirements doc (confirm list) and record pass/fail per browser and viewport (375px, 1366px). Fix defects found, each with a test where possible. | Not a PHPUnit task. Execute scripts, store output summary in `docs/browser-compat.md` (browser, version, date, result). For each bug: failing feature test (if server-side) or a documented manual case. |
| RNF-11 | Maintainability | Partial | Code is layered and documented, but has inline JavaScript in Blade, duplicated validation and obsolete seeders. | (a) Move inline JS to `resources/js/` modules built by Vite. (b) Replace duplicated controller validation with **Form Requests**. (c) Remove or archive obsolete seeders. No behavior change. | Refactor only under green tests. Before each refactor add a characterization test. Do (b) first (cheapest), then (a). Docs: update architecture notes and seeder inventory. |
| RNF-12 | Referential integrity | Partial | Foreign keys and uniques exist. Missing: unique `route_stop(route_id, stop_id)`; `trips.route_id` cascades, so deleting a route deletes its history. | (a) Unique `(route_id, stop_id)`. (b) Deleting a route/bus/driver with trips is **blocked or turned into deactivation/soft delete**; history is preserved. | 🗄️ **Ask before migrating.** First run a duplicate-detection query on dev data (a stop may only appear once per route). Tests: duplicate attach fails; delete with trips does not remove trips. Overlaps **HU-038, HU-043, HU-047**. Docs: schema + deletion policy. |
| RNF-13 | Backup and restore | Pending | No procedure or automation. Planned for the production deployment. | Scheduled DB backup (SQLite dev / PostgreSQL prod), retention policy, and a **documented, tested restore**. | Tests (dev scope): artisan backup command creates a file in `Storage::fake()` and prunes by retention; fails loudly on error. Package choice (e.g. a maintained Laravel backup package vs custom command) → **Decision needed**. Docs: `docs/runbooks/backup-restore.md` with exact commands. Production Docker/IONOS wiring comes **after** the local feature works. |
| RNF-14 | Scalability | No evidence | k6 script is read-only and was never run. | Run the load test, record throughput/errors at stated concurrency, identify bottlenecks. Fix N+1/slow queries found. | Tests: query-count guards on hot endpoints (map data, bus list). Record results in `docs/performance.md` with the load profile used. |
| RNF-15 | Accessibility | Partial | Responsive layout and labels exist; no contrast or keyboard audit. | WCAG 2.1 AA targets: visible focus, keyboard-operable controls (modals, map controls alternatives), form labels, `alt`/`aria-*`, color contrast in light **and** dark. | Feature tests that rendered key pages contain `lang`, labelled inputs, `aria-label` on icon buttons and the dark-mode toggle. Manual audit checklist stored in `docs/accessibility.md` with findings and fixes. |

---

## 4. User stories — master list (4.5)

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| HU-20 | Mark a message as read | Pending | Depends on messaging. | Recipient marks one/all messages as read; unread count updates. | Implement with RF-21 (tests: recipient only, idempotent). |
| HU-30 | Estimated arrival time | Partial | ETA query per stop exists in the passenger map. Uncertainty warning not confirmed. | ETA response includes an `estimated`/`uncertain` flag; the UI shows an "approximate" notice when the bus position is stale (> 5 min, `STALENESS_THRESHOLD_MS`) or no active bus serves the stop; never show a precise-looking time in that case. | Tests: ETA endpoint with fresh/stale/no bus; payload flag; view contains the notice string. Add feature coverage (RF-30 currently has **no automated tests**). Docs: ETA formula and assumptions. |

---

## 5. User stories — detailed list (4.6) (HU-003 excluded)

> Wording in 4.6 comes from an earlier prototype (JWT, community, license approval). Judge by intent; where a story conflicts with the monolith design, document the deviation instead of forcing it.

### 5.1 Authentication and accounts

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| HU-001 | User type selection | Pending | One login form. Role comes from the account. | Landing/login offers three entry points (passenger, driver, admin). Selection only changes copy/redirect hint; **role is always enforced from the account**, never from the choice. **Decision needed:** implement the entry points or accept the single login as a documented deviation (recommended: entry points that link to the single login with a hint). | Tests: welcome page shows the three entries; selecting a profile cannot elevate privileges; user with the wrong role is redirected to their own dashboard. |
| HU-002 | Passenger registration | Partial | Registration with validation and unique email. **No verification email.** | Implement `MustVerifyEmail` for passengers, `verified` gate on passenger routes, resend link, throttle. Seeders/factories must create verified users so existing tests stay green. **Decision needed:** enforce verification for drivers/admins (default: no). | Tests with `Notification::fake()`: registration sends verification; unverified user is redirected to the notice; verified link grants access; resend throttled; existing suite still green. Docs: mail config required in production. |
| HU-005 | Driver registration with license | Pending | Only an admin can create drivers. No license number, photo or request. | Public driver sign-up: personal data, **license number**, **license photo** (image ≤ 2 MB, jpg/png), account created as **pending** (cannot operate). Admin approval in HU-040. | 🗄️ migration (license fields, `status`: pending/approved/rejected). Tests: valid request creates pending driver; invalid file type/size rejected; duplicate license/email rejected; files stored privately (`Storage::fake`, not publicly reachable). Docs: schema, storage disk. |
| HU-006 | Approved driver login | Partial | Driver logs in and sees the assigned unit. No pending state exists. | Pending/rejected drivers cannot reach driver features and see a clear message; approved drivers behave as today. Depends on HU-005. | Tests: pending → blocked with message; rejected → blocked; approved → dashboard; existing drivers (backfill as approved) unaffected. |
| HU-007 | Admin login | Partial | Role-based access. No profile selector or second factor. | Admin logs in and lands on the admin dashboard; non-admins get 403 on `/admin`. **Decision needed:** 2FA and selector are likely out of scope for the monolith → document as accepted deviation unless the owner asks for TOTP. | Tests: admin redirect after login; passenger/driver 403 on admin routes (already covered by Phase A authorization tests). If 2FA is requested, specify TOTP and write the tests first. |

### 5.2 Passenger map and discovery

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| HU-009 | Real-time buses with ETA | Partial | Real-time positions and route filter work. ETA is queried per stop, not shown per bus. | Each bus marker/popup shows an ETA to the selected (or nearest) stop, with the same staleness handling as **HU-30**. | Tests: endpoint returns ETA per active bus for a given stop; stale buses flagged; view contains the ETA container. No OSRM calls on load. |
| HU-012 | Complete bus details | Partial | Shows driver and rating. No "users sharing location" count and no **Notify me** button. | Add the count of users sharing location on that bus (aggregate only, no identities). **Notify me:** subscribe to "bus X is near stop Y/arrived"; **Decision needed** on channel (in-app only recommended; no external push now). | Tests: count excludes expired/revoked shares; payload has no user data; subscribe/unsubscribe idempotent; only own subscriptions. 🗄️ migration for subscriptions if built. Docs: privacy notes. |
| HU-014 | Automatic driver GPS | Partial | Continuous send and Reverb broadcast. No background execution and no 5-minute validity. | Server marks a position **fresh** for 5 min (`Bus::isLocationFresh()` or equivalent) and hides/flags older ones. Client: keep the screen awake with the Wake Lock API where supported and show a clear warning that the tab must stay open (true background GPS is a browser limitation — document it). | Tests: freshness boundary (4:59 fresh, 5:01 stale); stale buses flagged in the payload; unauthenticated/foreign-bus updates rejected. Docs: browser limitations. |


### 5.4 Incident reports and favorites

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| HU-024 | Report comments | Partial | Description is **required**, max **1000**. The story asks for optional, max 500. | **Decision needed.** Recommended: keep current validation and document the deviation (the master list 4.5 requires a validated description). | If changed: update validation in a Form Request and tests for both boundaries. Otherwise: record the deviation in `docs/requirements-deviations.md`. |
| HU-025 | Submit incident report | Partial | Saves category and description. No route, bus, coordinates or public visibility. | Optional `route_id`, `bus_id`, `lat`, `lng`, and `is_public`; validated and stored; admin list shows them. Public visibility must exclude reporter identity. | 🗄️ migration. Tests: each optional field valid/invalid; foreign ids rejected; public view hides the reporter. Shares schema work with **RF-19**. |
| HU-027 | Favorite routes | Partial | Add/remove works. No maximum of 5 and no ordering/label. | Max **5** favorites per user (6th rejected with a clear message); user can order them and optionally label them. | 🗄️ migration (`position`, `label`). Tests: 5 ok / 6th rejected; reorder persists; removal compacts order; per-user isolation. |
| HU-030 | Settings | Partial | Profile settings only. No notification or language options. | Language selector (es/en) persisted per user using Laravel localization; notification preferences (in-app) stored. **Decision needed:** scope of translations (default: UI chrome only). | 🗄️ migration (`locale`, preferences). Tests: locale persisted and applied by middleware; invalid locale rejected; preferences saved. |
| HU-031 | Logout | Partial | Secure logout. No confirmation; a driver's active trip is not ended. | Confirmation step (modal) and, for a driver with an active trip, either end the trip and stop GPS automatically or warn first (recommended: confirm then end). Revoke active location links (already done). | Tests: logout with active trip → trip ended/bus freed (or blocked with warning, per decision); passenger logout unaffected; session invalidated. |
| HU-033 | Consistent dark mode | Partial | Applied broadly, no contrast audit. | Every Blade view and component has correct `dark:` variants; AA contrast in both modes; map tiles/controls readable. | Checklist audit of all views in `docs/accessibility.md` (shared with RNF-15). Feature tests: toggle renders on all layouts. Fix findings; screenshot evidence at 375px and 1366px in the notes. |

### 5.5 Administration

| ID | Requirement | Status | Current state (assessed) | Target behavior / acceptance criteria | TDD plan, notes and docs |
|---|---|---|---|---|---|
| HU-034 | Admin dashboard metrics | Partial | Metrics and live map exist. No average rating or community activity. | Add platform average rating and community counts (posts today/week). Community part depends on **HU-017/018**. | Tests: dashboard shows averages with fixtures (including no ratings → "—"); community widgets appear after HU-017. |
| HU-038 | Delete bus | Partial | Physical delete. No soft delete or active-use guard. | **Soft delete or deactivate**; block with a message if the bus has an active trip or an assigned driver (or require unassign first). History kept. | 🗄️ `deleted_at` if soft delete. Tests: delete blocked with active trip; allowed otherwise; trips preserved; bus hidden from lists/maps. Overlaps **RNF-12**. |
| HU-041 | Add driver manually | Partial | Admin creates the account. No automatic temporary password or email. | Generate a strong temporary password (or invitation link), email it, and **force a change at first login**. Never log or display the password after creation. | Tests with `Notification::fake()`: mail queued; `must_change_password` enforced; old flow validations unchanged. 🗄️ flag column. |
| HU-042 | Edit driver | Partial | Edit with active status. No password-reset email. | Admin can trigger a password-reset email; edits validate unique email; deactivating a driver with an active trip is handled (end/block). | Tests: reset email sent; deactivate with active trip behavior; validation. |
| HU-043 | Delete driver | Partial | Deletes the account **and cascades to trips**. History is lost. | Replace hard delete with **deactivation/soft delete**; trips, ratings and stats stay; the driver can no longer log in. | 🗄️ if soft delete. Tests: trips remain; login blocked; driver hidden from active lists; ratings still visible to admin. Overlaps **RNF-12**. |
| HU-045 | Add route with map | Partial | Form with stop selection. No map-based drawing. | Delivered by the **Route & Stop Management Studio** (Milestones 2–5, `GET /admin/routes/manage`). Selecting main stops on a Leaflet map, anchored frequent stops preserved, geometry via `RouteGeometryService`, **OSRM only on explicit action**, geometry preserved on failure. | Follow `.junie/AGENTS.md`: `RouteStopSyncService` (done), then `RouteGeometryService` with `Http::fake()`, JSON endpoints under `/admin`, Blade + Alpine + Leaflet UI. Tests first for each endpoint (guest/driver/passenger/admin). Docs: Studio usage. |
| HU-046 | Edit route with map | Partial | Same as HU-045, and the current form **reorders stops** (known defect). | Editing keeps the combined main/frequent order; changing main stops only moves frequent stops per the anchored rule; the route is flagged for review when an anchor is removed. | Existing `known_defect` / `known_gap` characterization tests should be flipped to assert the **correct** behavior once the Studio replaces the form. Update `RouteController` or retire the old form deliberately. |
| HU-047 | Delete route | Partial | Physical delete; cascades to trips; no validations. | Prefer **deactivate** (`is_active = false`). Hard delete only when the route has no trips; otherwise blocked with an explanation. | Update the `known_risk` destroy test to the new contract. Tests: delete with trips blocked; deactivate hides from passenger lists but keeps history; guard messages. Overlaps **RNF-12**. |

---

## 6. Overlaps and suggested order

**Dependency groups (implement together):**

| Group | Items | Notes |
|---|---|---|
| Messaging | HU-20 | One feature, one test file. |
| Emergency and incident reports | HU-25, HU-24 | Shared migration. |
| Driver onboarding | HU-05 → HU-06 → then HU-41, HU-42 | 🗄️ migration for `status` and license fields. |
| Safe deletion | RNF-12, HU-38, HU-43, HU-47 | Deactivate/soft delete + unique index; ask for migration approval. |
| Route Studio | HU-45, HU-46, RNF-05 (stops endpoint) | Already in progress; see `.junie/AGENTS.md`. |
| Community | HU-34 (community widget) | 🗄️ `posts`. |
| ETA and freshness | HU-30, HU-09, HU-14, RF-30 tests | Share one staleness definition (5 min). |
| Docs and measurement | RNF-06, RNF-10, RNF-13, RNF-14, RNF-15, HU-33 | Mostly runs, reports and runbooks. |

**Recommended order:**

1. Baseline: run `php artisan test` and record counts; confirm Phase A and `RouteStop` tests pass.
2. Route Studio (Milestones 2–5): `RouteGeometryService` → endpoints → UI.
3. Safe deletion + `route_stop` unique index (after owner approves the migrations).
4. Authorization fixes (RNF-02) and the ETA/staleness work (HU-30, HU-09, HU-14).
5. Reports (RF-19, HU-25, HU-24) and favorites limit (HU-27).
6. Driver onboarding and approval (HU-05/06/41/42).
7. Messaging, then community.
8. Settings/locale, logout behavior, dark-mode and accessibility audits.
9. Measurements and operations: k6 and Selenium runs, backup/restore, then production Docker/IONOS.

## 7. Documentation deliverables

- `docs/requirements-status.md` — one row per ID: status, tests, date.
- `docs/requirements-deviations.md` — HU-003 (JWT dropped), any approved deviations (HU-001, HU-007, HU-024).
- `docs/performance.md`, `docs/browser-compat.md`, `docs/accessibility.md`, `docs/runbooks/backup-restore.md`.
- Update `.junie/AGENTS.md` section "TDD plan and status" after each completed group.
