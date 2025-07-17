<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function project_owner_cannot_upload_to_completed_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'user')->create([
            'status' => Project::STATUS_COMPLETED
        ]);

        $policy = new \App\Policies\ProjectFilePolicy();
        $this->assertFalse($policy->uploadFile($user, $project));
    }

    /** @test */
    public function project_owner_can_upload_to_open_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'user')->create([
            'status' => Project::STATUS_OPEN
        ]);

        $policy = new \App\Policies\ProjectFilePolicy();
        $this->assertTrue($policy->uploadFile($user, $project));
    }

    /** @test */
    public function pitch_owner_cannot_upload_to_completed_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_COMPLETED]);

        $this->assertFalse($pitchOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function pitch_owner_cannot_upload_to_denied_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_DENIED]);

        $this->assertFalse($pitchOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function pitch_owner_can_upload_to_in_progress_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertTrue($pitchOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function pitch_owner_can_upload_to_revisions_requested_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);

        $this->assertTrue($pitchOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function contest_pitch_owner_cannot_upload_after_winning()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST
        ]);
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_CONTEST_WINNER]);

        $this->assertFalse($pitchOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function contest_pitch_owner_can_upload_during_entry_period()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => now()->addDays(1), // Contest still open
        ]);
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_CONTEST_ENTRY]);

        $this->assertTrue($pitchOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function non_owner_cannot_upload_to_any_project()
    {
        $projectOwner = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'status' => Project::STATUS_OPEN
        ]);

        $policy = new \App\Policies\ProjectFilePolicy();
        $this->assertFalse($policy->uploadFile($otherUser, $project));
    }

    /** @test */
    public function non_owner_cannot_upload_to_any_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchOwner = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchOwner, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($otherUser->can('uploadFile', $pitch));
    }
}