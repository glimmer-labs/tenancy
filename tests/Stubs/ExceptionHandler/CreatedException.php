<?php

namespace Glimmer\Tenancy\Tests\Stubs\ExceptionHandler;

use Exception;
use Glimmer\Tenancy\Jobs\Concerns\EventExceptionHandler;
use Throwable;

class CreatedException implements EventExceptionHandler
{
    public function __invoke(Throwable $exception)
    {
        throw new Exception('CreatedException');
    }
}
