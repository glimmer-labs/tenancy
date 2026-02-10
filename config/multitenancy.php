<?php

use Glimmer\Tenancy\Actions\MakeQueueMaybeTenantAwareAction;
use Glimmer\Tenancy\Jobs\Concerns\MaybeTenantAware;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedClosure;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Spatie\Multitenancy\Jobs\TenantAware;

return [
    /*
     * This class is responsible for determining which tenant should be current for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     * Available tenant finders:
     *  `Glimmer\Tenancy\TenantFinders\DomainTenantFinder`
     *  `Glimmer\Tenancy\TenantFinders\SubDomainTenantFinder`
     *  `Glimmer\Tenancy\TenantFinders\DomainAndSubDomainTenantFinder`
     *  `Glimmer\Tenancy\TenantFinders\PathParameterTenantFinder`
     */
    'tenant_finder' => null,

    /*
     * These fields are used by tenant:artisan command to match one or more tenant.
     */
    'tenant_artisan_search_fields' => [
        'id',
    ],

    /*
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\SwitchTenantTask
     */
    'switch_tenant_tasks' => [
        // \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
        // \Spatie\Multitenancy\Tasks\SwitchRouteCacheTask::class,
        // \Glimmer\Tenancy\Tasks\SwitchDatabaseConnectionTask::class,
        // \Glimmer\Tenancy\Tasks\PrefixFilesystemTask::class,
        // \Glimmer\Tenancy\Tasks\PrefixScoutTask::class,
        // \Glimmer\Tenancy\Tasks\PrefixSpatiePermissionTask::class,
    ],

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must extend `Spatie\Multitenancy\Models\Tenant::class` or
     * implement `Spatie\Multitenancy\Contracts\IsTenant::class` interface
     */
    'tenant_model' => Glimmer\Tenancy\Models\Tenant::class,

    /*
     * Events jobs are triggered when a tenant is created, updated, deleted, restored, etc.
     * https://laravel.com/docs/master/eloquent#events
     *
     * A valid event job is any class extending `Glimmer\Tenancy\Events\TenantEventQueue`.
     * The jobs are dispatched in a chain.
     * The jobs will receive the Tenant instance as `$tenant`, which can be used within the job.
     * The jobs are not tenant-aware (you can use the `$tenant` to execute tenant-specific logic).
     *
     * Optionally, a 'catch' key can be added to an event with an invokable class that implements
     * `Glimmer\Tenancy\Jobs\Concerns\EventExceptionHandler` interface.
     * It will be executed if a job in the chain fails, receiving a Throwable instance as an argument.
     */
    'tenant_events' => [
        'created' => [
            // \Glimmer\Tenancy\Jobs\TenantEvents\CreateDatabase::class,
            // \Glimmer\Tenancy\Jobs\TenantEvents\MigrateDatabase::class,
            // \Glimmer\Tenancy\Jobs\TenantEvents\SeedDatabase::class,
            // 'catch' => Not required, must be any class that implements `Glimmer\Tenancy\Jobs\Concerns\EventExceptionHandler` interface
        ],
    ],

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'queues_are_tenant_aware_by_default' => true,

    /*
     * This key will be used to associate the current tenant in the context
     */
    'current_tenant_context_key' => 'tenantId',

    /*
     * This key will be used to bind the current tenant in the container.
     */
    'current_tenant_container_key' => 'currentTenant',

    /*
     * Set it to `true` if you like to cache the tenant(s) routes
     * in a shared file using the `SwitchRouteCacheTask`.
     */
    'shared_routes_cache' => false,

    /*
     * Set it to `true` if you like the package to automatically register the routes
     * for the tenant and landlord from the files `routes/tenant.php` and `routes/landlord.php`
     */
    'use_default_routes_groups' => true,

    /*
     * You can customize some of the behavior of this package by using your own custom action.
     * Your custom action should always extend the default one.
     */
    'actions' => [
        'make_tenant_current_action' => MakeTenantCurrentAction::class,
        'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware_action' => MakeQueueMaybeTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],

    /*
     * You can customize the way in which the package resolves the queueable to a job.
     *
     * For example, using the package laravel-actions (by Loris Leiva), you can
     * resolve JobDecorator to getAction() like so: JobDecorator::class => 'getAction'
     */
    'queueable_to_job' => [
        SendQueuedMailable::class => 'mailable',
        SendQueuedNotifications::class => 'notification',
        CallQueuedClosure::class => 'closure',
        CallQueuedListener::class => 'class',
        BroadcastEvent::class => 'event',
    ],

    /*
    * Interface that once implemented, will make the job tenant aware
    */
    'tenant_aware_interface' => TenantAware::class,

    /*
     * Interface that once implemented, will make the job not tenant aware
     */
    'not_tenant_aware_interface' => NotTenantAware::class,

    /*
     * Interface that once implemented, will make the job maybe tenant aware
     */
    'maybe_tenant_aware_interface' => MaybeTenantAware::class,

    /*
     * Jobs tenant aware even if these don't implement the TenantAware interface.
     */
    'tenant_aware_jobs' => [
        // ...
    ],

    /*
     * Jobs not tenant aware even if these don't implement the NotTenantAware interface.
     */
    'not_tenant_aware_jobs' => [
        // ...
    ],

    /*
     * Jobs maybe tenant aware even if these don't implement the MaybeTenantAware interface.
     */
    'maybe_tenant_aware_jobs' => [
        // ...
    ],
];
