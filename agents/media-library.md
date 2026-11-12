# Media Library

Implement the future /media-library route behind authentication and per-record policies. Scope list, view, upload, update, and delete to the authorized owner. Treat global branding as a separate ability restricted to Super Admins, including logo replacement and deletion.

Store files using Laravel Filesystem and keep private originals outside the public document root. Validate byte size, dimensions, decoded image content, and MIME type. Reject SVG uploads unless a vetted, explicitly authorized sanitization and serving design is introduced. Record metadata and usage references. Block deletion of referenced files until authorized replacement or detachment succeeds. Follow [storage and lifecycle rules](../docs/media-library.md).
