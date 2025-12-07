<?php

namespace Glimmer\Tenancy\Exceptions;

use Exception;
use Laravel\SerializableClosure\SerializableClosure;

class SerializableClosureIsNotMaybeTenantAware extends Exception
{
    public static function make(): static
    {
        return new static(SerializableClosure::class.' is not registered as a maybe tenant aware job. Please add it to the multitenancy.maybe_tenant_aware_jobs config.');
    }
}
