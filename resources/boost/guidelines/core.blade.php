## Glimmer Tenancy

Glimmer Tenancy is an opinionated Laravel package that extends `spatie/laravel-multitenancy`. It provides multi-database tenancy, separate landlord and tenant route files, tenant-aware queues, tenant event jobs, tenant finders, and additional tenant switching tasks.

### Installation

Install the package and publish its configuration, route files, and factory:

@verbatim
<code-snippet name="Install and publish Glimmer Tenancy" lang="bash">
composer require glimmer/tenancy
php artisan vendor:publish --provider="Glimmer\Tenancy\TenancyServiceProvider"
</code-snippet>
@endverbatim

The package service provider is discovered automatically. The published files are `config/multitenancy.php`, `routes/landlord.php`, `routes/tenant.php`, and the tenant factory. Landlord migrations remain in `database/migrations`; tenant migrations belong in `database/migrations/tenant`.

### Core configuration

Configure `config/multitenancy.php` before using tenancy:

- Set `tenant_model` to the tenant model used by the application. The default is `Glimmer\Tenancy\Models\Tenant`.
- Set `tenant_finder` to a finder appropriate for the request, such as `Glimmer\Tenancy\TenantFinders\DomainTenantFinder`, `SubDomainTenantFinder`, `DomainAndSubDomainTenantFinder`, or `PathTenantFinder`. The path finder expects the tenant ID in the first URL segment.
- Enable the required classes in `switch_tenant_tasks`. `SwitchDatabaseConnectionTask` is the usual choice for the package’s multi-database approach.
- Keep `use_default_routes_groups` enabled to automatically load `routes/landlord.php` and `routes/tenant.php` from the application base path. Set it to `false` when registering route groups manually.
- Add tenant event jobs under `tenant_events.created` when tenant databases should be created, migrated, or seeded automatically.

### Tenant model and database structure

Use `Glimmer\Tenancy\Models\Tenant` or extend it when the application needs a custom tenant model. Its `hosts` and `connection_config` attributes are cast to collections, and `getDatabaseName()` derives the tenant database name from `connection_config['database']` or the application name and tenant ID.

Tenant migrations must be stored separately from landlord migrations:

@verbatim
<code-snippet name="Tenant migration layout" lang="text">
database/
├── migrations/                 # landlord database migrations
└── migrations/tenant/          # tenant database migrations
</code-snippet>
@endverbatim

To migrate existing tenants manually, run the tenant artisan command with the tenant migration path:

@verbatim
<code-snippet name="Run tenant migrations" lang="bash">
php artisan tenants:artisan "migrate --path=database/migrations/tenant"
</code-snippet>
@endverbatim

### Landlord and tenant routes

Put landlord-only routes in `routes/landlord.php` and tenant-only routes in `routes/tenant.php`. Automatic registration applies the `landlord.` and `tenant.` name prefixes and the package’s landlord/tenant middleware groups.

If automatic registration is disabled, register route groups explicitly with the `TenancyRoute` facade:

@verbatim
<code-snippet name="Register explicit tenancy route groups" lang="php">
use Glimmer\Tenancy\Facades\TenancyRoute;
use Illuminate\Support\Facades\Route;

TenancyRoute::landlord()->group(function (): void {
    Route::get('/dashboard', fn () => 'Landlord dashboard')->name('dashboard');
});

TenancyRoute::tenant()->group(function (): void {
    Route::get('/home', fn () => 'Tenant home')->name('home');
});
</code-snippet>
@endverbatim

Use `ForbidsTenant` and `EnsureNoTenantSession` for landlord-only access, and the tenant route middleware configured by the package for tenant-only access. Do not mix landlord and tenant database operations in the same request without deliberately switching context.

### Tenant switching tasks

Enable only the tasks the application needs in `switch_tenant_tasks`:

@verbatim
<code-snippet name="Enable tenant switching tasks" lang="php">
'switch_tenant_tasks' => [
    \Glimmer\Tenancy\Tasks\SwitchDatabaseConnectionTask::class,
    \Glimmer\Tenancy\Tasks\PrefixFilesystemTask::class,
    \Glimmer\Tenancy\Tasks\PrefixScoutTask::class,
    \Glimmer\Tenancy\Tasks\PrefixSpatiePermissionTask::class,
],
</code-snippet>
@endverbatim

`SwitchDatabaseConnectionTask` creates a connection per tenant using the landlord’s default connection. A tenant can override the connection driver with `database_connection` and connection settings with `connection_config`; set `connection_config['database']` to override the database name.

### Tenant events and queues

Tenant event jobs are chained when configured and receive the tenant instance. Common setup for automatically provisioning a tenant database is:

@verbatim
<code-snippet name="Provision tenants with events" lang="php">
'tenant_events' => [
    'created' => [
        \Glimmer\Tenancy\Jobs\TenantEvents\CreateDatabase::class,
        \Glimmer\Tenancy\Jobs\TenantEvents\MigrateDatabase::class,
        \Glimmer\Tenancy\Jobs\TenantEvents\SeedDatabase::class,
    ],
],
</code-snippet>
@endverbatim

Jobs are tenant-aware by default. Implement `Glimmer\Tenancy\Jobs\Concerns\MaybeTenantAware` when a job may run with or without a current tenant, and configure custom jobs in `tenant_aware_jobs`, `not_tenant_aware_jobs`, or `maybe_tenant_aware_jobs` when they do not implement the corresponding interface.

### Seeder chaining

Use `Glimmer\Tenancy\Traits\ChainSeeding` to dispatch multiple seeders in order. It accepts seeder class names and closures; when closures are queued, register `Laravel\SerializableClosure\SerializableClosure::class` in `maybe_tenant_aware_jobs`.

### Shared models

Apply `Glimmer\Tenancy\Traits\IsSharedModel` to a model that must be synchronized across the landlord and tenant databases. The trait dispatches synchronization jobs after the model is saved or deleted, so configure and run a queue worker when relying on this behavior.

### Handling tenant access exceptions

Use `Glimmer\Tenancy\Facades\LandlordTenantException` in `bootstrap/app.php` when `NoCurrentTenant` or `TenantIsForbidden` should render a redirect instead of the default error response:

@verbatim
<code-snippet name="Redirect tenant access exceptions" lang="php">
use Glimmer\Tenancy\Facades\LandlordTenantException;

$exceptions->render(LandlordTenantException::redirect('login'));
</code-snippet>
@endverbatim

### Best practices

- Keep landlord and tenant migrations, routes, and data access clearly separated.
- Prefer the package’s tenant model, finders, tasks, and event jobs over duplicating tenant switching logic.
- Ensure the tenant model contains the fields required by the configured finder and switching tasks, especially `hosts`, `database_connection`, and `connection_config` where applicable.
- Test both landlord-context and tenant-context behavior, including queued jobs and Octane if the application uses it.