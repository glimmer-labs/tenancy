<?php

use Glimmer\Tenancy\Jobs\SynchronizeSharedModel;
use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\Tasks\SwitchDatabaseConnectionTask;
use Glimmer\Tenancy\Tests\Stubs\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    Config::set('multitenancy.switch_tenant_tasks', [SwitchDatabaseConnectionTask::class]);

    touch(database_path($this->tenant->getDatabaseName().'.sqlite'));

    $this->tenant->execute(function () {
        Artisan::call('migrate');
        Artisan::call('migrate', ['--path' => __DIR__.'/../../Stubs/database/migrations', '--realpath' => true]);

        $this->user = User::createQuietly([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    });
});

afterEach(function () {
    unlink(database_path($this->tenant->getDatabaseName().'.sqlite'));
});

it('updates/creates model on landlord database when created on tenant', function () {
    $this->tenant->execute(function () {
        $job = (new SynchronizeSharedModel($this->user::class, $this->user->getKey()));
        $job->handle();
    });

    expect(User::find($this->user->getKey()))->not->toBeNull();
});

it('deletes model on landlord database when deleted on tenant', function () {
    $this->tenant->execute(function () {
        $this->user->delete();
        $job = (new SynchronizeSharedModel($this->user::class, $this->user->getKey()));
        $job->handle();
    });

    expect(User::find($this->user->getKey()))->toBeNull();
});

it('restores model on landlord database when restored on tenant', function () {
    $this->user->deleteQuietly();

    expect(User::find($this->user->getKey()))->toBeNull();

    $this->tenant->execute(function () {
        $this->user->restore();
        $job = (new SynchronizeSharedModel($this->user::class, $this->user->getKey()));
        $job->handle();
    });

    expect(User::find($this->user->getKey()))->not->toBeNull();
});
