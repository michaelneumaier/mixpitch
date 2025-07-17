<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SetDefaultTimezoneSeeder extends Seeder
{
    /**
     * Set default timezone for existing users.
     */
    public function run(): void
    {
        $count = User::whereNull('timezone')->update([
            'timezone' => 'America/New_York',
        ]);

        $this->command->info("Updated timezone for {$count} existing users.");
    }
}
