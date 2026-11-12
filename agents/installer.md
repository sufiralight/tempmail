# Installer

Implement the [installation flow](../docs/installation.md) in Prompt 04. Verify the database before exposing the first Super Admin step. Require deployment-operator authorization established outside the public application and bind progress to that authorized session. Keep routes closed by default, including when installation status is unknown.

Keep CSRF enabled and throttle verification and privileged writes. Validate the configured production host and trusted proxy boundary. Accept an empty password for an explicitly local database and support localhost and 127.0.0.1. Protect submitted connection details from logs, URLs, and session flash data. Generate the application key during authorized new installation.

Serialize installation writes with a lock whose lifetime covers finalization. Persist progress privately and reconcile with the database before retrying. Close every installer route after completion. Require operator recovery for missing environment files on an existing instance. Avoid a public setup-credential form and preserve the original application key during recovery.
