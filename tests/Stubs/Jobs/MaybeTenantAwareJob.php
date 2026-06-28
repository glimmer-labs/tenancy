<?php

namespace Glimmer\Tenancy\Tests\Stubs\Jobs;

use Glimmer\Tenancy\Jobs\Concerns\MaybeTenantAware;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Multitenancy\Contracts\IsTenant;

class MaybeTenantAwareJob implements MaybeTenantAware, ShouldQueue
{
    use Queueable;

    public static bool $handled = false;

    public function __construct() {}

    public function handle(): void
    {
        self::$handled = true;

        if (app(IsTenant::class)::checkCurrent()) {
            $tenant = app(IsTenant::class)::current();

            $tenant->name = 'MaybeTenantAwareJob executed';
            $tenant->save();
        }
    }
}
