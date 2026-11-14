# Security

The foundation uses Laravel's web middleware and production error handling. Session defaults in [config/session.php](../config/session.php) use encrypted file storage, HttpOnly cookies, SameSite=Lax, and Secure cookies when APP_ENV is production. The production debug guard lives in [config/app.php](../config/app.php). Keep browser mutations behind CSRF and use HTTPS in production.

Store .env, APP_KEY, credentials, and private files outside the web root. Back up the key and configuration securely. Trust only provider-documented proxy addresses and forwarded headers when TLS terminates upstream. Reject unexpected production hosts at the host boundary and validate the configured host before enabling future installer operations.

The database installer uses a private operator gate, raw network peer and Host checks, session CSRF tokens, a per-peer rate limit, and an encrypted, expiring server-side lease. Finalization also uses a filesystem lock and an engine-specific database lock or the private SQLite file lock, records a database operation ID, and closes the gate after durable completion. Installation audit messages contain no submitted values. Other privileged flows require explicit server-side authorization and suitable throttles. Escape template output and treat message HTML and uploaded content as untrusted. Use the [installer recovery model](installation.md) and [media validation](media-library.md) for these entry points. Log security-relevant actions with actor, target, outcome, and request ID. Keep raw credentials, token values, cookies, and private content out of logs and responses. Restrict audit access and define retention as the relevant features are implemented.

Verify session cookies, authorization denials, throttles, safe errors, and private file isolation at the relevant boundaries. See [Authorization](authorization.md) and [Deployment](deployment.md).

## HTTP errors and deployment diagnostics

The shared error view handles 403, 404, 419, 429, 500 and 503 without querying the database or loading the authenticated layout. It uses inline Zinc styling and the browser theme preference. When Blade rendering fails, the handler falls back to plain text. JSON requests receive a generic message. Retry-After and other exception headers are preserved. Laravel still handles authentication and validation responses.

HTTP exception reports include the exception class, route name and a coarse failure category. They exclude exception text, SQL and request context. Database connection and recognizable missing-schema failures return 503. For an affected administrative route, review the release migration status and apply pending migrations through the controlled deployment process. Ordinary requests never apply migrations.

Responses include nosniff, no-referrer, a restricted Permissions-Policy and CSP frame restrictions. The media picker permits same-origin framing. HSTS requires production, an HTTPS APP_URL and a secure request under Laravel's existing proxy configuration. CSP leaves script execution unchanged for the current Alpine and Vite integration.

Boost browser log collection is disabled in application configuration in every environment. The package watcher captures full URLs and arbitrary console arguments without application-specific redaction. Local developers can use browser DevTools and normal Laravel logs. Deploy with Composer dev dependencies excluded.

Public branding serves only the PNG preview assigned to a known slot. Draft URLs remain subject to media ownership. The branding transaction checks active media and preview availability before publishing.
