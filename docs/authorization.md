# Workspace authorization

The installer-created Super Admin retains authority through the existing is_super_admin flag. The added role field stores member and admin assignments. WorkspaceRole resolves the effective role and registers these permissions through Laravel Gates.

| Role | Permissions |
| --- | --- |
| user | dashboard.view |
| admin | dashboard.view, admin.access, users.view, settings.view |
| super_admin | All admin permissions plus users.manage, roles.manage, settings.manage, audit.view |

Every console route requires an active authenticated account, verified email, and the relevant Gate. User mutations also use UserPolicy. Public signup creates active members. Privileged fields are excluded from mass assignment. Disabled accounts cannot sign in and lose access on the next protected request.

The Super Admin can change roles and activation with explicit confirmation. A transaction writes the singleton workspace settings row to serialize competing mutations, then reloads the actor and target before authorizing the operation. It protects the last active Super Admin. There is no account deletion endpoint. The installation record retains its original administrator reference.

The console exposes /admin, /admin/users, /admin/users/{user}, /admin/roles, /admin/settings and /admin/audit-logs. Role definitions are fixed in code. Settings persist the console display name. Administrative audit rows record actor and target IDs, action, timestamp and allowlisted metadata. They have no public write route.

Run the additive workspace administration migration in a controlled release before opening authenticated routes on existing installations. New installs apply it through the existing installer. See [Database](database.md) and [Authentication](authentication.md).
