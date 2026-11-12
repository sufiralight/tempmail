# Testing

The current [PHPUnit configuration](../phpunit.xml) uses SQLite in memory and array sessions and cache. The suite checks the root and health routes, local preview visibility, button rendering, field associations, media markup, and pagination. Use the local /_ui preview for browser interaction and responsive checks. The [CI workflow](../.github/workflows/checks.yml) builds assets and runs PHPUnit, Larastan, and Pint.

| Check | Command |
| --- | --- |
| Unit and feature suite | composer test |
| Focused behavior | php artisan test --filter=TestName --no-interaction |
| Static analysis | composer analyse |
| Formatting | vendor/bin/pint --test (Windows: vendor/bin/pint.bat --test) |
| Clean release build | npm ci, then npm run build |
| Routes | php artisan route:list --no-interaction |

Check both Vite entry points and referenced files in the production manifest. Use a local or CI-generated key where the test needs encrypted sessions or cookies. Use isolated databases for migration tests and never point automated tests at a production database.

| Area | Coverage to add with the feature |
| --- | --- |
| Installer | Unavailable database, empty local password, missing environment on an existing instance, unauthorized access, competing writes, interrupted finalization and replay |
| Authentication and authorization | Session regeneration and logout, token expiry, throttling, guest access, cross-owner denials, explicit administrator abilities |
| Schema | Fresh migrate, upgrade with data, constraints, recoverable failures on supported engines |
| Components | Rendered variants and defaults, forwarded attributes, accessible labels and error state, browser interactions and theme persistence |
| Media | MIME mismatch, invalid image, limits, forbidden access, referenced deletion and partial storage failure |
| Accessibility | Automated scan plus the [manual checks](accessibility.md) in both themes |

Report each command executed, outcome, and coverage gaps. SQLite smoke tests alone do not establish MySQL or MariaDB compatibility.

The installer integration test opts into a disposable MariaDB instance bound to 127.0.0.1 on a non-default port. Create a new, empty schema named tempmail_installer_test_ followed by 16 lowercase hexadecimal characters on that isolated instance. Set INSTALLER_TEST_MYSQL_PORT and INSTALLER_TEST_MYSQL_DATABASE and run the suite. The fixture uses an empty root password and writes migrations and an administrator only to that schema. Discard the instance afterward. The ordinary suite skips this test when the fixture is absent.
