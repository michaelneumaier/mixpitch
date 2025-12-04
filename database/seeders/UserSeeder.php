<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pre-hashed password for '92Colum4607'
        // This bcrypt hash will correctly verify against the plaintext password
        $passwordHash = '$2y$10$cyc6myXu0AaICH/WfxuKwO7/WkRZzwYJz70Lq2xa6zK21uJICKm8e';

        // Create MixPitch System user (will be assigned admin role by FilamentAdminSeeder)
        User::updateOrCreate(
            ['email' => 'system@mixpitch.com'],
            [
                'name' => 'MixPitch System',
                'password' => $passwordHash,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Created/Updated: MixPitch System (system@mixpitch.com)');

        // Create test user
        User::updateOrCreate(
            ['email' => 'test@test.com'],
            [
                'name' => 'test',
                'password' => $passwordHash,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Created/Updated: test (test@test.com)');

        $this->command->info('All users created with password: 92Colum4607');
    }
}
