# Architecture decision records

Use a new identifier when replacing a decision and link both records. Dates below record when the existing foundation decisions or future requirements were documented.

## ADR-001 Laravel modular monolith

**Date** 2026-11-12

**Status** Accepted

**Context** One application must run locally and on shared hosting.

**Decision** Keep Laravel 13 on the PHP 8.5 target, use Laravel conventions, and introduce feature ownership inside one deployable.

**Consequences** Cross-feature dependencies require clear ownership. Deployment keeps one application and database. See [Architecture](architecture.md).

## ADR-002 Release assets built off-host

**Date** 2026-11-12

**Status** Accepted

**Context** Production hosts may lack Node.js and interactive shell access.

**Decision** Use Blade, Alpine, Tailwind CSS 4, and Vite. Build assets locally or in CI and ship the manifest and assets with the release.

**Consequences** Keep build output out of Git and verify artifacts before uploading. See [Deployment](deployment.md).

## ADR-003 Database-independent initial boot

**Date** 2026-11-12

**Status** Accepted

**Context** An unconfigured database cannot serve sessions, cache, or queued jobs.

**Decision** Default to file sessions and cache with synchronous queues. Keep migrations explicit and target MySQL or MariaDB.

**Consequences** Storage must be writable. Database-backed features require verified schema before activation. See [Database](database.md).

## ADR-004 Database-first installation and recovery

**Date** 2026-11-12

**Status** Accepted

**Context** Shared-hosting operators need browser installation and must retain control after configuration loss.

**Decision** Verify the database first within an operator-authorized flow and provision the first Super Admin once. Treat uncertain state as recovery requiring trusted access.

**Consequences** The installer pairs a durable database operation with a private filesystem checkpoint. It resumes proven database commits and routes uncertain DDL to operator recovery. See [Installation](installation.md).

## ADR-005 Shared UI and accessibility

**Date** 2026-11-12

**Status** Accepted

**Context** Multiple features will reuse the same controls and layouts.

**Decision** Centralize shared Blade components using Pure Zinc tokens, Geist, Lucide, and a WCAG 2.2 AA target.

**Consequences** Component changes affect all consumers and require theme and accessibility checks. See [Design system](design-system.md) and [Components](components.md).
