---
name: glimmer-tenancy-development
description: Build and work with Glimmer Tenancy features, including tenant routing, multi-database switching, tenant-aware jobs, provisioning, and shared models.
---

# Glimmer Tenancy Development

## When to use this skill

Use this skill when adding or changing Glimmer Tenancy configuration, tenant models, landlord or tenant routes, database provisioning, tenant-aware jobs, seeding, or shared model synchronization.

## Core workflow

1. Confirm the installed package version with `composer show glimmer/tenancy`.
2. Inspect `config/multitenancy.php` and the existing tenant model before changing tenancy behavior.
3. Keep landlord and tenant routes, migrations, and data access separate.
4. Use the package’s route groups, tenant finders, switching tasks, and event jobs instead of duplicating tenant context logic.
5. Test both landlord and tenant execution paths, including queued jobs when relevant.

## Essential conventions

- Keep landlord migrations in `database/migrations` and tenant migrations in `database/migrations/tenant`.
- Choose a finder that matches the URL: `DomainTenantFinder`, `SubDomainTenantFinder`, `DomainAndSubDomainTenantFinder`, or `PathTenantFinder`.
- Enable only the required classes in `switch_tenant_tasks`; `SwitchDatabaseConnectionTask` is the package’s multi-database task.
- Jobs are tenant-aware by default. Explicitly mark jobs that may run with or without a tenant using `MaybeTenantAware`.
- Apply `IsSharedModel` only to models that must be synchronized between landlord and tenant databases, and ensure a queue worker is running.

## Topic references

Read the relevant reference before implementing a focused workflow:

- `references/routing.md` — route separation, finders, middleware, and access exceptions.
- `references/databases.md` — tenant model fields, migrations, switching tasks, and provisioning.
- `references/jobs.md` — tenant-aware jobs, tenant events, seeding, and shared models.