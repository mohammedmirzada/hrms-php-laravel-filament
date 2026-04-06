<?php

namespace App\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

class ImpersonationEventSubscriber
{
    public function handleEnter(EnterImpersonation $event): void
    {
        /** @var Model $impersonator */
        $impersonator = $event->impersonator;
        /** @var Model $impersonated */
        $impersonated = $event->impersonated;
        activity('auth')
            ->causedBy($impersonator)
            ->performedOn($impersonated)
            ->withProperties([
                'impersonator_id' => $impersonator->getKey(),
                'impersonated_id' => $impersonated->getKey(),
                'ip' => request()->ip(),
            ])
            ->log('Started impersonating user');
    }

    public function handleLeave(LeaveImpersonation $event): void
    {
        /** @var Model $impersonator */
        $impersonator = $event->impersonator;
        /** @var Model $impersonated */
        $impersonated = $event->impersonated;
        activity('auth')
            ->causedBy($impersonator)
            ->performedOn($impersonated)
            ->withProperties([
                'impersonator_id' => $impersonator->getKey(),
                'impersonated_id' => $impersonated->getKey(),
                'ip' => request()->ip(),
            ])
            ->log('Stopped impersonating user');
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(EnterImpersonation::class, [self::class, 'handleEnter']);
        $events->listen(LeaveImpersonation::class, [self::class, 'handleLeave']);
    }
}
