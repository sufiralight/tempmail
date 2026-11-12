# Security

The foundation uses Laravel's web middleware and production error handling. Session defaults in [config/session.php](../config/session.php) use encrypted file storage, HttpOnly cookies, SameSite=Lax, and Secure cookies when APP_ENV is production. The production debug guard lives in [config/app.php](../config/app.php). Keep browser mutations behind CSRF and use HTTPS in production.

Store .env, APP_KEY, credentials, and private files outside the web root. Back up the key and configuration securely. Trust only provider-documented proxy addresses and forwarded headers when TLS terminates upstream. Reject unexpected production hosts at the host boundary and validate the configured host before enabling future installer operations.

Planned privileged flows require explicit server-side authorization and suitable throttles. Escape template output and treat message HTML and uploaded content as untrusted. Use the [installer recovery model](installation.md) and [media validation](media-library.md) for these entry points. Log security-relevant actions with actor, target, outcome, and request ID. Keep raw credentials, token values, cookies, and private content out of logs and responses. Restrict audit access and define retention as the relevant features are implemented.

Verify session cookies, authorization denials, throttles, safe errors, and private file isolation at the relevant boundaries. See [Authorization](authorization.md) and [Deployment](deployment.md).
