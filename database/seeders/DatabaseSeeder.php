<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\MigrateUserTagsSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\LicenseTemplateSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Seed Filament admin roles and permissions
        $this->call(FilamentAdminSeeder::class);

        $this->call([
            ProjectTypeSeeder::class,  // Seed project types first
            SubscriptionLimitsSeeder::class, // Seed subscription limits for storage system
            MigrateUserTagsSeeder::class,
            TagSeeder::class,
            LicenseTemplateSeeder::class, // Seed license templates
        ]);
    }
}
