<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FilamentAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Find the first user (or create one if none exist, though usually seeded earlier)
        $adminUser = User::first(); 

        if ($adminUser) {
            // Set the role directly on the user model
            $adminUser->role = User::ROLE_ADMIN;
            $adminUser->save();
            Log::info('FilamentAdminSeeder: Assigned admin role to user ID ' . $adminUser->id);
        } else {
            Log::warning('FilamentAdminSeeder: No users found to assign admin role.');
            // Optionally, you could create an admin user here if necessary
            // User::factory()->create([
            //     'name' => 'Admin User',
            //     'email' => 'admin@example.com', 
            //     'password' => bcrypt('password'), 
            //     'role' => User::ROLE_ADMIN,
            // ]);
        }

        // Remove all previous Spatie permission/role logic
    }
} 