# Architecture

Requests enter [public/index.php](../public/index.php), load [bootstrap/app.php](../bootstrap/app.php), pass through Laravel middleware, and reach [web routes](../routes/web.php). GET / renders the welcome Blade view. Laravel registers GET /up for application health. Bootstrap selects JSON exception responses for requests under api/* or requests expecting JSON. Package and framework routes may appear in the route listing. Inspect them with php artisan route:list before relying on the public surface.

The foundation uses the default App namespace, Laravel configuration and migration paths, Blade views, and Vite entry points. The following ownership map describes planned modules.

| Module | Owns | Expected dependencies |
| --- | --- | --- |
| Installation | Bootstrap verification and first administrator provisioning | Database, configuration, account provisioning |
| Authentication | Identity, sessions, verification and recovery | User model and framework auth |
| Dashboard | Authenticated landing page and read summaries | Account and feature read operations |
| Profile | Self-service account settings | Authentication and permitted media references |
| Media Library | Uploads, ownership, usage and storage lifecycle | Identity and Filesystem |
| Administration | Privileged operations and global settings | Policies and operations of owning modules |
| Email Infrastructure | Inbox and message lifecycle, ingestion and retention | Ownership and storage contracts |
| CMS | Content authoring and publishing | Authorization and media references |
| Subscriptions | Plans, subscription state and entitlements | Account identity and billing integration |

Start with a named route, focused use case, and necessary tests and schema. Move logic to feature namespaces when that clarifies ownership. Keep writes with the owner and pass cross-module data through explicit operation inputs or read contracts. Keep account and infrastructure dependencies independent of their callers. See [agent architecture rules](../agents/architecture.md) and [database ownership](database.md).
