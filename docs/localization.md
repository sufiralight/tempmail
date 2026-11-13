# Localization

The Languages console is available to active, verified Super Admins under Localization → Languages. The database registry owns visibility, direction, ordering and stable locale codes. The migration seeds 30 entries with English as the only active and default entry. Reseeding inserts missing entries and preserves operator edits. Locale codes are immutable in the console. There is no delete action.

A single database row references the default. Every console mutation locks that row inside a transaction. Only active languages qualify as default and bulk deactivation rejects the entire selection if it contains the default. Changes are recorded in the existing administrative audit log.

## Public resolution

The landing page resolves an explicit locale path first, then an active session preference, then the active registry default. Unrecognized and inactive explicit locales return 404. If the registry is unavailable before migration, the root uses the configured application locale and localized routes stay closed. Locale codes are matched case-insensitively with hyphens and underscores normalized. The resolved record sets HTML language and direction. Laravel uses APP_FALLBACK_LOCALE for missing translation keys. Public landing copy remains English until translated resources are provided. The console and authentication currently use their existing interface language.

The registry is queried on each public request and has no persistent cache to invalidate. The public switcher only shows active records. Changes leave localized content intact.

## Integration

CMS and UI Translations should store relationships against stable language IDs and use canonical codes for external URLs. Scope slug uniqueness to the owning content type and language. Store slug, publication state and SEO metadata on the specific translation. Reload all translation fields when the editor changes languages. Apply both language activation and publication checks before including content in public routes, navigation, canonicals, hreflang and sitemaps. Authorized editors may access inactive translations. Gate schema-dependent editors on their own migrations.

Fonts Management will be a separate Localization module. Reference language IDs and allow multiple script coverage entries and separate heading and body assignments where needed. Validate glyph coverage, direction, family, subsets and variable ranges. Use locally hosted fallback stacks and review Google Fonts licenses and hosting terms. Load only the font files needed by the active locale and rendered scripts. Geist does not cover all supported scripts. The registry supplies locale and direction and does not prescribe a single family per locale.

## Deployment

Back up the application database and verify restore access before a release. Run migrate:status against the intended connection. Review pending media migration 000010 separately before approving a full migration run. The language migration requires the existing administrative schema for audited writes. Apply the reviewed migrations during the release window, verify the registry has 30 initial entries and an active default, then check the Super Admin screen and public locale routes. Package the compiled Vite assets. Do not reset a deployed database. The language migration rollback removes its own registry and must not be used after downstream modules reference it without a reviewed data plan.
