# Glimmer Tenancy

#### An opinionated Spatie Multitenancy extension package for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/glimmer/tenancy?style=flat-square)](https://packagist.org/packages/glimmer/tenancy)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/laravel-glimmer/tenancy/tests.yml?branch=main&label=Tests&style=flat-square)](https://github.com/laravel-glimmer/tenancy/actions/workflows/tests.yml?query=branch%3Amain)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/12.x/octane#introduction)

This package extends [spatie/laravel-multitenancy](https://github.com/spatie/laravel-multitenancy) with additional
opinionated features. For example, it defaults to multi-database tenancy but takes a different approach than Spatie's
implementation.

Since this package builds upon Spatie’s, it retains most original concepts and functionality. It’s recommended to
review Spatie's official documentation before using this package.

### What makes it opinionated?

- It uses a multi-database approach by default, creating a new connection for each tenant.
- Different landlord/tenant migrations structure.
- Auto-routing files with predefined middlewares.
- Additional tasks and tenant finders that expect the model to have certain columns.
- Allows queue jobs to run in both tenant and landlord contexts.

## Installation

```bash
composer require glimmer/tenancy
```

All files (routes, config, migrations) can be published at once by running:

```bash
php artisan vendor:publish --provider="Glimmer\Tenancy\TenancyServiceProvider"
```

## Features

#### Additional Tenant Switching Tasks:

- `PrefixFilesystemTask` – Prefixes filesystem disk paths with the tenant key (ID) using
  `league/flysystem-path-prefixing`.
- `PrefixScoutTask` – Prefixes Laravel Scout index names in `config/scout.php` with the tenant key.
- `PrefixSpatiePermissionTask` – Adds a tenant-specific prefix to Spatie Permission cache keys.

#### Enhanced Tenant Switching Tasks:

- `SwitchDatabaseConnectionTask` (Replaces Spatie’s `SwitchTenantDatabaseTask`) – Multi-database approach that creates a
  connection for each tenant using the landlord’s default connection (`.env` setting) and dynamically switches between
  them.
    - A different connection can be specified per tenant by setting the `database_connection` column in the tenant
      model.
    - Individual connection configurations can be overridden using the `connection_config` column.
    - The database name can be changed within the `connection_config` array by defining `'database' => 'name'`.
    - This approach improves performance by avoiding frequent reconnections but slightly increases memory usage,
      especially in Laravel Octane environments.

#### Tenant Finders

- `DomainTenantFinder` – Finds the tenant by matching the request domain with the entries in the tenant model’s `hosts`
  array.
- `SubDomainTenantFinder` – Identifies the tenant using the subdomain from the request, based on the `hosts` array in
  the model.
- `PathTenantFinder` – Determines the tenant by extracting its ID from the request path.
- `DomainAndSubDomainTenantFinder` – Matches tenants using either the request’s domain or subdomain.

> The `hosts` array in the tenant model can contain a list of either domains or subdomains.

#### Queue Enhancements:

- `MakeQueueMaybeTenantAwareAction` – A modified `MakeQueueTenantAwareAction` allowing jobs to run in both tenant and
  landlord contexts if the job implements `MaybeTenantAware`.
- `MaybeTenantAware` – An interface that allows jobs to be dispatched with/without tenant context depending on the job
  implementation.
- `tenant events` - Allows executing certain jobs when a tenant event is fired. (Job must extend `TenantEventQueue`)
  (Jobs must be defined on `multitenancy.php` config file with its respective event)

#### Tenant events:

For these events to work, the `HasTenantEvents` trait must be used in the tenant model (which is already included in
Glimmer's `Tenant` model).

- `CreateDatabase` – Triggered when a tenant is created.
- `MigrateDatabase` – Fired after tenant creation to apply migrations (expects migrations to be at:
  `database/migrations/tenant`).
- `SeedDatabase` – Runs database seeders upon tenant creation.
    - Defaults to `DatabaseSeeder.php` but will use `tenant/DatabaseSeeder.php` if it exists.

#### Seeder Utilities:

- `ChainSeeding` – A trait that allows chaining multiple seeders to be executed sequentially.
    - Can be used in any class that needs to dispatch multiple seeders in a specific order.
    - Supports both seeder class names and closures as seeders.
    - Example usage:
    ```php
    use Glimmer\Tenancy\Traits\ChainSeeding;
  
    class MySeeder extends Seeder
    {
        use ChainSeeding;

        public function run()
        {
            // Chain multiple seeders
            $this->chain([
                FirstSeeder::class,
                SecondSeeder::class,
                function() {
                    // Custom seeding logic
                }
            ]);

            // Dispatch the chain of seeders
            $this->dispatchSeeders();
        }
    }
    ```
    - When using closures, make sure to register `SerializableClosure` as a maybe tenant aware job in your config:
    ```php
    // config/multitenancy.php
    'maybe_tenant_aware_jobs' => [
        // ...
        \Laravel\SerializableClosure\SerializableClosure::class,
    ],
    ```

#### Command Enhancements:

- `IsTenantAware`|`TenantAware` – A modification to `Spatie/Commands/Concerns/TenantAware` trait enabling SQLite usage
  without lock issues.
- `MaybeTenantAware` – A trait for commands that can be executed with/without tenant context.
- `tenants:artisan` – A modification that uses Glimmer's `IsTenantAware` trait to allow SQLite databases to be used
  without blocking commands.

#### Middlewares:

- `EnsureNoTenantSession` - Prevents tenant session to be used on landlord routes.
- `ForbidsTenant` - Prevents tenant to access landlord routes.

### Extended functionality:

- **Tenant Model** – `Glimmer\Tenancy\Models\Tenant` extends `Spatie\Multitenancy\Models\Tenant` with
  additional functionality as running events and determining the database name as expected.
- **Separated Route Files** – Landlord (landlord.php) and tenant (tenant.php) routes are managed separately, while
  `web.php` remains shared.
- **`IsSharedModel` trait** – Dispatches synchronization jobs when a model is saved or deleted so shared model records
  can be kept in sync across the landlord and tenant databases. Add the trait to the model that should be synchronized.
- **Automatic Route Registration** – Routes are automatically registered and assigned appropriate middlewares to prevent
  unauthorized access (this can be disabled in `multitenancy.php` config).
    - Tenant routes includes `NeedsTenant` and `EnsureValidTenantSession` middleware by default.
    - Landlord routes includes `ForbidsTenant` `EnsureNoTenantSession` middleware by default.
    - When using route auto-registration tenant routes names are prefixed with `tenant.` and landlord routes with
      `landlord.`.
    - If auto-registration is disabled, use `TenancyRoute::landlord()` and/or `TenancyRoute::tenant()` to register
      them by hand and group the routes you need.
    ```php
      use Illuminate\Support\Facades\Route;
      use Glimmer\Tenancy\Facades\TenancyRoute;

      TenancyRoute::landlord()->group(function () {
        Route::get('/dashboard', function () {
            return 'Landlord dashboard';
        })->name('dashboard');
      });

      TenancyRoute::tenant()->group(function () {
          Route::get('/home', function () {
              return 'Tenant home';
          })->name('home');
      });
    ```

---

##### Notes:

- To avoid showing `500` error on `NoCurrentTenant` or `TenantIsForbidden` exception and instead redirect to a route,
  you can add the following to your `bootstrap/app.php` in to the exception handler:
  ```php
  use Glimmer\Tenancy\Facades\LandlordTenantException;

  return Application::configure(basePath: dirname(__DIR__))
      ....
      ->withExceptions(function (Exceptions $exceptions) {
          $exceptions->render(LandlordTenantException::redirect('your-route'));
      })->create();
  ```

- As disclosed before, major structural changes are migration paths for landlord and tenant, as landlord migrations are
  at `database/migrations` root and tenant migrations are at `database/migrations/tenant`, and so for migrating the
  landlord you need to run:
  ```bash
  php artisan migrate
  ```
  For migrating the tenant, you need to run:
  ```bash
  php artisan tenants:artisan "migrate --path=database/migrations/tenant"
  ```
  > Or instead, use Glimmer's tenant events, which will automatically migrate the tenant after creation.

- The `SwitchRouteCacheTask` should be considered hardly if needed. In many cases, middleware/controller checks can
  enforce route access restrictions without needing this task.
