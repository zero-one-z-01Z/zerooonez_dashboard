# Source starter

The package is an installer only. The installed Laravel application owns its dashboard runtime under `app/Dashboard` and `app/Providers/DashboardServiceProvider.php`.

Canonical package theme inputs are `resources/views/dashboard`, `resources/js/back`, `resources/css`, `resources/lang`, and `public/dashboard`. Run `npm run build:starter` after changing them to refresh the source payload and manifest. `starter/app` contains the adapted local PHP runtime. The original four controllers and five migrations are archived with hashes in `provenance/source-snapshots`.

In a host application, build changed dashboard front-end assets with `npm exec vite -- --config vite.dashboard.config.js`. Create the first administrator with the installed local command; no credentials or client banking values are seeded.
