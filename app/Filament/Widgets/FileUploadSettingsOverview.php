<?php

namespace App\Filament\Widgets;

use App\Models\FileUploadSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FileUploadSettingsOverview extends BaseWidget
{
    protected static ?int $sort = 15;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $globalSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $projectsSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        $pitchesSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PITCHES);
        $clientPortalsSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_CLIENT_PORTALS);

        // Count custom settings (non-default)
        $customSettingsCount = FileUploadSetting::count();

        return [
            Stat::make('Max File Size (Global)', $globalSettings['max_file_size_mb'].' MB')
                ->description('Global maximum file size limit')
                ->descriptionIcon('heroicon-m-cloud-arrow-up')
                ->color('primary'),

            Stat::make('Chunking Status', $globalSettings['enable_chunking'] ? 'Enabled' : 'Disabled')
                ->description('Chunked uploads for large files')
                ->descriptionIcon($globalSettings['enable_chunking'] ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($globalSettings['enable_chunking'] ? 'success' : 'warning'),

            Stat::make('Custom Settings', $customSettingsCount)
                ->description('Context-specific overrides')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color($customSettingsCount > 0 ? 'info' : 'gray'),

            Stat::make('Projects Max Size', $projectsSettings['max_file_size_mb'].' MB')
                ->description('Project upload limit')
                ->descriptionIcon('heroicon-m-folder')
                ->color('blue'),

            Stat::make('Pitches Max Size', $pitchesSettings['max_file_size_mb'].' MB')
                ->description('Pitch upload limit')
                ->descriptionIcon('heroicon-m-musical-note')
                ->color('green'),

            Stat::make('Client Portal Max Size', $clientPortalsSettings['max_file_size_mb'].' MB')
                ->description('Client portal upload limit')
                ->descriptionIcon('heroicon-m-users')
                ->color('purple'),
        ];
    }

    public function getDisplayName(): string
    {
        return 'File Upload Settings';
    }
}
