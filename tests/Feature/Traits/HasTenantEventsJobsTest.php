<?php

use Glimmer\Tenancy\Jobs\TenantEvents\CreateDatabase;
use Glimmer\Tenancy\Jobs\TenantEvents\MigrateDatabase;
use Glimmer\Tenancy\Models\Tenant;
use Glimmer\Tenancy\TenantEventsServiceProvider;
use Glimmer\Tenancy\Tests\Stubs\ExceptionHandler\CreatedException;
use Glimmer\Tenancy\Tests\Stubs\Jobs\TenantEventWithException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('multitenancy.tenant_events', [
        'created' => [
            CreateDatabase::class,
            MigrateDatabase::class,
            'catch' => CreatedException::class,
        ],
    ]);

    $this->eventName = 'eloquent.created: Glimmer\Tenancy\Models\Tenant';
    (new TenantEventsServiceProvider(app()))->boot();
});

it('has `created` event with it\'s chain jobs', function () {
    Tenant::factory()->make();

    $dispatcher = Tenant::getEventDispatcher();

    expect($dispatcher->hasListeners($this->eventName))->toBeTrue();

    $eventReflection = new ReflectionFunction($dispatcher->getListeners($this->eventName)[0]);
    $listenerReflection = new ReflectionFunction($eventReflection->getStaticVariables()['listener']);

    expect($listenerReflection->getStaticVariables()['jobs']->search(CreateDatabase::class))->not->toBeFalse();
});

it('dispatches `created` event', function () {
    Event::fake();

    Tenant::factory()->create();

    Event::assertDispatched($this->eventName);
});

it('`created` event dispatches jobs chain', function () {
    Queue::fake();

    Tenant::factory()->create();

    Queue::assertPushedWithChain(CreateDatabase::class, [MigrateDatabase::class]);
});

it('has `catch` in the jobs chain', function () {
    Queue::fake();

    Tenant::factory()->create();

    expect(count(Queue::pushed(CreateDatabase::class)->get(0)->chainCatchCallbacks))->toBe(1);
});

it('calls `catch` invokable callback', function () {
    Config::set('multitenancy.tenant_events', [
        'created' => [
            TenantEventWithException::class,
            'catch' => CreatedException::class,
        ],
    ]);
    (new TenantEventsServiceProvider(app()))->boot();

    expect(fn () => Tenant::factory()->create())->toThrow('CreatedException');
});

it("throws error when `catch` doesn't implements EventExceptionHandler", function () {
    Config::set('multitenancy.tenant_events', [
        'created' => [
            TenantEventWithException::class,
            'catch' => TenantEventWithException::class,
        ],
    ]);

    expect(fn () => (new TenantEventsServiceProvider(app()))->boot())->toThrow(InvalidArgumentException::class);
});
