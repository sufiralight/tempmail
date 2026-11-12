# Installation

**Planned for Prompt 04.** The current foundation serves the welcome page and health route with file-backed sessions and cache. Follow [Deployment](deployment.md) for today's setup. Local developer key generation is a separate workflow from the future browser installer.

1. GET /install opens only for an authorized deployment operator. Submit database settings via a CSRF-protected, throttled POST. Verify the connection, required capabilities, and installation state before proceeding. For local development support localhost and 127.0.0.1 and allow an empty local database password.
2. GET /install/admin opens only within the verified installation session. Collect the first Super Admin details and hash the password using Laravel. Keep role assignment server-side.
3. Under an exclusive installation lock, generate a new application key for a confirmed fresh installation, stage private configuration, run required migrations, provision the administrator once, and mark completion only after required writes succeed. Reconcile the migration ledger, admin state, and private checkpoint before resuming. MySQL and MariaDB DDL may commit independently of application transactions.
4. Close installer routes, clear installation session data, and redirect to /login. After login, redirect to /dashboard.

On shared hosting, require a host-controlled maintenance gate that restricts access to the intended operator before the installer can open. A cPanel operator can provision private enablement through File Manager and restrict access at the host layer. Validate a configured production host over HTTPS and trust forwarded metadata only from configured proxies. A marker or a missing file alone grants no remote authority. The application flow requires neither a setup credential form nor manual key generation.

## Recovery

Treat missing .env or uncertain completion as a closed recovery state. For an existing installation, keep the public installer closed and restore private configuration, the original APP_KEY, and matching database access from operator-controlled backups. Confirm installation state through trusted access and reconcile partial changes before reopening traffic. If state cannot be established, stop and require operator intervention. Preserve existing accounts and encrypted data. See [security](security.md) and [database operations](database.md).
