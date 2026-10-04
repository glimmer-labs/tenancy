# Jobs and shared models

## Tenant awareness

Jobs are tenant-aware by default when dispatched with a current tenant. A job that may run in either landlord or tenant context should implement `Glimmer\Tenancy\Jobs\Concerns\MaybeTenantAware`:

```php
use Glimmer\Tenancy\Jobs\Concerns\MaybeTenantAware;

final class RebuildReport implements MaybeTenantAware
{
    public function handle(): void
    {
        // Works with or without a current tenant.
    }
}
```

For third-party jobs that cannot implement an interface, configure their classes in `tenant_aware_jobs`, `not_tenant_aware_jobs`, or `maybe_tenant_aware_jobs`.

## Tenant events and seeding

Tenant event jobs extend the package event queue and are chained in `config/multitenancy.php`. They receive the affected tenant as `$tenant`; they are not tenant-aware automatically. Use `ChainSeeding` to dispatch multiple seeders in order. It accepts seeder class names and closures; queued closures require `Laravel\SerializableClosure\SerializableClosure::class` in `maybe_tenant_aware_jobs`.

## Shared models

Apply `Glimmer\Tenancy\Traits\IsSharedModel` to models synchronized between landlord and tenant databases. The trait dispatches synchronization jobs after save and delete operations, so a working queue is required.

Test both landlord and tenant execution paths, especially for queued jobs and long-running workers such as Octane.