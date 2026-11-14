# Temp Mail SaaS

Temp Mail SaaS implements the installer, account workspace and administration foundation. Temporary inbox delivery and retention are planned.

| Module | Status | Evidence or release gap |
| --- | --- | --- |
| Dashboard and Profile | Implemented and verified | Authenticated navigation, account states and profile writes are covered by feature tests. |
| Media Library | Implemented and verified | Automated tests cover ownership, SEO metadata, pagination, Trash and Restore. See [media integration gaps](media-library.md#media-integration-verification). |
| Admin Overview, Users, Roles & Permissions, Settings, Audit Logs and Media Audit | Implemented and verified | Authorization and administration feature tests cover access and selected workflows. |
| Founder Super Admin | Implemented and verified | Installer identity and role and status protection are covered by isolated tests. See [authorization](authorization.md#founder-super-admin). |
| Global Branding | Implemented but not fully verified | Reference management and public preview are covered by tests. Browser picker confirmation remains unverified. |
| Languages | Implemented and verified | Registry, default language and public locale boundaries are covered by feature tests. |
| Fonts | Partially available locally | Base typography is installed locally. Italic assignment requires the pending style migration. |
| UI Translations | Blocked by local schema | Implemented and covered by isolated tests. The local console returns 503 until its migration is applied. |
| Account email delivery | Blocked by local configuration | Direct mail flow exists. The local log transport cannot deliver account emails and real delivery is unverified. |
| Temporary inbox infrastructure, CMS and subscriptions | Planned | Future modules. |

Verification applies to the tested scope. Production gates include target schema, private storage, mail delivery, responsiveness and accessibility.

Use Laravel 13 and the PHP 8.5 target with Blade, Alpine, Tailwind CSS 4 and Vite. Build assets locally or in CI for Apache/cPanel. See [Architecture](architecture.md), [Deployment](deployment.md), [Decisions](decisions.md) and [Localization](localization.md).
