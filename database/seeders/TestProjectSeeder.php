<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(1);

        if (! $user) {
            $this->command->error('User with ID 1 not found. Please ensure user ID 1 exists before running this seeder.');

            return;
        }

        $this->command->info('Creating test projects for user: '.$user->name);

        // Create 2 Standard Workflow Projects
        $this->createStandardProjects($user);

        // Create 2 Contest Projects
        $this->createContestProjects($user);

        // Create 2 Client Management Projects
        $this->createClientManagementProjects($user);

        $this->command->info('Test projects created successfully!');
    }

    private function createStandardProjects(User $user): void
    {
        $this->command->info('Creating Standard workflow projects...');

        // Standard Project 1 - Pop Single
        Project::factory()
            ->for($user)
            ->configureWorkflow(Project::WORKFLOW_TYPE_STANDARD)
            ->create([
                'name' => 'Pop Single - Need Professional Mix',
                'description' => 'Looking for a professional mix for my latest pop single. The track has modern pop elements with electronic drums and synthesizers. Need someone who specializes in contemporary pop production. Tempo: 128 BPM, Key: C Major, Mood: Upbeat and energetic. Reference: Think Dua Lipa meets The Weeknd.',
                'genre' => 'Pop',
                'artist_name' => 'Sarah Mitchell',
                'project_type' => 'single',
                'collaboration_type' => ['Mixing'],
                'budget' => 500,
                'deadline' => now()->addWeeks(3),
            ]);

        // Standard Project 2 - Rock EP
        Project::factory()
            ->for($user)
            ->configureWorkflow(Project::WORKFLOW_TYPE_STANDARD)
            ->published()
            ->create([
                'name' => 'Indie Rock EP - Full Production Needed',
                'description' => 'I have 4 demo tracks for an indie rock EP that need full production, mixing, and mastering. Looking for someone with experience in modern indie rock production. Tempo: 110-140 BPM range, Keys: Various, Mood: Alternative and atmospheric. References: Arctic Monkeys, Tame Impala, Mac DeMarco.',
                'genre' => 'Rock',
                'artist_name' => 'The River Bend',
                'project_type' => 'ep',
                'collaboration_type' => ['Production', 'Mixing', 'Mastering'],
                'budget' => 1500,
                'deadline' => now()->addMonths(2),
            ]);
    }

    private function createContestProjects(User $user): void
    {
        $this->command->info('Creating Contest workflow projects...');

        // Contest Project 1 - Beat Making Contest
        Project::factory()
            ->for($user)
            ->configureWorkflow(Project::WORKFLOW_TYPE_CONTEST, [
                'submission_deadline' => now()->addWeeks(2),
                'judging_deadline' => now()->addWeeks(3),
                'prize_amount' => 750,
            ])
            ->published()
            ->create([
                'name' => 'Hip-Hop Beat Making Contest 2024',
                'description' => 'Calling all producers! Create an original hip-hop beat that captures the essence of modern trap/drill music. Winner gets $750 and potential collaboration opportunities. Tempo: 140-160 BPM, Keys: Minor keys preferred, Mood: Dark, aggressive, modern. References: 21 Savage, Future, Travis Scott style beats.',
                'genre' => 'Hip Hop',
                'artist_name' => 'MC FlowMaster',
                'project_type' => 'single',
                'collaboration_type' => ['Production'],
                'budget' => 750,
                'show_submissions_publicly' => true,
            ]);

        // Contest Project 2 - Remix Contest
        Project::factory()
            ->for($user)
            ->configureWorkflow(Project::WORKFLOW_TYPE_CONTEST, [
                'submission_deadline' => now()->addDays(10),
                'judging_deadline' => now()->addWeeks(2),
                'prize_amount' => 500,
            ])
            ->published()
            ->create([
                'name' => 'Official Remix Contest - "Midnight Dreams"',
                'description' => 'Remix my track "Midnight Dreams" in any electronic genre. Original stems will be provided. Looking for creative interpretation and professional quality. Tempo: 120-130 BPM, Key: D Minor, Mood: Dreamy, ethereal, danceable. References: ODESZA, Flume, Disclosure.',
                'genre' => 'Electronic',
                'artist_name' => 'Luna Waves',
                'project_type' => 'single',
                'collaboration_type' => ['Production', 'Mixing'],
                'budget' => 500,
                'show_submissions_publicly' => false,
            ]);
    }

    private function createClientManagementProjects(User $user): void
    {
        $this->command->info('Creating Client Management workflow projects...');

        // Client Management Project 1 - Corporate Music
        Project::factory()
            ->for($user)
            ->configureWorkflow(Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT, [
                'client_email' => 'marketing@techcorp.com',
                'client_name' => 'TechCorp Marketing Team',
            ])
            ->create([
                'name' => 'Corporate Video Background Music',
                'description' => 'Need original background music for a corporate promotional video. Must be uplifting, professional, and copyright-free. 2-3 minute duration. Tempo: 110 BPM, Key: Major key, Mood: Professional, uplifting, inspiring. Style: Corporate presentation music, motivational.',
                'genre' => 'Corporate',
                'artist_name' => 'Professional Audio Solutions',
                'project_type' => 'soundtrack',
                'collaboration_type' => ['Composition', 'Production'],
                'budget' => 800,
                'deadline' => now()->addWeeks(4),
                'payment_amount' => 800,
            ]);

        // Client Management Project 2 - Wedding Music
        Project::factory()
            ->for($user)
            ->configureWorkflow(Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT, [
                'client_email' => 'jennifer.smith@email.com',
                'client_name' => 'Jennifer & Michael Smith',
            ])
            ->create([
                'name' => 'Custom Wedding Ceremony Music',
                'description' => 'Looking for a custom arrangement of "Canon in D" for our wedding ceremony. Need acoustic guitar and string ensemble version. Very special occasion. Tempo: 60 BPM, Key: D Major, Mood: Romantic, elegant, timeless. Reference: Pachelbel Canon in D, acoustic versions.',
                'genre' => 'Classical',
                'artist_name' => 'Harmony Strings Studio',
                'project_type' => 'arrangement',
                'collaboration_type' => ['Arrangement', 'Recording'],
                'budget' => 600,
                'deadline' => now()->addMonths(1),
                'payment_amount' => 600,
            ]);
    }
}
