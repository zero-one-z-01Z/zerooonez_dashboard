# Source installer verification — 2026-09-19

This is an unreleased local working tree, not a published replacement for v0.1.1.

## Verified

- Independent clean Laravel 12 fixture.
- Its full suite passed: **17 tests, 103 assertions**. Coverage includes installed auth/core pages, actual session-based generated City CRUD, route-ID authority, permission-sync idempotence, inactive/missing-role denial, no password-hash disclosure, role deletion protection, permission allowlisting, isolated reset tokens, settings scalar/JSON persistence, and host default-guard preservation.
- The same suite passed after removing the dashboard Composer package and refreshing autoload/discovery. `vendor/zeroonez/zerooonez-dashboard` is absent in that fixture.
- Blade and route caching passed; test caches were cleared afterward.
- Package suite: 21 tests, 45 assertions, with 3 intentionally skipped legacy package-runtime auth tests. Those skips are not reported as passing tests; installed-source authentication is covered by the independent fixture.
- JavaScript suite: 77 passed, 0 failed. Production Vite build passed.
- Browser: actual fixture login, generated City create and rendered row, Arabic/English core screens, POST logout, and mobile 390px login/Home inspection. The toolbar no longer renders `[object HTMLSpanElement]`.

## Consuming project installed

A separate new Laravel 12 consumer project.

- Composer now uses the local source package as a development dependency through a path repository. The GitHub VCS entry remains available, but does not provide these uncommitted changes.
- 640 source-manifest entries; the final update dry run reports zero writes/conflicts.
- Added five pending core/reset migrations on the existing local MySQL database. Existing users/permissions/demo categories migrations and data were not reset.
- Preserved the generated DemoCategory definition/model/route/controller behavior and custom translation keys. Only its shared base-controller namespace was migrated from the package to local `App\Dashboard`.
- Pre-install source backup: `.dashboard/backups/pre-source-install/project-files.tar.gz`; installer replacements also have checksum-tracked backups.
- `/admin/login` renders in the consuming project. No real administrator was created: the user chose to run `dashboard:create-admin` privately.

## Boundaries

- Email delivery was isolated with a test fake/log transport; real delivery requires the consuming project's mail configuration.
- Same donor theme/auth templates are reused; Home uses available core administration data, not fictitious auction sales. Original source snapshots remain under `provenance`.
- This is local/internal validation, not a production security certification or an asset redistribution-license approval. No release/tag/push was performed.
- The temporary fixture is evidence, not a distributable production project.
