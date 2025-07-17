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
use App\Filament\Widgets\UserVerificationStats;
use App\Filament\Widgets\EmailStats;
use App\Filament\Widgets\EmailActivityChart;
use App\Filament\Widgets\PayoutStatsOverview;
use App\Filament\Resources\EmailEventResource;
use App\Filament\Resources\EmailSuppressionResource;
use App\Filament\Resources\EmailTestResource;
use App\Filament\Resources\EmailAuditResource;
use App\Filament\Resources\MarketplaceTemplateResource;
use App\Filament\Resources\PayoutScheduleResource;
use App\Filament\Resources\StripeTransactionResource;
use App\Filament\Resources\FileUploadSettingResource;
use App\Filament\Resources\SubscriptionLimitResource;
use App\Filament\Pages\EmailAuditPage;
use App\Filament\Pages\EmailSuppressionPage;
use App\Filament\Pages\PayoutHoldSettings;
use App\Filament\Pages\FileUploadSettingsManager;
use App\Filament\Widgets\HoldPeriodStatsWidget;
use App\Filament\Widgets\FileUploadSettingsOverview;
use App\Filament\Plugins\BillingPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->homeUrl('/dashboard')
            ->colors([
                'primary' => [
                    50 => '#f0f4ff',
                    100 => '#e0e7ff',
                    200 => '#c7d2fe',
                    300 => '#a5b4fc',
                    400 => '#818cf8',
                    500 => '#6875f5',
                    600 => '#5a67d8',
                    700 => '#4c51bf',
                    800 => '#434190',
                    900 => '#3730a3',
                    950 => '#1e1b4b',
                ],
                'success' => [
                    50 => '#f0fdf4',
                    100 => '#dcfce7',
                    200 => '#bbf7d0',
                    300 => '#86efac',
                    400 => '#4ade80',
                    500 => '#00ef2b',
                    600 => '#16a34a',
                    700 => '#15803d',
                    800 => '#166534',
                    900 => '#14532d',
                ],
                'danger' => Color::Rose,
                'warning' => Color::Orange,
                'info' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->favicon(asset('favicon.ico'))
            ->brandName('MixPitch')
            ->brandLogo(asset('images/logo-admin.svg'))
            ->brandLogoHeight('2.5rem')
            ->navigationGroups([
                'Analytics' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible(),
                'Content Management' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Content Management')
                    ->icon('heroicon-o-document-duplicate')
                    ->collapsible(),
                'User Management' => \Filament\Navigation\NavigationGroup::make()
                    ->label('User Management')
                    ->icon('heroicon-o-users')
                    ->collapsible(),
                'Email Management' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Email Management')
                    ->icon('heroicon-o-envelope')
                    ->collapsible(),
                'Financial' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Financial')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible(),
                'Subscriptions' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Subscriptions')
                    ->icon('heroicon-o-credit-card')
                    ->collapsible(),
                'System' => \Filament\Navigation\NavigationGroup::make()
                    ->label('System')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ])
            ->resources([
                ProjectResource::class,
                PitchResource::class,
                ProjectFileResource::class,
                UserResource::class,
                PayoutScheduleResource::class,
                StripeTransactionResource::class,
                EmailAuditResource::class,
                EmailEventResource::class,
                EmailSuppressionResource::class,
                EmailTestResource::class,
                MarketplaceTemplateResource::class,
                FileUploadSettingResource::class,
                SubscriptionLimitResource::class,
            ])
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\Analytics::class,
                Settings::class,
                EmailAuditPage::class,
                EmailSuppressionPage::class,
                PayoutHoldSettings::class,
                FileUploadSettingsManager::class,
            ])
            ->widgets([
                // Core metrics - shown first
                StatsOverview::class,
                \App\Filament\Widgets\PayoutStatsOverview::class,
                \App\Filament\Widgets\PayoutManagementWidget::class,
                HoldPeriodStatsWidget::class,
                ProjectStats::class,
                UserVerificationStats::class,
                EmailStats::class,
                FileUploadSettingsOverview::class,
                
                // Activity and trend widgets
                UserActivity::class,
                EmailActivityChart::class,
                
                // Content overview widgets
                LatestProjects::class,
                LatestPitches::class,
                FilesOverview::class,
                
                // User account widget - always last
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
            ->authGuard('web')
            ->passwordReset()
            ->emailVerification()
            ->login()
            ->profile(isSimple: false)
            ->userMenuItems([
                'settings' => \Filament\Navigation\MenuItem::make()
                    ->label('Settings')
                    ->url(fn (): string => route('filament.admin.pages.settings'))
                    ->icon('heroicon-m-cog-6-tooth'),
                'view-site' => \Filament\Navigation\MenuItem::make()
                    ->label('View Site')
                    ->url('/')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->maxContentWidth('full')
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldSuffix(fn (): ?string => match (PHP_OS_FAMILY) {
                'Darwin' => '⌘K',
                default => 'Ctrl+K',
            })
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->spa()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
                BillingPlugin::make(),
            ]);
    }
}
