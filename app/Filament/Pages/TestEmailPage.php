<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TestEmailPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Email Management';

    protected static string $view = 'filament.pages.test-email-page';

    protected static ?string $title = 'Test Email Tool';

    protected static ?string $navigationLabel = 'Quick Email Test';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->can('view_email_tests') || auth()->user()->hasRole('admin'));
    }
}
