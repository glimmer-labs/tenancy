## Glimmer Tenancy

Glimmer Tenancy extends `spatie/laravel-multitenancy` with opinionated multi-database tenancy, separate landlord and tenant routes, tenant-aware queues, tenant event jobs, and additional tenant finders and switching tasks.

### Installation

```bash
composer require glimmer/tenancy
php artisan vendor:publish --provider="Glimmer\Tenancy\TenancyServiceProvider"
```

The package is auto-discovered. Configure `config/multitenancy.php` and use `Glimmer\Tenancy\Models\Tenant` or a subclass as the tenant model.

### Core rules

- Keep landlord migrations in `database/migrations` and tenant migrations in `database/migrations/tenant`.
- Choose a tenant finder that matches the URL: `DomainTenantFinder`, `SubDomainTenantFinder`, `DomainAndSubDomainTenantFinder`, or `PathTenantFinder` (tenant ID in the first path segment).
- Enable only the required classes in `switch_tenant_tasks`; `SwitchDatabaseConnectionTask` is the package’s multi-database task.
- Keep landlord and tenant routes and data access separate. Use the package’s route groups instead of duplicating tenant context logic.
- Jobs are tenant-aware by default. Explicitly mark jobs that can run with or without a tenant using `MaybeTenantAware`.

When working on a focused tenancy feature, activate the installed `glimmer-tenancy-development` skill and read the relevant reference:

- `references/routing.md` — route files, route groups, finders, middleware, and access exceptions.
- `references/databases.md` — tenant model fields, database switching, migrations, and provisioning.
- `references/jobs.md` — tenant-aware jobs, tenant events, seeding, and shared model synchronization.