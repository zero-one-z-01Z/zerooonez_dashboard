# Source starter

The installer copies this payload into the Laravel application. Runtime classes live under app/Dashboard and app/Providers/DashboardServiceProvider.php; the Composer package does not supply a live provider or helper file.

Run migrations, then create the first administrator with php artisan dashboard:create-admin. No administrator, password, IBAN, bank, or client business data is seeded by the starter.

The shipped assets are in public/dashboard/build. After changing copied dashboard JavaScript or CSS, rebuild them in the host application with npm exec vite -- --config vite.dashboard.config.js.

The full source snapshots and SHA-256 hashes under provenance/source-snapshots preserve the original four controllers and five migrations. The installed HomeController uses only core dashboard metrics; it intentionally does not invent auction or sales data.
