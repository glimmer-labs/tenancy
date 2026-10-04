# Databases

## Database layout

Keep landlord and tenant migrations separate:

```text
database/migrations/          # landlord database
database/migrations/tenant/   # tenant databases
```

Run tenant migrations for existing tenants with:

```bash
php artisan tenants:artisan "migrate --path=database/migrations/tenant"
```

## Tenant model and switching

Use `Glimmer\Tenancy\Models\Tenant` or extend it. Configure the package task in `config/multitenancy.php`:

```php
'switch_tenant_tasks' => [
    \Glimmer\Tenancy\Tasks\SwitchDatabaseConnectionTask::class,
],
```

`SwitchDatabaseConnectionTask` creates a tenant connection using the landlord default connection. Set `database_connection` on a tenant to choose a driver, and use `connection_config` for per-tenant overrides. Set `connection_config['database']` to override the derived database name.

The default model casts `hosts` and `connection_config` to collections. The model must contain fields expected by the configured finder and switching tasks.

## Provisioning on creation

Tenant event jobs are chained and receive the tenant as `$tenant`. Configure the standard database workflow under `tenant_events.created`:

```php
'tenant_events' => [
    'created' => [
        \Glimmer\Tenancy\Jobs\TenantEvents\CreateDatabase::class,
        \Glimmer\Tenancy\Jobs\TenantEvents\MigrateDatabase::class,
        \Glimmer\Tenancy\Jobs\TenantEvents\SeedDatabase::class,
    ],
],
```