# Installation

Prompt 04 implements database verification at GET and POST /install. The first Super Admin screen and finalization belong to Prompt 05. The current success state is /install#connection-verified. /install/admin has no handler yet.

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

POST requires the session CSRF token, accepts an 8 KiB URL-encoded form, and permits five attempts per network peer per minute. The verifier creates a short-timeout PDO connection, selects the supplied database, runs SELECT 1, and rejects databases where SHOW FULL TABLES returns a table or view. Use a dedicated new database whose contents are visible to the account. Only read queries run. Verification never writes to .env, changes the default connection, runs migrations, or creates an administrator.

## State and Prompt 05 contract

| State | Condition and transition |
| --- | --- |
| Closed | No valid enable.json, request outside the gate, or the installed marker exists |
| Ready | Authorized request with no active lease for its session |
| Verifying | POST claims the 15-minute lease and regenerates the session ID on first claim. A new POST clears prior verification before validation. |
| Verified | The matching attempt succeeds and saves encrypted connection settings. The session ID rotates again and the page announces success. |
| Expired | The server rejects access to expired state. A fresh attempt can acquire the lease. |
| Installed | Reserved for Prompt 05. The durable private installed marker takes priority over enable.json. |

The lease is scoped to the exact enable.json contents and to an owner identifier in the encrypted server-side session. Only the matching session can obtain verified settings. InstallerState::verifiedDatabase($request) returns a verified host, port, database, username, and password for the current owner or null. Call it only behind the installer gate with an initialized installer session. Keep the returned settings server-side.

Prompt 05 must protect GET and POST /install/admin with the same gate and require current verification. Before a privileged write, extend the state service to recheck the lease and hold the same exclusive filesystem lock across the controlled finalization sequence. Recheck the target schema, persist configuration safely without replacing unrelated settings or an existing APP_KEY, run required migrations with recoverable checkpoints, provision the first Super Admin once, and record durable completion. Clear the lease and temporary installer key only after finalization and retire enable.json. Redirect to /login and then /dashboard when those routes exist.

## Recovery

Remove enable.json to close the installer. Preserve the installed marker across updates and backups. If .env is lost on an existing deployment, keep the installer closed and restore the original APP_KEY, environment settings, and matching database access from operator-controlled backups. Treat missing or uncertain installation evidence as a recovery operation. Enable a first installation only after independently confirming that the deployment and target database are new. Never recreate the first Super Admin to recover configuration. See [Security](security.md).
