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
use Throwable;

class MakeQueueMaybeTenantAwareAction extends MakeQueueTenantAwareAction
{
    /**
     * @throws ReflectionException
     * @throws CurrentTenantCouldNotBeDeterminedInTenantAwareJob
     */
    protected function bindOrForgetCurrentTenant(JobProcessing|JobRetryRequested $event): void
    {
        if ($this->isTenantAware($event)) {
            try {
                $this->bindAsCurrentTenant($this->findTenant($event)->makeCurrent());

                return;
            } catch (CurrentTenantCouldNotBeDeterminedInTenantAwareJob $e) {
                $reflection = $this->getJobReflection($event);

                if (
                    ! $reflection->implementsInterface(config('multitenancy.maybe_tenant_aware_interface',
                        MaybeTenantAware::class)) &&
                    ! in_array($reflection->name, config('multitenancy.maybe_tenant_aware_jobs'))
                ) {
                    $event->job->delete();
                    throw $e;
                }
            }
        }

        app(IsTenant::class)::forgetCurrent();
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
            if ($event instanceof JobProcessing) {
                $event->job->delete();
            }

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noIdSet($event);
        }

        if (! $tenant = app(IsTenant::class)::find($tenantId)) {
            if ($event instanceof JobProcessing) {
                $event->job->delete();
            }

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
