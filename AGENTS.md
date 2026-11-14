# Temp Mail SaaS

Build a maintainable temporary email SaaS as a Laravel 13 modular monolith. PHP 8.5 is the development and CI target. Use the configured MySQL, MariaDB, PostgreSQL, SQLite or SQL Server connection, Blade, reusable Blade components, Alpine, Tailwind CSS 4, and Vite. Check the manifests, lockfiles, and installed versions before changing dependencies. Preserve working behavior and Laravel conventions.

## Workflow

1. Inspect the relevant existing code, this file, and any matching .ai/rules instructions when present. Read the relevant guides below and the linked technical documentation.
2. Identify affected modules, data, and dependencies. Implement the smallest complete change. Reuse existing components, keep controllers thin, and keep business logic outside views.
3. Add or update module-owned migrations when needed and add or update tests for changed behavior. Run applicable checks and record actual results and gaps.
4. Update existing documentation where contracts or operational procedures change. Mark features Implemented, Planned, Under development, or Deprecated according to repository evidence. Use relative links and avoid per-task reports.

## Rules by area

| Area | Read before changing it |
| --- | --- |
| Module boundaries and server code | [Architecture](agents/architecture.md), [Backend](agents/backend.md) |
| Templates and interaction | [Frontend](agents/frontend.md), [Blade components](agents/blade-components.md) |
| Visual design and accessibility | [Design system](agents/design-system.md), [Accessibility](agents/accessibility.md) |
| Installation and data | [Installer](agents/installer.md), [Database](agents/database.md) |
| Accounts and privileged operations | [Authentication](agents/authentication.md), [Security](agents/security.md) |
| Uploads and branding | [Media Library](agents/media-library.md) |
| Verification and operations | [Testing](agents/testing.md), [Shared hosting](agents/shared-hosting.md) |

Use the Pure Zinc visual system, the shared Blade catalog documented in [Components](docs/components.md), and WCAG 2.2 AA as the accessibility target. Require server-side authorization for privileged and ownership-sensitive operations. Protect secrets and preserve CSRF. Keep schema changes explicit and initial boot independent of a configured database. Support Apache and cPanel with public/ as document root and Vite assets built locally or in CI. Production must run without Node.js, npm, or persistent queue workers.

## Product experience and definition of done

Use the [design system](docs/design-system.md) as the product UX reference. Extend the shared Blade shell, semantic tokens and existing component APIs. Each screen should put its main task first. Forms need visible labels, inline errors, pending and completion feedback, and safe submission behavior. Keep current-page selection explicit and use confirmations for irreversible operations.

A change is done when the normal entry point, authorization boundary, validation and failure path are verified, applicable tests and build checks have run, and the affected contracts and release steps are documented. Check changed interfaces by keyboard and at representative mobile and desktop widths in both themes using isolated data. Record untested cases explicitly. Keep production data and files out of test fixtures.

Future phase and step prompts must reference this file and the applicable guides. For multilingual work, define locale, direction, slug uniqueness and inactive-language visibility before exposing content. Add locale-keyed translations when required by an implemented module.

### Functional UX Before Decorative UX

Define a measurable [primary-task contract](docs/design-system.md#primary-task-contract) for every new or revised user-facing screen. Track implementation and release blockers in the [project overview](docs/project-overview.md). A rendered page alone does not establish readiness.

## Git and releases

Keep changes focused and commits descriptive. Preserve Git history and unrelated files. Obtain explicit authorization before rewriting history or force-pushing. Explain deployment configuration changes in the change summary. Keep .env, secrets, sensitive database dumps, runtime caches, and generated release assets out of Git. Use .env.example for non-sensitive settings and package public/build with releases.

Start with the [project overview](docs/project-overview.md), [architecture decisions](docs/decisions.md), and [deployment procedure](docs/deployment.md).

See [Localization](docs/localization.md) for the language registry, public locale rules, integration contracts, and release gates.

For font catalog policy, locale typography, and deployment checks, see [Fonts Management](docs/localization.md#fonts-management).

See [UI Translations](docs/localization.md#ui-translations) for key registration, status workflow, escaping and deployment checks.

Keep the Roles & permissions matrix aligned with the [authorization contracts](docs/authorization.md). Verify role counts, direct URL access, and user assignment protections after changes.

Preserve the installer-recorded Founder Super Admin and the independent last active Super Admin invariant in every account lifecycle change. See [Workspace authorization](docs/authorization.md#founder-super-admin).

Use the [navigation contexts](docs/components.md#navigation-contexts) and [deployment checks](docs/deployment.md#releases-backups-and-troubleshooting) when changing console access or schema-backed features.

Apply the [Branding primary-task contract](docs/media-library.md#branding-workflow) when changing brand assets or their public delivery.

Keep [homepage publishing](docs/localization.md#homepage-publishing) separate from public locale resolution. Preserve canonical English, authorization and explicit visitor preferences.

Use the [typography contract](docs/localization.md#multiscript-typography) for script defaults, language overrides and technical LTR text.
