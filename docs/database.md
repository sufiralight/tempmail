# Database

The installer and the application use the native Laravel connection for each selected engine.

| Engine | DB_CONNECTION | PHP capability | Default TCP port |
| --- | --- | --- | --- |
| MySQL | mysql | pdo_mysql | 3306 |
| MariaDB | mariadb | pdo_mysql | 3306 |
| PostgreSQL | pgsql | pdo_pgsql | 5432 |
| SQLite | sqlite | pdo_sqlite | Private fixed file |
| Microsoft SQL Server | sqlsrv | pdo_sqlsrv and a compatible Microsoft ODBC driver | 1433 |

PDO registration determines which options can be submitted. Actual verification exercises the configured driver and server. SQL Server uses encrypted connections with certificate validation by default. PostgreSQL expects the public schema and SQL Server expects dbo. Configure provider-approved TLS for remote endpoints. Both loopback hostnames normalize to TCP 127.0.0.1 for network verification. An empty database password is accepted for local connections.

The first step uses an isolated Laravel connection, runs SELECT 1, and checks that the selected database is empty and matches the requested target. It uses an in-memory connection for SQLite and checks availability of a fixed private filename. No tables or database file are created during verification. See [Installation](installation.md).

Finalization rechecks the target and takes a database-scoped advisory lock for network engines. For SQLite, the private filesystem lock and exclusive file creation protect the fixed database. The installer runs the existing Laravel Schema Builder migrations for users, reset tokens, sessions, cache, jobs and workspace installation. The first account and its operation record share a transaction. MySQL and MariaDB installation use InnoDB.

Later modules must supply their own portable migrations. Use a controlled release with backups and a recovery plan. The migration ledger records applied changes. Test fresh installation and upgrades against isolated instances of each affected engine. SQLite results alone cannot establish network-engine compatibility.

On Windows, local validation exercised the sqlite, mysql and mariadb Laravel paths, with both network paths using an isolated MariaDB server. PostgreSQL PDO was present without a test server. PDO SQLSRV and a SQL Server fixture were unavailable. No separate native MySQL server or live cPanel host was tested.

The workspace administration migration adds role and activation columns plus settings and audit tables. Apply it in a controlled release on existing installations. Its shared settings row serializes privileged mutations.
