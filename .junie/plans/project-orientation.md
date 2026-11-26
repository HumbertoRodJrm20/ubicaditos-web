---
sessionId: session-261125-155617-1iha
---

# Requirements

### Goal
Prepare project context for the next programming task by reviewing the supplied AGENTS guidance, repository documentation, and current Route & Stop Studio implementation.

### Scope
- Summarize the project architecture and working constraints.
- Identify documentation that conflicts with current source.
- Make no code or documentation changes.

# Technical Design

### Current Implementation
The project is a Laravel 12 Blade application with Alpine.js and Leaflet. The Route & Stop Studio is implemented in `app/Http/Controllers/Admin/RouteManagementController.php`, `app/Http/Controllers/Admin/StopController.php`, `app/Services/RouteStopSyncService.php`, `app/Services/RouteGeometryService.php`, `resources/views/admin/routes/manage.blade.php`, and `resources/js/route-studio.js`.

### Findings
- No repository `AGENTS.md` was found; use the AGENTS guidance supplied in the task.
- Several reference docs still describe Stop CRUD as unimplemented and say there are no WebSockets, contrary to current source.
- The Studio currently sends all route geometries and all stops to the page and creates markers for all stops, conflicting with the stated map performance constraints.
- No project files are to be changed for this orientation task.

# Delivery Steps

###   Step 1: Review project guidance and documentation
The project architecture, documented domain, workflows, and operating constraints are summarized for the next task.
- Use the supplied AGENTS guidance as the project instructions because no repository `AGENTS.md` exists.
- Review the documentation index, Markdown docs, Swagger specification, and diagram document.
- Record documented historical constraints and implementation conventions.

###   Step 2: Compare Route & Stop Studio docs with source
The current Studio implementation and documentation mismatches are identified without modifying files.
- Trace the route and stop controllers, synchronization and geometry services, Blade view, and Alpine/Leaflet module.
- Compare behavior against the route-management documentation and performance requirements.
- Report confirmed mismatches and readiness notes; do not run tests or make code changes.