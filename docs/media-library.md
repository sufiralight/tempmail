# Media Library

The authenticated library lives at /media. GET and POST list and upload, GET /picker opens the reusable picker, POST /bulk-delete moves an authorized selection to Trash, and GET, PATCH and DELETE /{media} show, edit and move one image to Trash. GET /{media}/file serves the private original or its preview after ownership authorization. All route names use the media. prefix. The prior /media-library GET paths redirect after the same authorization checks. Legacy write paths continue to invoke the canonical handlers with their original methods. /admin/media is a read-only console for verified Super Admin accounts with media.audit.

The local media disk stores original bytes and PNG previews under storage/app/media outside the document root. Keep this directory private and include it in backups. PHP requires GD and Fileinfo for uploads. Set upload_max_filesize to at least MEDIA_MAX_KIB and post_max_size above that limit plus form overhead. The application defaults to 5120 KiB and caps configuration at 10240 KiB. The service accepts matching JPEG, PNG, WebP and GIF extensions and signatures, checks dimensions and pixel count before decoding, and rejects SVG. JPEG previews use EXIF orientation when available. Animated originals are preserved and previews use a single frame.

The picker supports browsing, filtering and uploading within the same authenticated owner scope. Pass name, label, and optional value and preview-url to x-ui.media-picker. The parent receives an ID and must revalidate ownership on submission. Upload progress uses measured bytes where supported. Cancellation stops remaining uploads and the active request, but a server-side operation may already have committed.

Only verified accounts with settings.manage can change /admin/settings/branding. A slot may reference only an owned image with a generated preview. Public branding endpoints serve re-encoded previews for the selected slots. Unconfigured logos use the built-in mark. The shared layout uses the dark logo in the effective dark theme.

All Media and Trash support search, type and sort filters, grid/list display, and server-side pages of 20, 40, 80 or 100 images. Selection is limited to the current page. The picker and the read-only administration console show active images only.

GET /media/trash lists the signed-in owner's deleted images. POST /media/trash/restore restores one or more selected items after verifying the original and any expected preview exist. DELETE /media/trash permanently deletes one or more selected items after confirmation. Each batch validates up to 100 distinct IDs and checks ownership and branding references before changing anything. Branding changes and media lifecycle operations serialize on the same workspace row.

Moving to Trash keeps the files and metadata private. Permanent deletion commits a purge marker before touching storage. If cleanup or the database operation fails, the affected record remains in Trash with a retry action. Restore is blocked once permanent deletion starts. Bulk feedback reports the number completed when cleanup is partial. Pending deletions require an explicit retry from Trash. Recoverable Trash has no automatic purge.

For an existing deployment, back up the database and private media, pause web traffic and the old scheduler, deploy the release, and run `php artisan migrate --force` against the intended connection before reopening traffic. The additive migration marks previously soft-deleted rows as pending permanent deletion without removing files or records. Those rows came from the old immediate-deletion workflow and are not eligible for restore. Do not roll back to a release whose scheduler purges all soft-deleted media once recoverable Trash is in use.

The upload form accepts multiple files and sends each image in a separate CSRF-protected request. It shows file validation and size errors per image. A session or CSRF failure stops the batch and asks the user to refresh or sign in before retrying. Authentication, permission, rate limit, storage and network failures also stop the batch. The interface keeps confirmed successes visible and identifies files that were never sent. It never replays uploads automatically.

Assigned profile photos are protected from Trash and permanent deletion. Change or remove the profile photo before moving its image. Avatar assignment and media lifecycle changes share the workspace database lock.

Collection thumbnails use generated PNG previews. Images without one show a placeholder in the library and are excluded from the image picker. The original remains available from authorized image details.

After confirmed uploads, the browser refreshes the collection and count using the current URL. The upload dialog retains per-file results and links to uploaded items even when filters or paging exclude them. The image picker refreshes its results and offers a link to the newest images when needed. Bulk actions return to the first page with the existing search, filters, sort and view preserved.

Image details stores optional title, ALT text, caption and description on the same media record. The picker emits a media-selected event containing the existing ID and private preview URL plus a metadata object. Treat metadata as plain text and revalidate ownership on save. Apply pending migrations before opening traffic. A schema-category 503 on media.index can occur when the listing requests purge_requested_at before migration 000007 is applied, including during the GET refresh after an upload.

A single successful library upload opens Image Details after refreshing the collection. Batch uploads keep per-file results and edit links. Duplicate display names are allocated inside the workspace transaction, scoped to the owner and including Trash. Original filenames are stored separately for new uploads. Older records use their existing filename. UUID storage paths stay unchanged. Apply migration 000010 during controlled deployment before accepting uploads. The default metadata stays on the media record. Future localized values can use a translation table keyed by media ID and locale.

## Media integration verification

- **Profile picker:** Automated tests verify assigning an existing owned image and rejecting cross-owner and trashed images. The complete browser picker selection remains unverified.
- **Avatar persistence:** The reference is verified by a fresh database read and subsequent authenticated image requests. Browser reload after selecting via the picker remains unverified.
- **Branding picker:** Tests verify the chooser renders, saving an eligible reference, public preview delivery and reference protection. Browser selection and confirmation remain unverified.
- **Trash thumbnails:** Trash currently displays a placeholder. Authorized Trash thumbnail delivery is **PLANNED — NOT IMPLEMENTED**. Active private preview routes retain their existing state checks.
