# Localization

The Languages console is available to active, verified Super Admins under Localization → Languages. The database registry owns visibility, direction, ordering and stable locale codes. The migration seeds 30 entries with English as the only active and default entry. Reseeding inserts missing entries and preserves operator edits. Locale codes are immutable in the console. There is no delete action.

A single database row references the default. Every console mutation locks that row inside a transaction. Only active languages qualify as default and bulk deactivation rejects the entire selection if it contains the default. Changes are recorded in the existing administrative audit log.

## Public resolution

The landing page resolves an explicit locale path, then the active session preference or account interface preference, then weighted browser preferences, then the active registry default. A regional browser preference can fall back to its base locale. Invalid ranges, wildcards and zero-weight choices do not select a language. If the configured default is inconsistent, the first active registry entry is used. Unrecognized and inactive explicit locales return 404. If the registry is unavailable before migration, the root uses the configured application locale and localized routes stay closed. Locale codes are matched case-insensitively with hyphens and underscores normalized. The resolved record sets HTML language and direction. Laravel uses APP_FALLBACK_LOCALE for missing translation keys. Public landing copy remains English until translated resources are provided. The console and authentication currently use their existing interface language.

The registry is queried on each public request and has no persistent cache to invalidate. The public switcher only shows active records. Changes leave localized content intact.

## Integration

CMS and UI Translations should store relationships against stable language IDs and use canonical codes for external URLs. Scope slug uniqueness to the owning content type and language. Store slug, publication state and SEO metadata on the specific translation. Reload all translation fields when the editor changes languages. Apply both language activation and publication checks before including content in public routes, navigation, canonicals, hreflang and sitemaps. Authorized editors may access inactive translations. Gate schema-dependent editors on their own migrations.

## Fonts Management

Verified Super Admins use Localization → Fonts to search the curated Google Fonts catalog, preview a language and assign body and heading families independently. The manifest in config/typography.php defines allowed family identifiers and primary writing system compatibility. Google Fonts CSS API availability was checked for the 13 families at normal weights 400 and 600. Script compatibility is curated metadata and does not certify complete glyph coverage or shaping. Urdu uses a dedicated Nastaliq candidate and Persian has a separate preview sample. Korean and Japanese may still require platform fallback for individual characters.

Assignments reference language IDs, use enumerated fallback stacks and can be reset to inherited typography. Inheritance uses the configured writing-system defaults described below. Deactivation rejects families with explicit assignments. Inactive languages remain editable without becoming public. CMS previews should resolve typography using the edited language ID and apply direction separately.

Public layouts receive typography only from the existing locale resolver. The application constructs a fixed-host CSS API URL with only the resolved family and weight pairs and deduplicates pairs in one stylesheet. The stylesheet loads asynchronously with display=swap. Local Geist and browser fallbacks remain available. Google receives the browser IP and standard request metadata when a remote font loads. Assign system typography and deactivate unassigned remote families when third-party requests are prohibited. No Google API key or runtime metadata fetch is needed. Registry reads use the database directly, so updates do not require cache clearing.

Apply the reviewed language migration before the typography migration. Back up both language and font settings before rollback. Reverting the typography migration drops its assignments and family state. After deployment verify authorization, inherited typography, an active RTL locale, and network requests for the selected families.

## Deployment

Back up the application database and verify restore access before a release. Run migrate:status against the intended connection. Review all pending migrations against the approved release scope. The language migration requires the existing administrative schema for audited writes. Apply the reviewed migrations during the release window, verify the registry has 30 initial entries and an active default, then check the Super Admin screen and public locale routes. Package the compiled Vite assets. Do not reset a deployed database. The language migration rollback removes its own registry and must not be used after downstream modules reference it without a reviewed data plan.

Typography 2.0 adds an additive style column with Normal as the default for existing assignments. Roboto offers Normal and Italic at the configured weights. Other curated families expose Normal. Deploy the style migration after the original typography migration, with a database backup. Older schemas continue to accept Normal assignments until the migration is applied.

## UI Translations

Verified Super Admins manage values in Localization → UI Translations. Stable group.key identifiers and English source strings are registered in config/ui_translations.php and inserted by additive migrations. Register each new key with an additive data migration and bind it explicitly in the relevant interface. Preserve existing values and never use rendered English sentences as identifiers. The initial bindings cover the public landing page.

Missing means no locale-specific value. Draft and Needs review are private. Translated publishes the saved value for active public locales. Reset removes the override and returns to the fallback chain. The existing locale resolver loads the published dictionary in one query per request. There is no persistent dictionary cache to invalidate. Resolution uses the active locale dictionary, Laravel translations and then the English source. Translation strings are escaped as plain text. Named placeholders must match the source token set. Use Laravel trans_choice for pluralized file-backed strings and locale-aware number and date formatters before substituting values. The editor does not yet manage plural variants.

Editors can access inactive languages, with direction and typography derived from Languages and Fonts. Store long-form CMS content and SEO translations in their owning modules. Back up the database and apply the Languages migration before UI Translations. The Fonts style migration is independent. An unavailable translation schema returns HTTP 503 in the console and falls back to the existing source on public pages. Verify a published translation and a draft in an active locale after deployment.

The Languages and Fonts entries appear inside the Admin Console for active, verified Super Admins with settings.manage. See [navigation contexts](components.md#navigation-contexts). The authorized installer records the initial Super Admin's email as verified during account creation.

As verified on 2026-11-14, the local database has the language and base typography migrations. Fonts style and UI Translations are pending. Normal font assignments work without the style column. Italic requires it, and UI Translations returns HTTP 503 until its schema exists. Verify the target independently and follow the [schema recovery procedure](deployment.md#schema-recovery).

## Homepage publishing

Open a language under Languages and select Homepage content. The editor shows the canonical English configuration beside editable text. Save draft writes separate values without replacing published copy. Preview shows the saved draft on a protected, uncached homepage. Publish applies all submitted fields in one transaction, checks the editor revision and uses canonical English for blank fields. Publishing does not activate an inactive language. The list counts only published, nonempty values for registered homepage keys.

The public selector saves an intentional choice in the existing session. Opening a localized URL leaves that preference intact. Localized responses are private and vary by Accept-Language and Cookie. The public homepage uses the published dictionary and immutable English fallback.

The legacy UI Translations URL redirects to Languages when the new editor schema is available. Existing filtered links and writes retain their route and authorization. The additive homepage draft migration requires the UI Translations tables. Apply both during a controlled release on an installation missing those tables. Older published values remain in place.

Run isolated browser QA for browser preferences, manual switching, direction, draft preview, publishing and stale-editor recovery.

## Multiscript typography

Fonts is available from Admin Console and from the selected language. The locale registry determines direction independently of typography. An explicit body or heading assignment takes precedence over a saved writing-system default and the compatible catalog default. Reset removes the language assignment. System fallback remains readable while remote assets load. The catalog and mappings live in [typography configuration](../config/typography.php).

Latin uses local Geist Sans with Inter available as an alternative. Arabic, Persian and Urdu default to Noto Sans Arabic. The Arabic catalog includes Naskh and Kufi, while Urdu offers Nastaliq. Hebrew uses its own Noto Sans family. Cyrillic and Greek use Noto Sans, while Indic, Thai and East Asian languages use their dedicated script or regional families. Geist Mono remains the technical font. Use LTR isolation for addresses and tokens in RTL previews.

Preview changes are scoped to the sample until Save. Local Geist is bundled by Vite. The existing fixed Google Fonts endpoint loads only selected remote families and weights with display=swap. Font assets retain their upstream licenses. Browser QA should verify actual glyph coverage and clipping, particularly for Nastaliq.

The additive font_script_defaults table stores a writing system key and a catalog family reference. Deploy its migration from a verified backup after checking the target schema. The existing style migration remains separate. Existing locale assignments remain intact. The new default editor is unavailable until the table exists. Font writes use verified settings.manage access, server-side compatibility checks, and audit events.


## Typography Studio

Open Typography Studio at /admin/fonts. Languages opens by default. Use Overview for counts and migration readiness and Font Library to compare active, compatible fonts using the same selected-language sample. Search and script filters still apply. Cards mark the script default, while Family settings contains provider, weights, assignment status and activation controls. The selected locale survives section navigation. The live sample reflects draft settings and supports narrow width and independent light and dark previews. Save applies the selected setting and reset removes the role override. The registered locale provides direction.

The Studio uses the same permissions, catalog and tables. Local migration status showed pending 2026_11_13_180907_add_style_to_font_assignments and 2026_11_14_000002_create_font_script_defaults. Apply both through [controlled schema recovery](deployment.md#schema-recovery) after verifying the backup and obtaining approval. Browser checks on isolated SQLite confirmed font-face loading with English, Turkish, Arabic, Hebrew, Persian and Urdu samples at 390 px. This does not establish complete glyph coverage or verify every font and weight.
