<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Actions\Action;
use Filament\Enums\GlobalSearchPosition;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Filament\Support\Facades\FilamentView;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            fn (): View => view('filament.pages.footer-admin-panel'),
        );
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Taupe,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop(true)
            ->maxContentWidth(Width::Full)
            ->spa(true)
            ->unsavedChangesAlerts()
            ->font('Noto Kufi Arabic')
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label('Edit Profile & Settings')
                    ->url('/admin/edit-profile')
                    ->icon('heroicon-s-pencil-square'),
                'logout' => fn (Action $action) => $action
                    ->label('Log out')
                    ->icon('heroicon-s-arrow-left-start-on-rectangle')
                    ->color('danger')
            ])
            ->favicon(asset('favicon.ico'))
            ->revealablePasswords(true)
            ->brandName(config('app.name'))
            ->navigationGroups([
                'Main',
                'Employees',
                'Organization',
                'Leave Management',
                'Attendance',
                'Payroll & Compensation',
                NavigationGroup::make('Reports')
                    ->icon('heroicon-o-chart-bar-square')
                    ->collapsible(true)
                    ->extraSidebarAttributes([
                        'class' => 'reports-nav-group',
                    ]),
                'Settings',
            ])
            ->globalSearch(position: GlobalSearchPosition::Topbar)
            ->plugins([
                FilamentShieldPlugin::make()
            ]);
    }
}
