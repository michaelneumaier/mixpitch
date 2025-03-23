<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Run ZIP cleanup once per week to remove old archives
        $schedule->command('zips:cleanup --days=30')
                ->weekly()
                ->sundays()
                ->at('01:00')
                ->appendOutputTo(storage_path('logs/zip-cleanup.log'));
                
        // Run temporary uploads cleanup daily to prevent buildup of orphaned files
        $schedule->command('uploads:cleanup --days=1')
                ->daily()
                ->at('03:00')
                ->appendOutputTo(storage_path('logs/uploads-cleanup.log'));

        // Sync invoices from Stripe daily to ensure we have the latest data
        $schedule->command('stripe:sync-invoices --all')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
