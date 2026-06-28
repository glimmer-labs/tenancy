<?php

use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\Tests\Stubs\Jobs\MaybeTenantAwareJob;
use Glimmer\Tenancy\Tests\Stubs\Jobs\TenantAwareJob;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    Config::set('multitenancy.queues_are_tenant_aware_by_default', false);
});

it("doesn't throw an exception on TenantAware job if tenant is provided", function () {
    $this->tenant->makeCurrent();
    expect(fn () => TenantAwareJob::dispatch())
        ->not->toThrow(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);
});

it('does throw an exception on TenantAware job when tenant is not provided', function () {
    expect(fn () => TenantAwareJob::dispatch())
        ->toThrow(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);
});

it("does throw an exception when Tenant doesn't exists", function () {
    $this->tenant->makeCurrent();
    $this->tenant->delete();

    expect(fn () => TenantAwareJob::dispatch())
        ->toThrow(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);
});

it('executes MaybeTenantAware job without a tenant', function () {
    MaybeTenantAwareJob::$handled = false;
    MaybeTenantAwareJob::dispatch();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect(MaybeTenantAwareJob::$handled)->toBeTrue();
});

it('executes MaybeTenantAware job with the current tenant', closure: function () {
    $this->tenant->makeCurrent();

    MaybeTenantAwareJob::$handled = false;
    MaybeTenantAwareJob::dispatch();

    Tenant::forgetCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect(MaybeTenantAwareJob::$handled)
        ->toBeTrue()
        ->and($this->tenant->refresh()->name)
        ->toBe('MaybeTenantAwareJob executed');
});
