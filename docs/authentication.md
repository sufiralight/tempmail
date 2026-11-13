# Authentication

Accounts are available after successful installation. The durable installer marker, active private environment file and application key are checked before the web session starts. The installer redirects through /setup-complete to the named login route with a success message. The administrator signs in using the same form as other members.

| Flow | Routes | Behavior |
| --- | --- | --- |
| Login | GET and POST /login | Guest only. Laravel verifies credentials and rotates the session. Invalid accounts and wrong passwords get the same message. |
| Registration | GET and POST /register | Guest only. Normalize email, enforce its database uniqueness and a confirmed password of at least 12 characters, then sign in the new ordinary member. |
| Logout | POST /logout | Authenticated with session CSRF token. Clear the guard, invalidate the session, and rotate the CSRF token. |
| Recovery | GET and POST /forgot-password, GET /reset-password/{token}, POST /reset-password | Guest only. Use Laravel's password broker, a neutral request response, an expiring single-use token, and a delivery URL built from APP_URL. |
| Workspace | GET /dashboard | Minimal authenticated landing page. |
| Administration | GET /admin | Console requiring the admin.access Gate and verified email. |

The login limiter permits five attempts per normalized email and network peer per minute and twenty attempts per peer per minute. Registration and password recovery have their own per-peer limits. Authentication writes require the session CSRF token. The redirect after sign-in uses a fixed internal destination. Only an account with console access can continue to an intended /admin route.

Laravel encrypts the session cookie and uses file-backed sessions by default. Preserve HttpOnly, SameSite and Secure on production HTTPS. No remember-me UI is provided. Authentication session middleware checks password hash changes on protected routes. Password fields use the shared accessible toggle and password manager autocomplete attributes.

Configure a direct delivery mailer such as SMTP and a canonical HTTPS APP_URL for real reset email. The log, array and composite mailers are excluded from reset delivery. Future password confirmation and account pages can reuse these routes and guards. See [Authorization](authorization.md) and [Installation](installation.md).

## Email verification and password policy

Registration sends Laravel's signed verification notification using the configured APP_URL. Links expire after 60 minutes by default and require the matching signed-in account. The workspace and logout remain available before verification. Administration requires an active administrative account and verified email. Initial administrators can request their first link from the workspace. Resends are limited to three per account per minute. Configure a direct mail transport for both account email flows.

Password creation uses AccountPassword for a 12-character minimum, a 1024-character maximum, the bcrypt 72-byte limit when active, and local rejection of a small list of common passwords, simple repeats, and sequences. The feedback component is a local estimate based on length and predictable patterns. It sends no password data and does not check a breach database. Reuse x-ui.password-strength with the IDs of the two shared password fields for future password changes. The server policy applies regardless of the displayed score.

Password recovery serializes broker requests and resets per normalized account using cache locks. The default database broker consumes the token in the same transaction as the password change. Recovery rotates the remember token and invalidates the requesting session. Protected sessions check the password hash on later requests.
