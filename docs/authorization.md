# Authorization

The installer provisions the first Super Admin through the authorized finalization operation. The users.is_super_admin flag defaults to false and is excluded from mass assignment. User::isSuperAdmin() backs the access-admin Gate on /admin. Registration rejects submitted role fields. Later resources still need their own Gates or Policies. The workspace_installation record preserves the initial account reference. The remaining permissions below are planned.

| Actor | Intended access |
| --- | --- |
| Guest | Public routes and applicable guest account flows |
| Normal user | Own profile and authorized account resources, including owned media |
| Super Admin | Explicitly granted administration and global branding operations |

Use middleware for broad access requirements, Gates for global abilities, and model Policies for ownership-sensitive records. Authorize reads and writes at the server boundary. Scope list queries before pagination and authorize route-bound records before mutation or download. Super Admin privileges require explicit ability checks. Guard role fields from request assignment and audit privilege and branding changes.

Test guest requests, same-owner and other-owner access, and each privileged action with and without the required ability. See [Authentication](authentication.md) and [Media Library](media-library.md).
