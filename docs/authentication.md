# Authentication

**Implemented foundation.** The starter User model and account-related migrations exist. The repository has no project login, registration, recovery, verification, or dashboard routes.

**Planned routes.** Finalize named routes and middleware when implementing the module.

| Flow | Proposed endpoints | Access and behavior |
| --- | --- | --- |
| Registration | GET and POST /register | Guest and throttled. Assign ordinary privileges and initiate verification. |
| Login | GET and POST /login | Guest and throttled. Validate with Laravel, regenerate the session ID, and redirect to an authorized intended page or /dashboard. |
| Logout | POST /logout | Authenticated with CSRF. Invalidate the session and regenerate the CSRF token. |
| Reset password | GET and POST /forgot-password, GET /reset-password/{token}, POST /reset-password | Guest. Use the password broker, expiring tokens, and neutral recovery responses. |
| Email verification | GET /email/verify, GET /email/verify/{id}/{hash}, POST /email/verification-notification | Authenticated. Require a valid signed link for verification and throttle resends. |
| Password confirmation | GET and POST /confirm-password | Authenticated. Require recent confirmation for sensitive actions. |

Use Laravel hashing, retain secure cookies, and rate-limit attempts by suitable account and client identifiers. Allow password managers and paste. Create the first Super Admin through the [installer](installation.md). Use the normal authentication mechanism for that account and enforce separate [authorization](authorization.md) on privileged requests.
