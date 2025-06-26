<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\UserActivity;
use App\Filament\Widgets\LatestProjects;
use App\Filament\Widgets\LatestPitches;
use App\Filament\Widgets\FilesOverview;
use App\Filament\Widgets\ProjectStats;
use Filament\Widgets\AccountWidget;
use Filament\Pages\Page;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $view = 'filament.pages.dashboard';
    
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $title = 'Dashboard';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }
    
    public static function getNavigationGroup(): ?string
    {
        return null;
    }
    
    public static function getNavigationSort(): ?int
    {
        return -2;
    }
    
    public function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            ProjectStats::class,
        ];
    }
    
    public function hasHeaderWidgets(): bool
    {
        return count($this->getHeaderWidgets()) > 0;
    }
    
    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
        ];
    }
    
    public function getHeaderWidgetsData(): array
    {
        return [];
    }
    
    public function getFooterWidgets(): array
    {
        return [
            AccountWidget::class,
        ];
    }
    
    public function hasFooterWidgets(): bool
    {
        return count($this->getFooterWidgets()) > 0;
    }
    
    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }
    
    public function getFooterWidgetsData(): array
    {
        return [];
    }
    
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
        ];
    }
    
    public function getWidgets(): array
    {
        return [
            LatestProjects::class,
            LatestPitches::class,
            FilesOverview::class,
            UserActivity::class,
        ];
    }
    
    public function getWidgetData(): array
    {
        return [];
    }

    public function getHeading(): string
    {
        $user = auth()->user();
        $greeting = $this->getGreeting();
        
        return "{$greeting}, {$user->name}! 👋";
    }
    
    public function getSubheading(): ?string
    {
        return 'Welcome to your MixPitch admin dashboard. Here\'s what\'s happening on your platform today.';
    }
    
    private function getGreeting(): string
    {
        $hour = now()->hour;
        
        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 17) {
            return 'Good afternoon';
        } else {
            return 'Good evening';
        }
    }
    
    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('view_site')
                ->label('View Site')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url('/')
                ->openUrlInNewTab(),
            \Filament\Actions\Action::make('analytics')
                ->label('Analytics')
                ->icon('heroicon-m-chart-bar')
                ->color('primary')
                ->url(route('filament.admin.pages.analytics')),
        ];
    }
} 