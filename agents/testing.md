# Testing

Add focused tests for changed behavior and regressions. Use PHPUnit unit tests for isolated rules and Laravel feature tests for routes, validation, authorization, and persistence. Use isolated test data and factories. Prove negative paths at the actual action boundary.

Use the [coverage matrix and commands](../docs/testing.md) for installer retries, authentication, ownership, migrations, Blade components, upload validation, accessibility, and Vite release builds as each capability lands. Execute the relevant commands and report actual results, skipped checks, and environment limits.

Use the [browser QA matrix](../docs/testing.md#browser-qa-matrix) for changed interactions. Prefer actual pointer and keyboard input when investigating a click failure. Confirm the request, persisted result and feedback independently.

See [Localization](../docs/localization.md) for the language registry, public locale rules, integration contracts, and release gates.
