<?php

use Glimmer\Tenancy\Exceptions\SerializableClosureIsNotMaybeTenantAware;
use Glimmer\Tenancy\Jobs\TenantEvents\SeedDatabase;
use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\Tasks\SwitchDatabaseConnectionTask;
use Glimmer\Tenancy\Tests\Stubs\Seeders\AnotherTestSeeder;
use Glimmer\Tenancy\Tests\Stubs\Seeders\ChainSeeder;
use Glimmer\Tenancy\Tests\Stubs\Seeders\TestSeeder;
use Illuminate\Support\Facades\Config;
use Laravel\SerializableClosure\SerializableClosure;

beforeEach(function () {
    $this->testClass = new ChainSeeder;
    $this->tenant = Tenant::factory()->create();

    Config::set('multitenancy.switch_tenant_tasks', [SwitchDatabaseConnectionTask::class]);
    Config::set('multitenancy.maybe_tenant_aware_jobs', [SerializableClosure::class]);

    $this->tenant->makeCurrent();
});

afterEach(function () {
    Tenant::forgetCurrent();
});

it('can chain a single seeder', function () {
    $this->testClass->chain(TestSeeder::class);

    $jobs = (new ReflectionClass($this->testClass))->getProperty('jobs');
    $jobsArray = $jobs->getValue($this->testClass);

    expect($jobsArray)->toHaveCount(1)
        ->and($jobsArray[0])->toBeInstanceOf(SeedDatabase::class)
        ->and($jobsArray[0]->seeder)->toBe(TestSeeder::class);
});

it('can chain multiple seeders as an array', function () {
    $this->testClass->chain([TestSeeder::class, AnotherTestSeeder::class]);

    $jobs = (new ReflectionClass($this->testClass))->getProperty('jobs');
    $jobsArray = $jobs->getValue($this->testClass);

    expect($jobsArray)->toHaveCount(2)
        ->and($jobsArray[0])->toBeInstanceOf(SeedDatabase::class)
        ->and($jobsArray[0]->seeder)->toBe(TestSeeder::class)
        ->and($jobsArray[1])->toBeInstanceOf(SeedDatabase::class)
        ->and($jobsArray[1]->seeder)->toBe(AnotherTestSeeder::class);
});

it('can chain a closure', function () {
    $closure = function () {
        // Test closure
    };

    $this->testClass->chain([$closure]);

    $jobs = (new ReflectionClass($this->testClass))->getProperty('jobs');
    $jobsArray = $jobs->getValue($this->testClass);

    expect($jobsArray)->toHaveCount(1)
        ->and($jobsArray[0])->toBeCallable();
});

it('throws exception when SerializableClosure is not registered as maybe tenant aware', function () {
    Config::set('multitenancy.maybe_tenant_aware_jobs', []);

    $closure = function () {
        // Test closure
    };

    expect(fn () => $this->testClass->chain([$closure]))
        ->toThrow(SerializableClosureIsNotMaybeTenantAware::class);
});
