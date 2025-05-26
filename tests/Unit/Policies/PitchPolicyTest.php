<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Policies\PitchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PitchPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function createPolicy(): PitchPolicy
    {
        return new PitchPolicy();
    }

    // Test cases for selectWinner
    /** @test */
    public function selectWinner_allows_project_owner_for_contest_entry()
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_ENTRY]);
        $policy = new PitchPolicy();

        $this->assertTrue($policy->selectWinner($owner, $pitch));
    }

    /** @test */
    public function selectWinner_denies_project_owner_for_non_entry_status()
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_WINNER]);
        $policy = new PitchPolicy();

        $this->assertFalse($policy->selectWinner($owner, $pitch));
    }

    /** @test */
    public function selectWinner_denies_project_owner_for_non_contest_project()
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_STANDARD]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_ENTRY]);
        $policy = new PitchPolicy();

        $this->assertFalse($policy->selectWinner($owner, $pitch));
    }

    /** @test */
    public function selectWinner_denies_other_user()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_ENTRY]);
        $policy = new PitchPolicy();

        $this->assertFalse($policy->selectWinner($otherUser, $pitch));
    }

    // Test cases for selectRunnerUp
    /** @test */
    public function selectRunnerUp_allows_project_owner_for_contest_entry()
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_ENTRY]);
        $policy = new PitchPolicy();

        $this->assertTrue($policy->selectRunnerUp($owner, $pitch));
    }

    /** @test */
    public function selectRunnerUp_denies_project_owner_for_non_entry_status()
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_WINNER]);
        $policy = new PitchPolicy();

        $this->assertFalse($policy->selectRunnerUp($owner, $pitch));
    }

    /** @test */
    public function selectRunnerUp_denies_project_owner_for_non_contest_project()
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_STANDARD]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_ENTRY]);
        $policy = new PitchPolicy();

        $this->assertFalse($policy->selectRunnerUp($owner, $pitch));
    }

    /** @test */
    public function selectRunnerUp_denies_other_user()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id, 'workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->create(['project_id' => $project->id, 'user_id' => $producer->id, 'status' => Pitch::STATUS_CONTEST_ENTRY]);
        $policy = new PitchPolicy();

        $this->assertFalse($policy->selectRunnerUp($otherUser, $pitch));
    }

    // TODO: Add tests for other relevant policy methods if needed
} 