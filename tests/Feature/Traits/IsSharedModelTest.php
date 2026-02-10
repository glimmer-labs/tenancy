<?php

use Glimmer\Tenancy\Jobs\SynchronizeSharedModel;
use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\Tests\Stubs\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->anotherTenant = Tenant::factory()->create();

    $this->tempUser = User::make([
        'name' => 'test',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('dispatches job on update/create', function () {
    Queue::fake([
        SynchronizeSharedModel::class,
    ]);

    $this->tempUser->save();

    Queue::assertPushed(SynchronizeSharedModel::class, 1);
});

it('dispatches job on delete', function () {
    Queue::fake([
        SynchronizeSharedModel::class,
    ]);

    $this->tempUser->saveQuietly();

    $this->tempUser->delete();

    Queue::assertPushed(SynchronizeSharedModel::class, 1);
});

it('dispatches job on restore', function () {
    Queue::fake([
        SynchronizeSharedModel::class,
    ]);

    $this->tempUser->saveQuietly();

    $this->tempUser->restore();

    Queue::assertPushed(SynchronizeSharedModel::class, 1);
});
