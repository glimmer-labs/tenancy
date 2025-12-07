<?php

namespace Glimmer\Tenancy\Jobs\Concerns;

use Throwable;

interface EventExceptionHandler
{
    public function __invoke(Throwable $exception);
}
