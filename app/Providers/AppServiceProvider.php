<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Listeners\ImpersonationEventSubscriber;
use App\Models\LeaveRequest;
use App\Observers\LeaveRequestObserver;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LeaveRequest::observe(LeaveRequestObserver::class);

        Event::subscribe(AuthEventSubscriber::class);
        Event::subscribe(ImpersonationEventSubscriber::class);

        $this->configureDefaults();

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['en','ckb','ar'])
                    ->flags([
                        'en' => asset('flags/en.svg'),
                        'ckb' => asset('flags/ckb.svg'),      
                        'ar' => asset('flags/iq.svg')
                    ])
                    ->circular();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
