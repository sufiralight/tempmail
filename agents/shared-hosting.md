# Shared hosting

Support Apache and cPanel with the application's public directory as document root. Keep secrets and private files above that root. Preserve rewrite rules and use the provider's known proxy configuration when needed. Use precompiled Vite assets and prepare Composer dependencies outside production.

Production must work without Node.js, npm, Docker, mandatory Redis or Supervisor, persistent queue workers, or interactive shell access. Database-backed sessions are an option after the session table exists. Use bounded, non-overlapping cron work where the host provides cron and document the feature's prerequisite. Follow the [deployment procedure](../docs/deployment.md).
