<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users FIRST - other seeders depend on them
        $this->call(UserSeeder::class);

        // Seed Filament admin roles and permissions (requires users to exist)
        $this->call(FilamentAdminSeeder::class);

        $this->call([
            ProjectTypeSeeder::class,  // Seed project types first
            SubscriptionLimitsSeeder::class, // Seed subscription limits for storage system
            MigrateUserTagsSeeder::class,
            TagSeeder::class,
            LicenseTemplateSeeder::class, // Seed license templates
            TestProjectSeeder::class, // Seed test projects for development/testing (requires users)
        ]);
    }
}
