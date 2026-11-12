# Installation

The installer verifies the database at GET and POST /install, then creates the first Super Admin at GET and POST /install/admin. Both steps require the same private operator gate. The verified database belongs to one encrypted installer session. Successful finalization redirects through /setup-complete and then to the named login route when that route is available.

## Open a new installation

Create storage/app/private/installer with permissions restricted to the deployment account and PHP. Use mode 0700 on Unix where both run as the same account. On Windows restrict the directory ACL to the local operator and the PHP account. Keep the directory above the public document root and exclude it from release uploads and source control.

On a direct localhost development server, create enable.json in that directory with this content.

```json
{"mode":"local"}
```

Run the local Laravel server and open http://localhost:8000/install or http://127.0.0.1:8000/install. The HTTP peer and Host must both be loopback. Local Apache must also bind directly to loopback and run with APP_ENV=local. The installer accepts either hostname regardless of APP_URL. Use one hostname through the entire flow because session cookies are host-only.

For a fresh production deployment, use cPanel File Manager or an equivalent authenticated host control panel to create enable.json privately. Replace these example values with the real domain, the operator's dedicated client IP, and the database endpoint approved by the hosting provider.

```json
{
  "mode": "production",
  "host": "mail.example.com",
  "operator_ip": "203.0.113.17",
  "database_host": "db.example.com",
  "database_port": 3306
}
```

Use direct HTTPS and require the web server to supply the real network peer as REMOTE_ADDR. Apply an Apache or hosting access restriction when the operator shares an egress IP or the host cannot guarantee a dedicated client address. The installer refuses Forwarded and X-Forwarded-* headers. Leave the gate closed on deployments that cannot provide the required direct HTTPS and peer boundary. Never allowlist a shared proxy IP. Prepare dependencies and compiled assets according to [Deployment](deployment.md).

The authorized operator is redirected from / to /install. Requests outside the gate cannot reach either installer action. A genuinely fresh gated deployment needs no manual key generation. The application generates an installer-only key in the private directory and uses encrypted Laravel file sessions with a distinct host-only cookie scoped to /install.

## Verify the database

Create an empty MySQL or MariaDB database and a dedicated account for it using the provider panel. Enter the TCP host, port, database name, username, and password. Local localhost maps to 127.0.0.1 for TCP and accepts an empty password. Production requires a password and the endpoint from enable.json. See [Database](database.md).

POST requires the session CSRF token, accepts an 8 KiB URL-encoded form, and permits five attempts per network peer per minute. The verifier creates a short-timeout PDO connection, selects the supplied database, runs SELECT 1, and rejects databases where SHOW FULL TABLES returns a table or view. Use a dedicated new database whose contents are visible to the account. Only read queries run. This verification step is read-only. Finalization starts only after the administrator form is submitted.

## Finalize workspace setup

After verification, enter the administrator's full name, email address, and a confirmed password of at least 12 characters. The application normalizes the email, rejects supplied role fields, hashes the password through Laravel, and assigns the initial privilege server-side. The users table has a unique email constraint. A singleton workspace_installation record binds the initial account to the operation and records the database commit.

The installer holds an exclusive private filesystem lock and a MySQL advisory lock for the verified schema. Under the locks it rechecks authorization and requires an empty target for a new operation. It runs Laravel migrations through an isolated named connection. The initial account and the database installation record commit in a single transaction. A database account must be able to inspect and migrate its dedicated schema.

Finalization prepares .env before DDL and replaces it only after the database transaction commits. Existing valid application keys are preserved. A fresh installation generates a key automatically. Installer sessions use a separate temporary key. The writer checks that unrelated settings survive a Dotenv round trip, escapes credentials, and atomically replaces .env with a private sibling file. On Unix the new file uses mode 0600. Windows requires a restricted inherited ACL on the application directory. Dotenv metavalues such as a literal password of null remain literal for installer-managed MySQL credentials.

Use an uncached configuration for installation. The writer refuses conflicting process-level environment overrides and a selected environment file other than .env. Where applicable, have the hosting operator align overrides, use the standard .env, and deploy without bootstrap/cache/config.php. The PHP account needs write access to the private installer directory and the application directory above public/ during finalization. After completion, restore the application's normal filesystem permissions.

## State and recovery

| State | Condition and transition |
| --- | --- |
| Closed | No valid enable.json, request outside the gate, or durable installed marker exists |
| Ready | Authorized operator can verify a dedicated empty database |
| Verified | The active 15-minute lease holds encrypted settings. Only the owning session can enter the administrator step. |
| Migrating | A private checkpoint exists before DDL. Further database verification is blocked so it cannot replace the target. |
| Account created | The initial administrator and matching operation ID committed in the database. The installer writes .env and then the durable installed marker. |
| Installed | Both installer routes are closed. The lease, bootstrap key, gate and checkpoint are removed, and the installer session is invalidated. |

A browser retry is safe while the verified lease is active if the database contains the exact operation ID and the administrator supplies the same email and password. This completes a failed environment write or marker write without creating a second account. Keep the deployment gate in place until finalization succeeds.

If DDL was interrupted before the database transaction committed, or if the lease expired, browser finalization stops. Keep the private files and affected database as evidence. Through the authenticated hosting panel, close the gate and have the operator inspect the migration ledger, application schema, and private checkpoint. Restore from an operator-controlled backup or reconcile the original installation in a protected maintenance window. Treat any orphaned .env.install-* sibling as a secret and remove it after reconciliation. A genuinely new attempt requires a separate empty database and a clean deployment state after the operator has resolved and archived the failed operation. The installer never runs destructive migrations or resets a partial database automatically.

If .env is missing on a completed deployment, keep the installed marker and restore the original APP_KEY, private settings and database access from backups. Preserve the marker across releases. Restoring the original key matters for previously encrypted data. Do not recreate the first administrator to recover configuration. See [Security](security.md) and [Database](database.md).

## Prompt 06 integration

Provide the normal named login route. /setup-complete forwards to it with a success message, using the normal application session. Require a completed installation before enabling account authentication. The installer never signs the administrator in. Use User::isSuperAdmin() for authorization decisions and keep is_super_admin outside normal registration and profile assignment. The login flow owns redirection to /dashboard.
