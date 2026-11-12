# Media Library

**Planned.** The authenticated /media-library module will manage user-owned images. Use [authorization policies](authorization.md) for list, view, upload, replacement, and deletion. Scope user actions to owned records. Global application logos require an explicit Super Admin ability and audit entry.

Use Laravel Filesystem with generated storage keys and private originals. Serve private downloads through an authorized endpoint. Publish only approved public derivatives or branding assets. Record owner, disk and key, verified MIME type, byte size, image dimensions, and usage references. Treat the supplied filename as display metadata and escape it.

Define an image allowlist and size and dimension limits in the feature. Validate MIME and decode the contents. Reject SVG uploads by default. Test valid files, malformed images, mismatched extensions, and oversized files. Keep processing bounded for shared hosting.

Check references before deletion and coordinate reference creation with deletion so a file cannot become newly referenced during cleanup. Reject deletion while references exist, or require an authorized replacement or detachment operation. Use a recoverable workflow for database and filesystem changes and test partial storage failures. See [Security](security.md) and [Shared hosting](../agents/shared-hosting.md).
