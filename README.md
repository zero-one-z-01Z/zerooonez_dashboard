# ZeroOneZ Dashboard

Reusable Laravel 12 dashboard runtime, local builder, CRUD generator, Blade views, JavaScript modules, and publishable dashboard assets.

> **Private/internal distribution only.** The package source is supplied under the included private-use license. The bundled dashboard/theme assets were recovered from an existing application and their commercial redistribution rights have **not** been established. Do not publish this package or distribute the theme assets outside an authorized organization until each asset's license has been verified.

## Requirements

- PHP 8.2 or newer
- Laravel 12
- Node.js 20+ to rebuild frontend bundles

## Install from a private path repository

```json
{
  "repositories": [{"type": "path", "url": "packages/zerooonez_dashboard"}],
  "require": {"zeroonez/zerooonez-dashboard": "@dev"}
}
```

Run `composer update zeroonez/zerooonez-dashboard`, then:

```bash
php artisan dashboard:install
```

For an authorized private GitHub installation, replace the path repository with:

```json
{
  "repositories": [
    {"type": "vcs", "url": "git@github.com:zero-one-z-01Z/zerooonez_dashboard.git"}
  ],
  "require": {"zeroonez/zerooonez-dashboard": "^0.1"}
}
```

Projects should update deliberately with `composer update zeroonez/zerooonez-dashboard` and commit their Composer lockfile. Do not depend on `main` in production.

The command creates the configurable generator directories, keeps an existing config file, and publishes checksum-verified assets. Existing changed assets are not overwritten. Use `--force` only after reviewing local customizations. `dashboard:update-assets` performs the same guarded asset sync without the rest of installation.

The published `public/dashboard` tree is installation output. Keep it out of the consuming project's source control and reproduce it with `dashboard:install` or `dashboard:update-assets` during deployment.

The browser builder is disabled by default. In local development only, set `DASHBOARD_BUILDER_ENABLED=true`. Its controller additionally rejects non-loopback requests. Add authentication/authorization middleware through `dashboard.builder_middleware`; the package does not guess the consuming application's admin guard.

## Configuration seams

`config/dashboard.php` controls route prefixes, middleware, permission storage, public asset path, and all generated application paths/namespaces. Generated controllers/models/definitions/routes/translations are written into the consuming project, never into `vendor`. Route files are loaded inside the package's configurable admin group and use compact `all_routes(...)` blocks.

Set `permission_model`, `permission_table`, and `permission_key_column` for the host schema, or bind `ZeroOneZ\Dashboard\Contracts\PermissionStore` to a custom implementation. `dashboard:sync-permissions` inserts missing keys only; it never grants or removes role assignments.

## Builder and generator

- `dashboard:make schema.json` previews generated source.
- `dashboard:make schema.json --write` writes only after the preview fingerprint matches.
- `dashboard:sync-permissions [resource]` inserts missing permission keys.

The generator refuses conflicting paths and does not run migrations, seeders, or role grants. See `docs/` for the inherited dashboard contracts and examples. `examples/CityController.php` is reference-only and is not autoloaded.

## Frontend build

```bash
npm install
npm test
npm run build
```

Vite writes standalone bundles and its manifest to `public/build`; the build then refreshes `assets-manifest.json`. Blade uses the package `@dashboardVite` directive to resolve these published bundles.

## Tests

```bash
composer install
composer test
composer test:syntax
```

The isolated suite uses Orchestra Testbench. Theme behavior still requires browser testing in the consuming application because many copied views intentionally rely on host routes, translations, authentication data, and page definitions.
