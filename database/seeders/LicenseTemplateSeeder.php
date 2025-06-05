<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LicenseTemplate;
use App\Models\User;

class LicenseTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating system license templates...');
        
        // Create system-wide marketplace templates
        $systemTemplates = $this->createSystemTemplates();
        
        $this->command->info('Created ' . count($systemTemplates) . ' system templates');
        
        // Create default templates for existing users who don't have any
        $this->createDefaultTemplatesForExistingUsers();
    }
    
    /**
     * Create system-wide templates for the marketplace
     */
    private function createSystemTemplates(): array
    {
        $systemUser = User::where('email', 'system@mixpitch.com')->first();
        
        // Create system user if it doesn't exist
        if (!$systemUser) {
            $systemUser = User::create([
                'name' => 'MixPitch System',
                'email' => 'system@mixpitch.com',
                'password' => bcrypt('system-account-not-accessible'),
                'email_verified_at' => now(),
            ]);
        }

        $templates = [];
        
        // 1. Basic Collaboration License
        $templates[] = LicenseTemplate::updateOrCreate(
            ['name' => 'Basic Collaboration', 'user_id' => $systemUser->id],
            [
                'content' => "MUSIC COLLABORATION LICENSE\n\n" .
                           "Project: [PROJECT_NAME]\n" .
                           "Project Owner: [PROJECT_OWNER]\n" .
                           "Date: [DATE]\n\n" .
                           "TERMS:\n" .
                           "✓ Commercial use allowed\n" .
                           "✓ Modification and editing permitted\n" .
                           "✓ Credit appreciated but not required\n" .
                           "✗ Cannot resell original files\n" .
                           "✗ Cannot use for sample libraries\n\n" .
                           "This license is perpetual and non-transferable.",
                'category' => LicenseTemplate::CATEGORY_GENERAL,
                'use_case' => LicenseTemplate::USE_CASE_COLLABORATION,
                'description' => 'Standard license for most music collaboration projects. Allows commercial use without attribution requirements.',
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => false,
                    'modification_allowed' => true,
                    'distribution_allowed' => false,
                    'standalone_distribution_prohibited' => true,
                    'sample_library_creation_prohibited' => true,
                    'streaming_allowed' => true,
                    'territory' => 'worldwide',
                    'duration' => 'perpetual',
                ],
                'is_system_template' => true,
                'is_public' => true,
                'approval_status' => 'approved',
                'approved_by' => $systemUser->id,
                'approved_at' => now(),
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
            ]
        );

        // 2. Sync Ready Pro License
        $templates[] = LicenseTemplate::updateOrCreate(
            ['name' => 'Sync Ready Pro', 'user_id' => $systemUser->id],
            [
                'content' => "SYNC LICENSING AGREEMENT\n\n" .
                           "Project: [PROJECT_NAME]\n" .
                           "Project Owner: [PROJECT_OWNER]\n" .
                           "Date: [DATE]\n\n" .
                           "SYNC RIGHTS GRANTED:\n" .
                           "✓ Unlimited commercial use\n" .
                           "✓ Broadcast and streaming rights\n" .
                           "✓ Film, TV, and advertising use\n" .
                           "✓ Online content and social media\n" .
                           "✓ Modification for sync purposes\n" .
                           "✗ Cannot resell as standalone music\n\n" .
                           "Perfect for content creators, filmmakers, and advertisers.",
                'category' => LicenseTemplate::CATEGORY_MUSIC,
                'use_case' => LicenseTemplate::USE_CASE_SYNC,
                'description' => 'Comprehensive license for sync, media, and broadcast use. Ideal for content creators and media production.',
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => false,
                    'modification_allowed' => true,
                    'sync_licensing_allowed' => true,
                    'broadcast_allowed' => true,
                    'streaming_allowed' => true,
                    'distribution_allowed' => false,
                    'standalone_distribution_prohibited' => true,
                    'territory' => 'worldwide',
                    'duration' => 'perpetual',
                ],
                'industry_tags' => ['film', 'tv', 'advertising', 'content-creation'],
                'is_system_template' => true,
                'is_public' => true,
                'approval_status' => 'approved',
                'approved_by' => $systemUser->id,
                'approved_at' => now(),
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
            ]
        );

        // 3. Attribution Required License
        $templates[] = LicenseTemplate::updateOrCreate(
            ['name' => 'Commercial with Attribution', 'user_id' => $systemUser->id],
            [
                'content' => "ATTRIBUTION LICENSE\n\n" .
                           "Project: [PROJECT_NAME]\n" .
                           "Project Owner: [PROJECT_OWNER]\n" .
                           "Date: [DATE]\n\n" .
                           "TERMS:\n" .
                           "✓ Full commercial use permitted\n" .
                           "✓ Modification and editing allowed\n" .
                           "✓ Must credit [PROJECT_OWNER] in all uses\n" .
                           "✗ Cannot resell original files\n" .
                           "✗ Attribution cannot be removed\n\n" .
                           "REQUIRED CREDIT FORMAT:\n" .
                           "Music by [PROJECT_OWNER] (MixPitch)",
                'category' => LicenseTemplate::CATEGORY_GENERAL,
                'use_case' => LicenseTemplate::USE_CASE_COMMERCIAL,
                'description' => 'Commercial license requiring attribution in all uses. Great for building your brand and recognition.',
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => true,
                    'credit_format' => 'Music by [PROJECT_OWNER] (MixPitch)',
                    'modification_allowed' => true,
                    'distribution_allowed' => false,
                    'standalone_distribution_prohibited' => true,
                    'streaming_allowed' => true,
                    'territory' => 'worldwide',
                    'duration' => 'perpetual',
                ],
                'is_system_template' => true,
                'is_public' => true,
                'approval_status' => 'approved',
                'approved_by' => $systemUser->id,
                'approved_at' => now(),
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
            ]
        );

        // 4. Sample Pack License
        $templates[] = LicenseTemplate::updateOrCreate(
            ['name' => 'Sample Pack Pro', 'user_id' => $systemUser->id],
            [
                'content' => "SAMPLE LICENSING AGREEMENT\n\n" .
                           "Project: [PROJECT_NAME]\n" .
                           "Project Owner: [PROJECT_OWNER]\n" .
                           "Date: [DATE]\n\n" .
                           "SAMPLE RIGHTS:\n" .
                           "✓ Use in unlimited musical compositions\n" .
                           "✓ Commercial release of derivative works\n" .
                           "✓ Modification and chopping allowed\n" .
                           "✓ Combine with other samples/music\n" .
                           "✗ Cannot redistribute original samples\n" .
                           "✗ Cannot use for competing sample packs\n\n" .
                           "Royalty-free for all derivative musical works.",
                'category' => LicenseTemplate::CATEGORY_MUSIC,
                'use_case' => LicenseTemplate::USE_CASE_SAMPLES,
                'description' => 'Specialized license for sample packs and loops. Allows unlimited use in musical compositions.',
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => false,
                    'modification_allowed' => true,
                    'distribution_allowed' => false,
                    'standalone_distribution_prohibited' => true,
                    'sample_library_creation_prohibited' => true,
                    'streaming_allowed' => true,
                    'territory' => 'worldwide',
                    'duration' => 'perpetual',
                ],
                'industry_tags' => ['samples', 'loops', 'production', 'beats'],
                'is_system_template' => true,
                'is_public' => true,
                'approval_status' => 'approved',
                'approved_by' => $systemUser->id,
                'approved_at' => now(),
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
            ]
        );

        // 5. Remix & Edit License
        $templates[] = LicenseTemplate::updateOrCreate(
            ['name' => 'Remix & Edit License', 'user_id' => $systemUser->id],
            [
                'content' => "REMIX LICENSE AGREEMENT\n\n" .
                           "Project: [PROJECT_NAME]\n" .
                           "Project Owner: [PROJECT_OWNER]\n" .
                           "Date: [DATE]\n\n" .
                           "REMIX RIGHTS:\n" .
                           "✓ Create remixes and derivative versions\n" .
                           "✓ Commercial release of remixes\n" .
                           "✓ Substantial modification permitted\n" .
                           "✓ Add new elements and arrangements\n" .
                           "✓ Credit original artist encouraged\n" .
                           "✗ Cannot claim original composition ownership\n\n" .
                           "Perfect for remix contests and collaborative reworks.",
                'category' => LicenseTemplate::CATEGORY_MUSIC,
                'use_case' => LicenseTemplate::USE_CASE_REMIX,
                'description' => 'License for remixes and edits. Allows substantial modification while protecting original composition rights.',
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => false,
                    'modification_allowed' => true,
                    'distribution_allowed' => true,
                    'standalone_distribution_prohibited' => false,
                    'streaming_allowed' => true,
                    'territory' => 'worldwide',
                    'duration' => 'perpetual',
                ],
                'industry_tags' => ['remix', 'edit', 'derivative', 'contest'],
                'is_system_template' => true,
                'is_public' => true,
                'approval_status' => 'approved',
                'approved_by' => $systemUser->id,
                'approved_at' => now(),
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
            ]
        );

        return $templates;
    }
    
    /**
     * Create default templates for existing users who don't have any
     */
    private function createDefaultTemplatesForExistingUsers(): void
    {
        $usersWithoutTemplates = User::whereDoesntHave('licenseTemplates')->get();
        
        $this->command->info("Creating default templates for {$usersWithoutTemplates->count()} users...");
        
        foreach ($usersWithoutTemplates as $user) {
            LicenseTemplate::createDefaultTemplatesForUser($user);
        }
        
        $this->command->info('Default templates created for existing users');
    }
} 