<?php

namespace Glimmer\Tenancy\Traits;

use Closure;
use Glimmer\Tenancy\Exceptions\SerializableClosureIsNotMaybeTenantAware;
use Glimmer\Tenancy\Jobs\TenantEvents\SeedDatabase;
use Glimmer\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Laravel\SerializableClosure\SerializableClosure;
use Spatie\Multitenancy\Landlord;

trait ChainSeeding
{
    protected array $jobs = [];

    public function dispatchSeeders(): void
    {
        Landlord::execute(fn () => Bus::chain($this->jobs)->dispatch());
    }

    public function chain(string|array $seeders): void
    {
        foreach (Arr::wrap($seeders) as $seeder) {
            if ($seeder instanceof Closure) {
                $this->checkSerializableClosureInMaybeTenantAware();
                $tenant = Tenant::current();
                $this->jobs[] = fn () => $tenant->execute($seeder);
            } else {
                if (is_string($seeder) && class_exists($seeder) && is_subclass_of($seeder, Seeder::class)) {
                    $this->jobs[] = new SeedDatabase(Tenant::current(), $seeder);
                }
            }
        }
    }

    public function checkSerializableClosureInMaybeTenantAware(): void
    {
        if (! in_array(SerializableClosure::class, Config::get('multitenancy.maybe_tenant_aware_jobs'))) {
            throw SerializableClosureIsNotMaybeTenantAware::make();
        }
    }
}
