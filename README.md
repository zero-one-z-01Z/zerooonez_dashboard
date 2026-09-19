# ZeroOneZ Dashboard

Private Laravel 12 dashboard **source installer** for ZeroOneZ projects.

Version 0.2 installs the complete dashboard into the consuming application: authentication, Home, core admin controllers, models, migrations, views, translations, JavaScript, public assets, the Web Builder, and the local CRUD generator. Runtime code lives under the project's `app`, `resources`, `routes`, `database`, and `public` directories; it does not depend on package classes after installation.

> Internal/private distribution only. The included theme assets must not be redistributed outside an authorized organization until their third-party rights are verified. See `THIRD_PARTY_NOTICES.md`.

## Requirements

- PHP 8.2 or newer
- Laravel 12
- Node.js 20+ only when rebuilding dashboard frontend assets
- A private GitHub account/SSH key authorized for this repository

## Install from private GitHub

Add the repository and package as a development dependency:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:zero-one-z-01Z/zerooonez_dashboard.git"
    }
  ],
  "require-dev": {
    "zeroonez/zerooonez-dashboard": "^0.2"
  }
}
```

Then run:

```bash
composer update zeroonez/zerooonez-dashboard
php artisan dashboard:install --dry-run
php artisan dashboard:install
php artisan migrate
```

The installer uses a SHA-256 manifest and records installed baselines in `.dashboard/installed.json`. Existing conflicting files are preserved and reported. Do not use `--force` before reviewing the conflict and taking a backup.

Create the first administrator without putting its password in shell history:

```bash
php artisan dashboard:create-admin "Your name" "you@example.com" "your-phone"
```

The command asks for the password privately. No default administrator credentials are installed.

## Use the Builder

For local development, set:

```dotenv
APP_ENV=local
DASHBOARD_BUILDER_ENABLED=true
```

Clear cached configuration, sign in at `/admin/login`, then open `/admin/dashboard-builder`:

```bash
php artisan config:clear
```

The Builder creates project-owned controllers under `app/Http/Controllers/Admin/Generated`, models under `app/Models`, definitions under `dashboard/resources`, migrations, translations, and compact routes. After generating a resource such as `cities`, run:

```bash
php artisan migrate
php artisan dashboard:sync-permissions cities
php artisan route:clear
```

For a JSON definition, the local source generator also supports:

```bash
php artisan dashboard:make path/to/schema.json
php artisan dashboard:make path/to/schema.json --write
```

The preview command does not write files. The write command refuses to overwrite existing generated resources.

## Update installed source

Update the Composer package deliberately, preview the source changes, then apply them:

```bash
composer update zeroonez/zerooonez-dashboard
php artisan dashboard:update --dry-run
php artisan dashboard:update
```

`dashboard:update-assets` remains as a compatibility alias for the guarded source update. Locally modified files are preserved and reported as conflicts. Project-specific generated resources remain owned by the project and are not overwritten by package updates.

Do not edit files inside `vendor`. Application customizations belong in the installed project source. Package owners edit this repository, run the builds and tests, then publish a tagged version.

## Rebuild package sources

```bash
npm install
npm test
npm run build
npm run build:starter
```

`npm run build:starter` refreshes the canonical theme inputs in `starter/` and regenerates `starter-manifest.json`. Adapted PHP runtime sources inside `starter/app` are preserved.

## Verification

```bash
composer install
php vendor/bin/phpunit
npm test
npm run build
```

The package test environment currently needs PHP 8.4 because of its locked development dependencies; consuming applications support PHP 8.2+.

See:

- `docs/INSTALL-AND-CONTROLLERS.ar.md` for the Arabic installation/controller guide.
- `docs/STARTER-GUIDE.md` for source ownership and build notes.
- `docs/SOURCE-INSTALL-VERIFICATION.md` for the verified acceptance scope and remaining boundaries.
- `docs/BUILDER-V2-USER-GUIDE.md` for the Web Builder workflow.
