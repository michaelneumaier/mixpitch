<?php

namespace Tests\Feature\Policies;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PitchFilePolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $projectOwner;

    private User $pitchCreator;

    private User $unrelatedUser;

    private Project $project;

    private Pitch $pitchInProgress;

    private Pitch $pitchRevisionsRequested;

    private Pitch $pitchApproved; // A status where uploads/deletes are generally disallowed

    private PitchFile $pitchFileOnInProgress;

    private PitchFile $pitchFileOnApproved;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectOwner = User::factory()->create();
        $this->pitchCreator = User::factory()->create();
        $this->unrelatedUser = User::factory()->create();

        $this->project = Project::factory()->for($this->projectOwner, 'user')->create();

        $this->pitchInProgress = Pitch::factory()
            ->for($this->project)
            ->for($this->pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->pitchRevisionsRequested = Pitch::factory()
            ->for($this->project)
            ->for($this->pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);

        $this->pitchApproved = Pitch::factory()
            ->for($this->project)
            ->for($this->pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            ]);

        $this->pitchFileOnInProgress = PitchFile::factory()
            ->for($this->pitchInProgress, 'pitch')
            ->create(['user_id' => $this->pitchCreator->id]); // Associate file with creator

        $this->pitchFileOnApproved = PitchFile::factory()
            ->for($this->pitchApproved, 'pitch')
            ->create(['user_id' => $this->pitchCreator->id]);
    }

    // --- view Tests ---

    /** @test */
    public function project_owner_can_view_pitch_file()
    {
        $this->assertTrue($this->projectOwner->can('view', $this->pitchFileOnInProgress));
    }

    /** @test */
    public function pitch_creator_can_view_pitch_file()
    {
        $this->assertTrue($this->pitchCreator->can('view', $this->pitchFileOnInProgress));
    }

    /** @test */
    public function unrelated_user_cannot_view_pitch_file()
    {
        $this->assertFalse($this->unrelatedUser->can('view', $this->pitchFileOnInProgress));
    }

    // --- deleteFile Tests ---

    /** @test */
    public function pitch_creator_can_delete_file_when_pitch_in_progress()
    {
        $this->assertTrue($this->pitchCreator->can('deleteFile', $this->pitchFileOnInProgress));
    }

    /** @test */
    public function pitch_creator_can_delete_file_when_pitch_revisions_requested()
    {
        // Create a file specifically for the revisions requested pitch
        $fileOnRevisions = PitchFile::factory()->for($this->pitchRevisionsRequested)->create(['user_id' => $this->pitchCreator->id]);
        $this->assertTrue($this->pitchCreator->can('deleteFile', $fileOnRevisions));
    }

    /** @test */
    public function pitch_creator_cannot_delete_file_when_pitch_in_other_statuses()
    {
        // Example: Approved status
        $this->assertFalse($this->pitchCreator->can('deleteFile', $this->pitchFileOnApproved));

        // Add other disallowed statuses if needed
        $pitchCompleted = Pitch::factory()->for($this->project)->for($this->pitchCreator, 'user')->create(['status' => Pitch::STATUS_COMPLETED]);
        $fileOnCompleted = PitchFile::factory()->for($pitchCompleted)->create(['user_id' => $this->pitchCreator->id]);
        $this->assertFalse($this->pitchCreator->can('deleteFile', $fileOnCompleted));
    }

    /** @test */
    public function project_owner_cannot_delete_pitch_file()
    {
        $this->assertFalse($this->projectOwner->can('deleteFile', $this->pitchFileOnInProgress));
    }

    /** @test */
    public function unrelated_user_cannot_delete_pitch_file()
    {
        $this->assertFalse($this->unrelatedUser->can('deleteFile', $this->pitchFileOnInProgress));
    }

    // --- downloadFile Tests ---

    /** @test */
    public function project_owner_can_download_pitch_file()
    {
        // Project owners can only download files from accepted, completed, and paid pitches
        // They cannot download from IN_PROGRESS pitches per policy
        $this->assertFalse($this->projectOwner->can('downloadFile', $this->pitchFileOnInProgress));

        // But they CAN download from approved pitches (after payment)
        $this->assertTrue($this->projectOwner->can('downloadFile', $this->pitchFileOnApproved));
    }

    /** @test */
    public function pitch_creator_can_download_pitch_file()
    {
        $this->assertTrue($this->pitchCreator->can('downloadFile', $this->pitchFileOnInProgress));
    }

    /** @test */
    public function unrelated_user_cannot_download_pitch_file()
    {
        $this->assertFalse($this->unrelatedUser->can('downloadFile', $this->pitchFileOnInProgress));
    }
}
