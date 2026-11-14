# Deployment

## Local development

Use PHP 8.5, Composer, the PDO driver for the [selected database](database.md), and PDO SQLite for the default test configuration. Use a Node version supported by the locked Vite package. The current [CI workflow](../.github/workflows/checks.yml) selects Node 24. Run composer setup. When the local APP_KEY is empty, run php artisan key:generate. Keep existing keys. Start the development servers with composer dev or run php artisan serve and npm run dev separately. On Windows use npm.cmd if PowerShell restricts script execution.

Follow [Database](database.md) when configuring local database access. The initial welcome and health routes can start before a schema exists. See [Testing](testing.md) before publishing changes.

## Apache and cPanel

1. On a build machine with compatible PHP and extensions, prepare vendor/ with composer install --no-dev --prefer-dist --optimize-autoloader. Run npm ci and npm run build and verify public/build/manifest.json plus the assets it references.
2. Upload the application, prepared vendor/, and public/build/ as release artifacts. Keep .env and private files above the document root. Exclude public/hot, node_modules, and environment-specific runtime caches. Build artifacts stay out of Git.
3. In cPanel or Apache, point the domain at the application's public/ directory. Preserve public/.htaccess, enable rewriting, and grant the PHP user write access to storage/ and bootstrap/cache/. Use HTTPS and configure trusted proxies only where required.
4. Provision private production settings with APP_ENV=production, APP_DEBUG=false, HTTPS APP_URL, and a deployment-specific key. Preserve the key on upgrades. Use a least-privilege database account when configuring the database. For the current foundation, provision configuration through the hosting operator. The [web installer](installation.md) verifies an empty database and finalizes setup behind its private operator gate. It can provision the application key for a fresh deployment.
5. Verify /, /up, HTTPS asset responses, secure session cookies, and denial of requests for private files. Verify the expected environment and manifest after switching releases. Apply schema changes separately using [the migration procedure](database.md).

A File Manager upload can deploy prepared dependencies and assets without a production shell or Node.js. Use database-backed sessions only after the sessions migration and configuration are ready. If a later feature requires scheduling, use provider-managed cron with bounded, non-overlapping work and document its cadence. See [shared-hosting constraints](../agents/shared-hosting.md).

## Releases, backups and troubleshooting

Use the PHP 8.5 target with Composer platform requirements, the selected PDO extension and Fileinfo. GD with the relevant raster codecs is required for media uploads. SQL Server additionally needs its compatible ODBC runtime. Follow the [installation gate procedure](installation.md) on a new deployment and use the verified owner session to create the first Super Admin.

Back up the database, private media, installer completion marker, SQLite file when applicable, and the original environment and application key before replacing a release. Preserve these files across deployments. Verify a restore on an isolated host. Roll back code and assets together and restore the matching schema from backup when a migration cannot safely be reversed.

Confirm the target database connection and a recoverable backup. Review migrate:status and the release scope, then apply only approved migrations through a controlled terminal or through the hosting operator. Preserve existing records and avoid destructive database resets. The web installer handles fresh schema setup without shell access. Check migration status before opening traffic. After changing configuration, clear the configuration cache through the release tooling or have the host remove the generated bootstrap/cache/config.php file. Keep public/hot out of the release. A missing manifest requires redeploying the complete public/build directory from the same release.

Follow the [account mail deployment checks](authentication.md#account-mail-deployment-checks). Configure hosting cron for Laravel schedule:run to enforce session retention. Pending media deletion is retried from Trash.

## Schema recovery

1. Confirm the intended connection and inspect migration status.
2. Verify a recoverable backup, then inspect the actual schema and relevant data.
3. Plan additive changes and apply only explicitly approved migrations in a separate implementation task.
4. Verify the resulting schema, exercise each affected module and run regressions.

A migration file, a recorded migration, the resulting schema and functional module behavior are separate evidence. See [localization status](localization.md#ui-translations).
