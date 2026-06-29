<?php

namespace Glimmer\Tenancy\Models;

use Database\Factories\TenantFactory;
use Glimmer\Tenancy\Traits\TenantCallbackFixForConcurrency;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    use SoftDeletes, TenantCallbackFixForConcurrency;

    public $incrementing = false;

    public $usesUniqueIds = true;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected static function newFactory()
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        return TenantFactory::new();
    }

    public function getDatabaseName(): string
    {
        return $this->connection_config?->get('database') ?? strtolower(config('app.name')).'_'.$this->id;
    }

    protected function casts(): array
    {
        return [
            'hosts' => AsCollection::class,
            'connection_config' => AsCollection::class,
        ];
    }
}
