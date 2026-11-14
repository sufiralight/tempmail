# Workspace authorization

WorkspaceRole resolves the effective role from the is_super_admin flag and the stored role. Laravel Gates enforce the permissions below.

| Role | Permissions |
| --- | --- |
| Member (user) | dashboard.view |
| Admin (admin) | dashboard.view, admin.access, users.view, settings.view |
| Super Admin (super_admin) | All admin permissions plus users.manage, roles.manage, settings.manage, audit.view, media.audit |

Every console route requires an active authenticated account, verified email, and the relevant Gate. User mutations also use UserPolicy. Public signup creates active members. Privileged fields are excluded from mass assignment. Disabled accounts cannot sign in and lose access on the next protected request.

The Super Admin can change roles and activation with explicit confirmation. A transaction writes the singleton workspace settings row to serialize competing mutations, then reloads the actor and target before authorizing the operation. It protects the last active Super Admin. There is no account deletion endpoint. The installation record retains its original administrator reference.

The console exposes /admin, /admin/users, /admin/users/{user}, /admin/roles, /admin/settings and /admin/audit-logs. Role definitions are fixed in code. Settings persist the console display name. Administrative audit rows record actor and target IDs, action, timestamp and allowlisted metadata. They have no public write route.

Run the additive workspace administration migration in a controlled release before opening authenticated routes on existing installations. New installs apply it through the existing installer. See [Database](database.md) and [Authentication](authentication.md).

The Roles & permissions page derives its matrix from WorkspaceRole and counts assigned accounts, including inactive accounts, with one grouped database query. The Languages, Fonts and UI Translations rows describe routes guarded by settings.manage. Personal profile and media operations retain their ownership and state checks. Assign roles through Users Management.

## Founder Super Admin

**Implemented.** The isolated installer and authorization tests verify the original administrator reference and protected role and activation operations.

The singleton installation record identifies the Founder through initial_admin_id. Finalization writes this record and the first Super Admin in the same transaction. Founder is a protected identity within the Super Admin role. Regular Super Admins retain existing role management authority. Admins have limited console access and Members use the workspace.

Role and activation changes acquire the shared workspace lock, reload the actor and target, and reject founder demotion or deactivation with an audit event. The installation foreign key protects hard deletion and the application has no account deletion or suspension endpoint. Any future lifecycle operation must enforce founder protection under the same lock. Ordinary user management provides no founder reassignment action. Last active Super Admin protection applies independently. Founder accounts use normal authentication and session controls.

If the installation record is missing or its provenance is uncertain, open an operator-controlled recovery window. Verify the deployment database, take a recoverable backup and validate the original installation identity against trusted backups and installation records. Never infer it from email, username, display name or the lowest user ID. Restore the verified singleton and original administrator reference from that evidence through controlled database recovery. If the original identity cannot be verified, leave founder unassigned and retain last active Super Admin protection. Escalate to the deployment operator for identity recovery.
