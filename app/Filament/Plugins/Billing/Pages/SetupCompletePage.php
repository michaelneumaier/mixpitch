<?php

namespace App\Filament\Plugins\Billing\Pages;

use Filament\Pages\Page;

class SetupCompletePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static string $view = 'filament.pages.billing.setup-complete';

    protected static ?string $title = 'Billing Setup';

    protected static ?string $navigationLabel = 'Billing Setup';

    protected static ?string $slug = 'billing-setup';

    protected static ?int $navigationSort = 81;

    protected static ?string $navigationGroup = 'Account';
}
