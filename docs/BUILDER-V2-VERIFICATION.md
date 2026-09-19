# Web Builder v2 — verification, 2026-09-19

## Delivered scope

The local Builder is now an eight-section bilingual v2 editor. It imports v1/v2, preserves independent create/edit contracts, exposes all 16 input types and specialized/nested settings, and previews the page/forms, routes, controller/model, permissions and sidebar. Simple definitions generate real files. Advanced definitions return JSON + Markdown with proposed files/functions and explicit read/save/acceptance requirements; they do **not** create partial working pages or launch Agents.

Direct generation patches the protected admin routes and translations with a reviewed fingerprint. It refuses conflicting translations, existing resources and stale previews, backs up shared files, and attempts rollback of every written file even if one restore fails. Permission sync adds missing keys only; roles and migrations are manual.

## Evidence

Final run: **98 PHP tests / 886 assertions**, **75 JavaScript tests**, Vite build passed. Builder browser: 12 scenarios passed; widget browser: 3 scenarios passed; legacy tabs: 14 checks passed. Browser screenshots were inspected at 390px in both directions. These counts are separate suites, not a combined production acceptance claim.

- `/usr/local/opt/php/bin/php vendor/bin/phpunit tests/Feature/Dashboard tests/Unit/Dashboard`: Dashboard-focused PHP suites, SQLite memory and temporary generated source. Final count is recorded below.
- `npm run test:dashboard`: JavaScript runtime/editor tests, including typed dispatch, row-scoped dependencies, presence markers, switch serialization, zero map coordinates and boundary deletion.
- `npm run build`: all Vite entry points compile.
- [Builder browser report](../../tests/Browser/builder-v2-last-report.json): actual Blade and actual normalization/generator services through an isolated router. All bundled patterns and all 16 input types pass through the real editor/API. Direct files are written only in a temporary fixture. Arabic/English, 390px, stale approval invalidation, package-only restrictions, no uncaught exceptions.
- [Widget browser report](../../tests/Browser/builder-v2-widgets-report.json): actual Select2/Dropzone, Blade and adapters; three-level clearing, independent media, consecutive records, deletion markers, Multiform delete/add/clear with unique IDs.
- [Legacy tab browser report](../../tests/Browser/last-report.json): existing real jQuery/Bootstrap/DataTables/Select2/Validate workflow passes 14 checks, with eight expected simulated POSTs and no uncaught errors.

`BuilderV2ReviewTest` installs the **actual generated** Model, Controller, migration and routes in an isolated Laravel app, then tests HTTP CRUD/list/datatable/edit, 302 guest, 403 permissions, 422 validation, aliased read/write, authoritative route ID and 404. It also checks source rollback and legacy/new sidebar permissions.

## Limits

- Browser harness authentication/CSRF are bypassed **only in its test router**. Real middleware and permissions are covered by PHP feature tests; application routes retain their protections.
- No production database, migrations, roles, external services, or Lavooria source were changed. No Skill was created.
- Complex children/pivot/files/business operations remain **per-page Agent implementations**, as required by the hybrid plan. Exported products/features/actions are specifications, not implemented application pages.
- Map lifecycle/value tests use provider stubs, not a live Google Maps key. Widget tests prove adapters, not remote storage uploads.
- PHP results cover Dashboard suites, not every legacy business/payment test.

## Reproduce

1. Create an isolated directory: `mktemp -d /tmp/dashboard-browser-v2.XXXXXX`.
2. Set `BUILDER_TEST_ROOT` to that exact directory and start the loopback-only test server:

   ```sh
   BUILDER_TEST_ROOT=/tmp/dashboard-browser-v2.YOUR_SUFFIX /usr/local/opt/php/bin/php -S 127.0.0.1:8998 -t public tests/Browser/serve-builder-v2.php
   ```

3. Run `node tests/Browser/run-builder-v2.cjs` and `node tests/Browser/run-builder-widgets.cjs`. Override `PLAYWRIGHT_PATH` and `CHROME_PATH` if necessary. A fresh browser profile is used; vendor assets come from the existing `public/dashboard` files.
4. Stop only the test server you started. Temporary generated files/backups may be retained for inspection.

These files live in `tests/Browser`, not application routes. Do not deploy the test router as the application. See [legacy browser instructions](../../tests/Browser/README.md) for the separate regression harness on port 8997.
