# Routing

## Route separation

Keep landlord-only routes in `routes/landlord.php` and tenant-only routes in `routes/tenant.php`. With `use_default_routes_groups` enabled, the package loads both files, adds `landlord.` or `tenant.` name prefixes, and applies the corresponding middleware groups.

If automatic registration is disabled, use `TenancyRoute`:

```php
use Glimmer\Tenancy\Facades\TenancyRoute;
use Illuminate\Support\Facades\Route;

TenancyRoute::tenant()->group(function (): void {
    Route::get('/home', fn () => 'Tenant home')->name('home');
});
```

Use `TenancyRoute::landlord()` for landlord routes. Do not mix landlord and tenant database operations in one request without deliberately switching context.

## Tenant finders

Set `tenant_finder` in `config/multitenancy.php` to one of the package finders:

- `DomainTenantFinder`: matches the complete request domain against `hosts`.
- `SubDomainTenantFinder`: matches the request subdomain against `hosts`.
- `DomainAndSubDomainTenantFinder`: tries domain and subdomain matching.
- `PathTenantFinder`: reads the tenant ID from the first URL segment.

Ensure the tenant model contains the `hosts` values required by domain-based finders. Use `ForbidsTenant` and `EnsureNoTenantSession` for landlord-only access.

## Access exceptions

To redirect `NoCurrentTenant` or `TenantIsForbidden` exceptions, configure the facade in `bootstrap/app.php`:

```php
use Glimmer\Tenancy\Facades\LandlordTenantException;

$exceptions->render(LandlordTenantException::redirect('login'));
```