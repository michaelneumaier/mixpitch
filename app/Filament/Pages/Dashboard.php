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

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';
    
    protected static ?string $navigationIcon = 'heroicon-m-home';
    
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
            'sm' => 2,
            'md' => 2,
            'lg' => 2,
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
} 