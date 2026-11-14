# Testing

Add focused tests for changed behavior and regressions. Use PHPUnit unit tests for isolated rules and Laravel feature tests for routes, validation, authorization, and persistence. Use isolated test data and factories. Prove negative paths at the actual action boundary.

Use the [coverage matrix and commands](../docs/testing.md) for installer retries, authentication, ownership, migrations, Blade components, upload validation, accessibility, and Vite release builds as each capability lands. Execute the relevant commands and report actual results, skipped checks, and environment limits.

Use the [browser QA matrix](../docs/testing.md#browser-qa-matrix) for changed interactions. Prefer actual pointer and keyboard input when investigating a click failure. Confirm the request, persisted result and feedback independently.

See [Localization](../docs/localization.md) for the language registry, public locale rules, integration contracts, and release gates.

For font catalog policy, locale typography, and deployment checks, see [Fonts Management](../docs/localization.md#fonts-management).

See [UI Translations](../docs/localization.md#ui-translations) for key registration, status workflow, escaping and deployment checks.

Keep the Roles & permissions matrix aligned with the [authorization contracts](../docs/authorization.md). Verify role counts, direct URL access, and user assignment protections after changes.

Require evidence for the [primary-task contract](../docs/design-system.md#primary-task-contract). Record skipped, blocked and untested cases separately. Sequential tests do not establish concurrency behavior.
