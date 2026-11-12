# Security

Authorize administrative and ownership-sensitive actions on the server. Use deny-by-default policies, scoped queries, validated input, and CSRF on browser writes. Escape output with Blade, treat inbound HTML as untrusted, and require a vetted sanitization path before rendering rich content. Rate-limit sensitive actions and retain production-safe errors.

Keep keys and credentials in private configuration. Redact passwords, tokens, cookies, credential-bearing URLs, and sensitive SQL bindings from diagnostics. Log sensitive state changes with actor, action, target, result, and request identifier while limiting access and retention. Use least privilege for database, filesystem, and service credentials.

Keep installer errors redacted and credential fields out of logs and flashed input. Preserve the database operation ID and filesystem checkpoint during recovery. Preserve secure cookie settings and apply the [installer](installer.md) and [upload](media-library.md) rules at their respective entry points. Check the [security model](../docs/security.md) before adding trust boundaries.
