<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyOAuthUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oauth:verify-users {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify email addresses for OAuth users who should be auto-verified';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Find OAuth users who are not verified
        $unverifiedOAuthUsers = User::whereNotNull('provider')
            ->whereNotNull('provider_id')
            ->whereNull('email_verified_at')
            ->get();

        if ($unverifiedOAuthUsers->isEmpty()) {
            $this->info('No unverified OAuth users found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$unverifiedOAuthUsers->count()} unverified OAuth users:");

        $headers = ['ID', 'Name', 'Email', 'Provider', 'Created At'];
        $rows = [];

        foreach ($unverifiedOAuthUsers as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->provider,
                $user->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);

        if ($dryRun) {
            $this->warn('DRY RUN: No changes were made. Remove --dry-run to actually verify these users.');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Do you want to verify all these OAuth users?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $verified = 0;
        foreach ($unverifiedOAuthUsers as $user) {
            try {
                $user->markEmailAsVerified();
                $this->line("✓ Verified {$user->name} ({$user->email})");
                $verified++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to verify {$user->name} ({$user->email}): {$e->getMessage()}");
            }
        }

        $this->info("Successfully verified {$verified} OAuth users.");

        return Command::SUCCESS;
    }
} 