# Temp Mail SaaS

Temp Mail is a planned service for temporary email inboxes. Phase 1 establishes a Laravel foundation, development governance, and reusable UI before the installer and account modules. Email delivery and retention, content management, and subscriptions belong to later modules.

| Status | Scope |
| --- | --- |
| Implemented | Laravel bootstrap, welcome and health routes, starter migrations and model, Vite and Tailwind build, Alpine theme preference, shared Blade UI catalog, gated database verification and Super Admin installation, and repository checks |
| Planned | Authentication, dashboard, profile, Media Library, administration, email infrastructure, CMS, and subscriptions |
| Under development | Reserve this status for work with an identifiable implementation and describe the usable subset |
| Deprecated | Reserve this status for an existing capability marked for retirement and give a migration path |

Use Laravel 13 on the PHP 8.5 target, Blade, Alpine 3, Tailwind 4, Vite, and MySQL or MariaDB. The Composer manifest expresses a broader PHP constraint than the PHP version used by CI. Check the lockfiles and platform requirements before changing dependencies. Read [Architecture](architecture.md), [Deployment](deployment.md), and [Decisions](decisions.md).
