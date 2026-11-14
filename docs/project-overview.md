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

## Implementation priorities

1. **Database schema recovery.** Restore local Fonts style and UI Translations compatibility through [controlled recovery](deployment.md#schema-recovery).
2. **Media Library UX.** Complete and verify [Upload → Review/Edit → Return to Library](media-library.md#upload-edit-and-return).
3. **Picker, Branding and Trash.** Verify selection and persistence end to end and implement authorized Trash thumbnails.
4. **Account email.** Configure supported secure delivery and verify reset and verification.
5. **Core Temp Mail.** Implement temporary address creation, inbox synchronization and message reading.

### Additional status detail

The installer and account authentication are implemented with isolated regression coverage. Live target deployment and real account mail remain partially verified. Trash move, restore and permanent deletion are implemented with automated coverage. Trash displays placeholders. Authorized thumbnail delivery is unimplemented. Roles & Permissions is implemented as a read-only matrix. Its [Users navigation target](components.md#users-navigation-target) is approved and unimplemented.

### Core Temp Mail scope

Temporary address generation and inbox/IMAP synchronization are **Planned**. The core journey generates and displays a temporary address, receives messages, renders messages safely, and enforces access and expiry with loading, empty, error and expired states. The approved direction is hosting mailboxes with IMAP, multiple system and custom domains, simple domain CRUD and bounded shared-hosting scheduled synchronization. Automated DNS verification and MX/TXT management are optional outside this scope. Dashboard currently provides account and workspace functions.
