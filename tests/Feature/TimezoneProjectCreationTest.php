<?php

namespace Tests\Feature;

use App\Livewire\CreateProject;
use App\Livewire\ManageProject;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimezoneProjectCreationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_correctly_converts_standard_project_deadline_to_utc_on_creation()
    {
        // Create user in Mountain timezone (UTC-7 in summer)
        $user = User::factory()->create(['timezone' => 'America/Denver']);
        $this->actingAs($user);

        // User enters 2:00 PM in their timezone (tomorrow to ensure it's in the future)
        $tomorrow = now()->addDay()->format('Y-m-d');
        $userLocalTime = $tomorrow.'T14:00';

        $component = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->set('form.name', 'Test Project')
            ->set('form.description', 'Test Description')
            ->set('form.projectType', 'single')
            ->set('form.genre', 'Pop')
            ->set('form.budgetType', 'paid')
            ->set('form.budget', 500)
            ->set('form.deadline', $userLocalTime)
            ->call('save');

        // Check that project was created
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
        ]);

        $project = Project::where('name', 'Test Project')->first();

        // User entered 2:00 PM MDT, which should be 8:00 PM UTC (14:00 + 6 hours)
        $expectedUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 20:00:00', 'UTC');
        $this->assertEquals($expectedUtc->format('Y-m-d H:i:s'), $project->deadline->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_correctly_converts_contest_deadlines_to_utc_on_creation()
    {
        // Create user in Eastern timezone (UTC-4 in summer)
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $this->actingAs($user);

        // User enters deadlines in their timezone (future dates)
        $submissionDate = now()->addDays(3)->format('Y-m-d');
        $judgingDate = now()->addDays(7)->format('Y-m-d');
        $submissionDeadline = $submissionDate.'T16:00'; // 4:00 PM EDT
        $judgingDeadline = $judgingDate.'T18:00';       // 6:00 PM EDT

        $component = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_CONTEST)
            ->set('form.name', 'Test Contest')
            ->set('form.description', 'Test Contest Description')
            ->set('form.projectType', 'single')
            ->set('form.genre', 'Pop')
            ->set('form.budgetType', 'paid')
            ->set('form.budget', 1000)
            ->set('submission_deadline', $submissionDeadline)
            ->set('judging_deadline', $judgingDeadline)
            ->call('save');

        $project = Project::where('name', 'Test Contest')->first();

        // 4:00 PM EDT should be 8:00 PM UTC (16:00 + 4 hours)
        $expectedSubmissionUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 20:00:00', 'UTC');
        $this->assertEquals($expectedSubmissionUtc->format('Y-m-d H:i:s'), $project->submission_deadline->format('Y-m-d H:i:s'));

        // 6:00 PM EDT should be 10:00 PM UTC (18:00 + 4 hours)
        $expectedJudgingUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-20 22:00:00', 'UTC');
        $this->assertEquals($expectedJudgingUtc->format('Y-m-d H:i:s'), $project->judging_deadline->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_correctly_loads_standard_project_deadline_for_editing()
    {
        // Create user in Pacific timezone
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $this->actingAs($user);

        // Create a project with UTC deadline
        $utcDeadline = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 22:00:00', 'UTC');
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'deadline' => $utcDeadline,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

        // Load the project for editing
        $component = Livewire::test(CreateProject::class, ['project' => $project]);

        // 10:00 PM UTC should be displayed as 3:00 PM PDT (UTC-7 in summer)
        $expectedLocalFormat = '2024-07-15T15:00';
        $this->assertEquals($expectedLocalFormat, $component->get('form.deadline'));
    }

    /** @test */
    public function it_correctly_loads_contest_deadlines_for_editing_in_create_project()
    {
        // Create user in Central timezone
        $user = User::factory()->create(['timezone' => 'America/Chicago']);
        $this->actingAs($user);

        // Create contest with UTC deadlines
        $submissionUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 19:00:00', 'UTC');
        $judgingUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-20 21:00:00', 'UTC');

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => $submissionUtc,
            'judging_deadline' => $judgingUtc,
        ]);

        // Load the project for editing
        $component = Livewire::test(CreateProject::class, ['project' => $project]);

        // 7:00 PM UTC should be displayed as 2:00 PM CDT (UTC-5 in summer)
        $expectedSubmissionLocal = '2024-07-15T14:00';
        $this->assertEquals($expectedSubmissionLocal, $component->get('submission_deadline'));

        // 9:00 PM UTC should be displayed as 4:00 PM CDT
        $expectedJudgingLocal = '2024-07-20T16:00';
        $this->assertEquals($expectedJudgingLocal, $component->get('judging_deadline'));
    }

    /** @test */
    public function it_correctly_loads_contest_deadlines_for_editing_in_manage_project()
    {
        // Create user in Eastern timezone
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $this->actingAs($user);

        // Create contest with UTC deadlines
        $submissionUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 20:00:00', 'UTC');
        $judgingUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-20 22:00:00', 'UTC');

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => $submissionUtc,
            'judging_deadline' => $judgingUtc,
        ]);

        // Load the project for editing in ManageProject
        $component = Livewire::test(ManageProject::class, ['project' => $project]);

        // 8:00 PM UTC should be displayed as 4:00 PM EDT (UTC-4 in summer)
        $expectedSubmissionLocal = '2024-07-15T16:00';
        $this->assertEquals($expectedSubmissionLocal, $component->get('submission_deadline'));

        // 10:00 PM UTC should be displayed as 6:00 PM EDT
        $expectedJudgingLocal = '2024-07-20T18:00';
        $this->assertEquals($expectedJudgingLocal, $component->get('judging_deadline'));
    }

    /** @test */
    public function it_correctly_updates_contest_deadlines_via_manage_project()
    {
        // Create user in Mountain timezone
        $user = User::factory()->create(['timezone' => 'America/Denver']);
        $this->actingAs($user);

        // Create existing contest
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => now()->addDays(5),
            'judging_deadline' => now()->addDays(10),
        ]);

        // Update deadlines via ManageProject
        $newSubmissionDeadline = '2024-08-15T13:00'; // 1:00 PM MDT
        $newJudgingDeadline = '2024-08-20T15:00';    // 3:00 PM MDT

        $component = Livewire::test(ManageProject::class, ['project' => $project])
            ->set('submission_deadline', $newSubmissionDeadline)
            ->set('judging_deadline', $newJudgingDeadline)
            ->call('updateProjectDetails');

        $project->refresh();

        // 1:00 PM MDT should be 7:00 PM UTC (13:00 + 6 hours)
        $expectedSubmissionUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-08-15 19:00:00', 'UTC');
        $this->assertEquals($expectedSubmissionUtc->format('Y-m-d H:i:s'), $project->submission_deadline->format('Y-m-d H:i:s'));

        // 3:00 PM MDT should be 9:00 PM UTC (15:00 + 6 hours)
        $expectedJudgingUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-08-20 21:00:00', 'UTC');
        $this->assertEquals($expectedJudgingUtc->format('Y-m-d H:i:s'), $project->judging_deadline->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_handles_different_timezones_correctly_for_same_local_time()
    {
        // Test that the same local time in different timezones creates different UTC times

        // Pacific user enters 2:00 PM
        $pacificUser = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $this->actingAs($pacificUser);

        $component1 = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->set('form.name', 'Pacific Project')
            ->set('form.description', 'Test')
            ->set('form.projectType', 'single')
            ->set('form.genre', 'Pop')
            ->set('form.budgetType', 'free')
            ->set('form.deadline', '2024-07-15T14:00')
            ->call('save');

        // Eastern user enters 2:00 PM
        $easternUser = User::factory()->create(['timezone' => 'America/New_York']);
        $this->actingAs($easternUser);

        $component2 = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->set('form.name', 'Eastern Project')
            ->set('form.description', 'Test')
            ->set('form.projectType', 'single')
            ->set('form.genre', 'Pop')
            ->set('form.budgetType', 'free')
            ->set('form.deadline', '2024-07-15T14:00')
            ->call('save');

        $pacificProject = Project::where('name', 'Pacific Project')->first();
        $easternProject = Project::where('name', 'Eastern Project')->first();

        // Pacific 2:00 PM should be 9:00 PM UTC (14:00 + 7 hours PDT)
        $expectedPacificUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 21:00:00', 'UTC');
        $this->assertEquals($expectedPacificUtc->format('Y-m-d H:i:s'), $pacificProject->deadline->format('Y-m-d H:i:s'));

        // Eastern 2:00 PM should be 6:00 PM UTC (14:00 + 4 hours EDT)
        $expectedEasternUtc = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 18:00:00', 'UTC');
        $this->assertEquals($expectedEasternUtc->format('Y-m-d H:i:s'), $easternProject->deadline->format('Y-m-d H:i:s'));

        // The UTC times should be different even though users entered the same local time
        $this->assertNotEquals($pacificProject->deadline->format('Y-m-d H:i:s'), $easternProject->deadline->format('Y-m-d H:i:s'));
    }
}
