<?php

use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\Tasks\PrefixFilesystemTask;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->anotherTenant = Tenant::factory()->create();

    Config::set('multitenancy.switch_tenant_tasks', [PrefixFilesystemTask::class]);
});

test('switch prefixes local disk', function () {
    $disk = Illuminate\Support\Facades\Storage::disk('local');

    $this->tenant->makeCurrent();

    expect(Storage::disk('local')->path(''))->toBe($disk->path($this->tenant->getKey().'/'));
});

test('switching tenant will switch disk prefixes', function () {
    $disk = Illuminate\Support\Facades\Storage::disk('public');

    $this->tenant->makeCurrent();

    expect(Storage::disk('public')->path(''))->toBe($disk->path($this->tenant->getKey().'/'));

    $this->anotherTenant->makeCurrent();

    expect(Storage::disk('public')->path(''))->toBe($disk->path($this->anotherTenant->getKey().'/'));

    $this->tenant->forgetCurrent();

    expect(Storage::disk('public')->path(''))->toBe($disk->path(''));
});
