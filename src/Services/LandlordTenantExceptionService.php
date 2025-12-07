<?php

namespace Glimmer\Tenancy\Services;

use Closure;
use Glimmer\Tenancy\Exceptions\TenantIsForbidden;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

class LandlordTenantExceptionService
{
    public function redirect(string $route): Closure
    {
        return function (NoCurrentTenant|TenantIsForbidden $exception, Request $request) use ($route) {
            return redirect()->to($route)->withErrors([$exception->getMessage()]);
        };
    }
}
