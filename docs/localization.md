# Localization

The Languages console is available to active, verified Super Admins under Localization → Languages. The database registry owns visibility, direction, ordering and stable locale codes. The migration seeds 30 entries with English as the only active and default entry. Reseeding inserts missing entries and preserves operator edits. Locale codes are immutable in the console. There is no delete action.

A single database row references the default. Every console mutation locks that row inside a transaction. Only active languages qualify as default and bulk deactivation rejects the entire selection if it contains the default. Changes are recorded in the existing administrative audit log.

## Public resolution

The landing page resolves an explicit locale path first, then an active session preference, then the active registry default. Unrecognized and inactive explicit locales return 404. If the registry is unavailable before migration, the root uses the configured application locale and localized routes stay closed. Locale codes are matched case-insensitively with hyphens and underscores normalized. The resolved record sets HTML language and direction. Laravel uses APP_FALLBACK_LOCALE for missing translation keys. Public landing copy remains English until translated resources are provided. The console and authentication currently use their existing interface language.

The registry is queried on each public request and has no persistent cache to invalidate. The public switcher only shows active records. Changes leave localized content intact.

## Integration

CMS and UI Translations should store relationships against stable language IDs and use canonical codes for external URLs. Scope slug uniqueness to the owning content type and language. Store slug, publication state and SEO metadata on the specific translation. Reload all translation fields when the editor changes languages. Apply both language activation and publication checks before including content in public routes, navigation, canonicals, hreflang and sitemaps. Authorized editors may access inactive translations. Gate schema-dependent editors on their own migrations.

## Fonts Management

Verified Super Admins use Localization → Fonts to search the curated Google Fonts catalog, preview a language and assign body and heading families independently. The manifest in config/typography.php defines allowed family identifiers and primary writing system compatibility. Google Fonts CSS API availability was checked for the 13 families at normal weights 400 and 600. Script compatibility is curated metadata and does not certify complete glyph coverage or shaping. Urdu uses a dedicated Nastaliq candidate and Persian has a separate preview sample. Korean and Japanese may still require platform fallback for individual characters.

Assignments reference language IDs, use enumerated fallback stacks and can be reset to inherited typography. Inheritance preserves Geist for Latin, Cyrillic and Vietnamese and selects a compatible active Noto family for other supported writing systems. Deactivation rejects families with explicit assignments. Inactive languages remain editable without becoming public. CMS previews should resolve typography using the edited language ID and apply direction separately.

Public layouts receive typography only from the existing locale resolver. The application constructs a fixed-host CSS API URL with only the resolved family and weight pairs and deduplicates pairs in one stylesheet. The stylesheet loads asynchronously with display=swap. Local Geist and browser fallbacks remain available. Google receives the browser IP and standard request metadata when a remote font loads. Assign system typography and deactivate unassigned remote families when third-party requests are prohibited. No Google API key or runtime metadata fetch is needed. Registry reads use the database directly, so updates do not require cache clearing.

Apply the reviewed language migration before the typography migration. Back up both language and font settings before rollback. Reverting the typography migration drops its assignments and family state. After deployment verify authorization, inherited typography, an active RTL locale, and network requests for the selected families.

## Deployment

Back up the application database and verify restore access before a release. Run migrate:status against the intended connection. Review pending media migration 000010 separately before approving a full migration run. The language migration requires the existing administrative schema for audited writes. Apply the reviewed migrations during the release window, verify the registry has 30 initial entries and an active default, then check the Super Admin screen and public locale routes. Package the compiled Vite assets. Do not reset a deployed database. The language migration rollback removes its own registry and must not be used after downstream modules reference it without a reviewed data plan.

Typography 2.0 adds an additive style column with Normal as the default for existing assignments. Roboto offers Normal and Italic at the configured weights. Other curated families expose Normal. Deploy the style migration after the original typography migration, with a database backup. Older schemas continue to accept Normal assignments until the migration is applied.
