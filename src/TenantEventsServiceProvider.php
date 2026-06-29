<?php

namespace Glimmer\Tenancy;

use Glimmer\Tenancy\Jobs\Concerns\EventExceptionHandler;
use Glimmer\Tenancy\Jobs\Concerns\TenantEventQueue;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class TenantEventsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $config = Config::get('multitenancy.tenant_events', []);

        foreach ($config as $event => $definition) {
            $this->registerEvent($event, $definition);
        }
    }

    protected function registerEvent(string $event, array $definition): void
    {
        $jobs = collect($definition)
            ->except('catch')
            ->filter(fn ($job) => class_exists($job) && is_subclass_of($job, TenantEventQueue::class));

        if ($jobs->isEmpty()) {
            return;
        }

        $catch = $definition['catch'] ?? null;

        if ($catch !== null) {
            if (! class_exists($catch) || ! is_subclass_of($catch, EventExceptionHandler::class)) {
                throw new InvalidArgumentException('The catch key must be a class that implements EventExceptionHandler');
            }
        }

        $this->registerModelEvent($event, $jobs, $catch);
    }

    protected function registerModelEvent(string $event, $jobs, ?string $catch): void
    {
        $modelClass = Config::get('multitenancy.tenant_model');

        if (! class_exists($modelClass)) {
            throw new InvalidArgumentException("Tenant model [$modelClass] not found.");
        }

        $modelClass::registerModelEvent($event, fn ($model) => Bus::chain($jobs->map(fn ($job) => new $job($model)))
            ->when($catch, fn (PendingChain $chain) => $chain->catch(new $catch))
            ->dispatch()
        );
    }

    public function register()
    {
        //
    }
}
