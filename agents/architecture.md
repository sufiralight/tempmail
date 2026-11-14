# Architecture

Keep one Laravel deployable and one application database. Introduce modules inside existing Laravel directories when the first real use case lands. Use feature-specific namespaces, Blade view directories, named route groups, policies, and services as needed. Preserve public routes and existing data during changes. Document any required transition in the same change.

Use the ownership map in [Architecture](../docs/architecture.md). A module owns its rules, write operations, and schema. Cross-module calls go through the owning module's application operation or an explicitly documented read contract. Keep dependency direction visible and break cycles before merging. Use shared infrastructure only when multiple concrete callers need it.

Keep HTTP concerns at the edge and business rules out of Blade. Keep shared visual patterns in Blade components. Avoid microservices and framework layers that duplicate Laravel.

Apply schema changes during controlled releases and verify required migrations before exposing a module. For future translated content, use the owning module’s locale-keyed model and enforce locale availability at the route boundary.

See [Localization](../docs/localization.md) for the language registry, public locale rules, integration contracts, and release gates.

For font catalog policy, locale typography, and deployment checks, see [Fonts Management](../docs/localization.md#fonts-management).

See [UI Translations](../docs/localization.md#ui-translations) for key registration, status workflow, escaping and deployment checks.

Keep the Roles & permissions matrix aligned with the [authorization contracts](../docs/authorization.md). Verify role counts, direct URL access, and user assignment protections after changes.

Keep the [implementation priorities](../docs/project-overview.md#implementation-priorities) and [schema recovery procedure](../docs/deployment.md#schema-recovery) aligned with the deployed module contracts.

Reuse the localization registry and translation value store for [homepage content](../docs/localization.md#homepage-publishing).

Resolve typography through the language registry and existing font assignments. Script defaults are additive and independently deployed.

Keep the Typography Studio on the existing resolver, catalog and database contracts.
