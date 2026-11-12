# Installer

Use the [installation state machine](../docs/installation.md). The database and administrator steps share the private gate, lease, and finalization lock.

Require the private deployment gate on every installer request. Keep the direct peer, raw Host, HTTPS, and authorized production database endpoint checks intact. Local loopback permits an empty database password. Protect CSRF, body limits, rate limiting, and host-only encrypted file sessions. Keep connection settings out of flashed input, logs, responses, and browser state.

Finalization must recheck the verified lease and use the same exclusive lock. Preserve existing configuration, reconcile partial writes, record durable completion, and retire temporary state. Missing .env requires operator-controlled recovery when prior installation evidence exists or state is uncertain.
