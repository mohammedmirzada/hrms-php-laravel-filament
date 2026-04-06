<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;

class AuthEventSubscriber
{
    public function handleLogin(Login $event): void
    {
        /** @var Model $user */
        $user = $event->user;
        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard,
            ])
            ->log('Logged in');
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        /** @var Model $user */
        $user = $event->user;
        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'guard' => $event->guard,
            ])
            ->log('Logged out');
    }

    public function handleFailed(Failed $event): void
    {
        $activity = activity('auth')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ]);

        if ($event->user) {
            /** @var Model $failedUser */
            $failedUser = $event->user;
            $activity->causedBy($failedUser)->performedOn($failedUser);
        }

        $activity->log('Failed login attempt');
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        /** @var Model $user */
        $user = $event->user;
        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
            ])
            ->log('Password was reset');
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
        $events->listen(Failed::class, [self::class, 'handleFailed']);
        $events->listen(PasswordReset::class, [self::class, 'handlePasswordReset']);
    }
}
