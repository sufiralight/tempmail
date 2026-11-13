# Testing

The current [PHPUnit configuration](../phpunit.xml) uses SQLite in memory and array sessions and cache. The suite checks the root and health routes, local preview visibility, button rendering, field associations, media markup, and pagination. Use the local /_ui preview for browser interaction and responsive checks. The [CI workflow](../.github/workflows/checks.yml) builds assets and runs PHPUnit, Larastan, and Pint.

| Check | Command |
| --- | --- |
| Unit and feature suite | composer test |
| Focused behavior | php artisan test --filter=TestName --no-interaction |
| Static analysis | composer analyse |
| Upload progress, cancellation and response handling with simulated XHR | node --test tests/js/media-upload.test.mjs |
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

## Browser QA matrix

For changed screens, exercise the normal task at 375, 390, 768, 1024 and 1440 CSS pixels where applicable. Check Light, Dark and System, focus visibility, keyboard submission, dialog Escape and focus return, and horizontal overflow. Use an isolated database, temporary media disk and disposable accounts. Report each executed result and any gaps.

For SEO saves, click the visible submit control, check the server response and confirmation, then reload and verify persisted values. For collection changes, exercise selection, upload refresh, Trash and Restore, and protected deletion. Check the deployed schema before opening authenticated traffic.

### Regression prevention

- Verify direct localhost and 127.0.0.1 installer access through the existing operator gate. Keep installer authorization out of public setup forms.
- Check migration status before opening routes that query new tables. The confirmed media 503 was an absent purge_requested_at column on the collection GET. Apply migration 000010 before accepting uploads that write original_filename.
- Keep selection queries scoped to the collection root. Check avatar loading with the actual browser and preserve the media record on avatar removal. Test owner-scoped filename collisions, SEO persistence and automatic refresh.
- Check registration and recovery links under the intended guest configuration. Check saved and system theme transitions after reload.
- UI translation and CMS SEO tables, locale-specific slugs, RTL behavior and inactive-language visibility are future module checks. Verify schema and locale boundaries when those modules ship.

See [Localization](localization.md) for the language registry, public locale rules, integration contracts, and release gates.

For font catalog policy, locale typography, and deployment checks, see [Fonts Management](localization.md#fonts-management).
For Typography 2.0, verify an italic Roboto assignment persists and that public CSS requests only the selected style and weight.

See [UI Translations](localization.md#ui-translations) for key registration, status workflow, escaping and deployment checks.
Verify first sign-in to Admin Console for the installer-created Super Admin and require fresh verification after an email change.
