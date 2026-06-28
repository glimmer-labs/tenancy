<?php

namespace Glimmer\Tenancy\Actions;

use Glimmer\Tenancy\Jobs\Concerns\MaybeTenantAware;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Facades\Context;
use ReflectionClass;
use ReflectionException;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Spatie\Multitenancy\Jobs\TenantAware;
use Throwable;

class MakeQueueMaybeTenantAwareAction extends MakeQueueTenantAwareAction
{
    /**
     * @throws ReflectionException
     * @throws CurrentTenantCouldNotBeDeterminedInTenantAwareJob
     */
    protected function bindOrForgetCurrentTenant(JobProcessing|JobRetryRequested $event): void
    {
        $reflection = $this->getJobReflection($event);

        if ($this->jobIsTenantAware($reflection) || $this->jobIsMaybeTenantAware($reflection)) {
            try {
                $this->bindAsCurrentTenant($this->findTenant($event)->makeCurrent());

                return;
            } catch (CurrentTenantCouldNotBeDeterminedInTenantAwareJob $e) {
                if (! $this->jobIsMaybeTenantAware($reflection)) {
                    $event->job->delete();
                    throw $e;
                }
            }
        }

        app(IsTenant::class)::forgetCurrent();
    }

    protected function jobIsTenantAware(ReflectionClass $reflection): bool
    {
        if ($reflection->implementsInterface(config('multitenancy.tenant_aware_interface', TenantAware::class))) {
            return true;
        }

        if ($reflection->implementsInterface(config('multitenancy.not_tenant_aware_interface',
            NotTenantAware::class))) {
            return false;
        }

        if (in_array($reflection->name, config('multitenancy.tenant_aware_jobs'))) {
            return true;
        }

        if (in_array($reflection->name, config('multitenancy.not_tenant_aware_jobs'))) {
            return false;
        }

        return config('multitenancy.queues_are_tenant_aware_by_default') === true;
    }

    protected function jobIsMaybeTenantAware(ReflectionClass $reflection): bool
    {
        $concern = config('multitenancy.maybe_tenant_aware_interface', MaybeTenantAware::class);
        $config = config('multitenancy.maybe_tenant_aware_jobs');

        return $reflection->implementsInterface($concern) || in_array($reflection->name, $config);
    }

    /**
     * Extracted from MakeQueueTenantAwareAction findTenant method.
     * Modified not to delete the job, as it will be deleted in the bindOrForgetCurrentTenant method.
     *
     * @throws CurrentTenantCouldNotBeDeterminedInTenantAwareJob
     */
    protected function findTenant(JobProcessing|JobRetryRequested $event): IsTenant
    {
        $tenantId = Context::get($this->currentTenantContextKey());

        if (! $tenantId) {
            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noIdSet($event);
        }

        if (! $tenant = app(IsTenant::class)::find($tenantId)) {
            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noTenantFound($event);
        }

        return $tenant;
    }

    /**
     * Extracted from MakeQueueTenantAwareAction isTenantAware method.
     *
     * @throws ReflectionException
     */
    // @codeCoverageIgnoreStart
    protected function getJobReflection(JobProcessing|JobRetryRequested $event): ReflectionClass
    {
        $payload = $this->getEventPayload($event);

        $serializedCommand = $payload['data']['command'];

        if (! str_starts_with($serializedCommand, 'O:')) {
            $serializedCommand = app(Encrypter::class)->decrypt($serializedCommand);
        }

        try {
            $command = unserialize($serializedCommand);
        } catch (Throwable) {
            if ($tenantId = Context::get($this->currentTenantContextKey())) {
                $tenant = app(IsTenant::class)::find($tenantId);
                $tenant?->makeCurrent();
            }

            $command = unserialize($serializedCommand);
        }

        $job = $this->getJobFromQueueable($command);

        return new ReflectionClass($job);
    }
    // @codeCoverageIgnoreEnd
}
