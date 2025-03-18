<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\PitchResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\ProjectFileResource;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Settings;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\ProjectStats;
use App\Filament\Widgets\UserActivity;
use App\Filament\Widgets\LatestProjects;
use App\Filament\Widgets\LatestPitches;
use App\Filament\Widgets\FilesOverview;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Blue,
            ])
            ->font('Inter')
            ->favicon(asset('favicon.ico'))
            ->navigationGroups([
                'Content Management',
                'User Management',
                'System Settings',
            ])
            ->resources([
                ProjectResource::class,
                PitchResource::class,
                ProjectFileResource::class,
                UserResource::class,
            ])
            ->pages([
                Dashboard::class,
                Settings::class,
            ])
            ->widgets([
                // Overview widgets for high-level metrics
                StatsOverview::class,
                ProjectStats::class,
                
                // Activity widgets showing recent events
                UserActivity::class,
                
                // Detail widgets showing specific content
                LatestProjects::class,
                LatestPitches::class,
                FilesOverview::class,
                
                // User account widget
                Widgets\AccountWidget::class,
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
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearch()
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
