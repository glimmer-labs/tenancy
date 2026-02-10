<?php

namespace Glimmer\Tenancy\Tests;

use Glimmer\Tenancy\Actions\MakeQueueMaybeTenantAwareAction;
use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\TenancyServiceProvider;
use Glimmer\Tenancy\TenantFinders\PathTenantFinder;
use Glimmer\Tenancy\Tests\Stubs\Commands\TenantMaybeNoopCommand;
use Glimmer\Tenancy\Tests\Stubs\Commands\TenantNoopCommand;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Multitenancy\MultitenancyServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

#[WithMigration]
#[WithMigration('queue')]
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Glimmer\\Tenancy\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Stubs/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        $this->bootCommands();

        return [
            PermissionServiceProvider::class,
            MultitenancyServiceProvider::class,
            TenancyServiceProvider::class,
        ];
    }

    protected function bootCommands(): static
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                TenantNoopCommand::class,
                TenantMaybeNoopCommand::class,
            ]);
        });

        return $this;
    }

    protected function getEnvironmentSetUp($app): void
    {
        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        Config::set('scout.driver', 'database');
        Config::set('multitenancy.tenant_finder', PathTenantFinder::class);
        Config::set('multitenancy.tenant_model', Tenant::class);
        Config::set('multitenancy.use_default_routes_groups', true);
        Config::set('multitenancy.actions.make_queue_tenant_aware_action', MakeQueueMaybeTenantAwareAction::class);
        Config::set('multitenancy.routes_prefix', '../../../../');
    }
}
