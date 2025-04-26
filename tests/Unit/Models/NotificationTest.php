<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user, 'user')->create(['slug' => 'test-project', 'name' => 'Setup Test Project']);
        $this->pitch = Pitch::factory()->for($this->project)->for($this->user, 'user')->create(['slug' => 'test-pitch']);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $notification = Notification::factory()->for($this->user)->create();

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($this->user->id, $notification->user->id);
    }

    /** @test */
    public function it_can_have_a_related_model_pitch()
    {
        $notification = Notification::factory()
            ->for($this->user)
            ->create([
                'related_id' => $this->pitch->id,
                'related_type' => Pitch::class,
                'type' => Notification::TYPE_PITCH_SUBMITTED,
            ]);

        $this->assertInstanceOf(Pitch::class, $notification->related);
        $this->assertEquals($this->pitch->id, $notification->related->id);
    }

    /** @test */
    public function it_can_have_a_related_model_project()
    {
        // Although typically notifications relate to Pitches, testing polymorphism
        $notification = Notification::factory()
            ->for($this->user)
            ->create([
                'related_id' => $this->project->id,
                'related_type' => Project::class,
                'type' => 'project_notification', // Example type
            ]);

        $this->assertInstanceOf(Project::class, $notification->related);
        $this->assertEquals($this->project->id, $notification->related->id);
    }

    /** @test */
    public function mark_as_read_sets_read_at_timestamp()
    {
        $notification = Notification::factory()->for($this->user)->create(['read_at' => null]);

        $this->assertNull($notification->read_at);
        $notification->markAsRead();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function is_read_returns_correct_status()
    {
        $readNotification = Notification::factory()->for($this->user)->create(['read_at' => now()]);
        $unreadNotification = Notification::factory()->for($this->user)->create(['read_at' => null]);

        $this->assertTrue($readNotification->isRead());
        $this->assertFalse($unreadNotification->isRead());
    }

    /** @test */
    public function unread_scope_returns_only_unread_notifications()
    {
        Notification::factory()->count(3)->for($this->user)->create(['read_at' => now()]);
        Notification::factory()->count(2)->for($this->user)->create(['read_at' => null]);

        $this->assertCount(5, Notification::all());
        $this->assertCount(2, Notification::unread()->get());
    }

    /** @test */
    public function get_url_returns_correct_pitch_url()
    {
        // Ensure project and pitch have slugs
        $this->project->slug = 'test-project-slug';
        $this->project->save();
        $this->pitch->slug = 'test-pitch-slug';
        $this->pitch->project_id = $this->project->id; // Ensure association
        $this->pitch->save();

        $notification = Notification::factory()->for($this->user)->create([
            'related_id' => $this->pitch->id,
            'related_type' => Pitch::class,
            'type' => Notification::TYPE_PITCH_SUBMITTED,
        ]);

        // Use the correct route and pass models for implicit binding
        $expectedUrl = route('projects.pitches.show', ['project' => $this->project, 'pitch' => $this->pitch]);
        $this->assertEquals($expectedUrl, $notification->getUrl());
    }

    /** @test */
    public function get_url_returns_correct_pitch_file_comment_url()
    {
        $pitchFile = PitchFile::factory()->for($this->pitch)->create();
        $commentId = 123;

        $notification = Notification::factory()->for($this->user)->create([
            'related_id' => $pitchFile->id,
            'related_type' => PitchFile::class,
            'type' => Notification::TYPE_PITCH_FILE_COMMENT,
            'data' => ['comment_id' => $commentId]
        ]);

        $expectedUrl = route('pitch-files.show', $pitchFile) . '#comment-' . $commentId;
        $this->assertEquals($expectedUrl, $notification->getUrl());
    }

    /** @test */
    public function get_url_returns_dashboard_url_as_fallback()
    {
        // Notification with unrelated type or missing related object
        $notification = Notification::factory()->for($this->user)->create([
            'related_id' => 9999, // Non-existent ID
            'related_type' => Pitch::class,
            'type' => Notification::TYPE_PITCH_SUBMITTED,
        ]);

        $expectedUrl = route('dashboard');
        $this->assertEquals($expectedUrl, $notification->getUrl());
    }

    // --- getReadableDescription Tests ---

    /**
     * @dataProvider notificationDescriptionProvider
     * @test
     */
    public function get_readable_description_returns_correct_string(string $type, array $dataTemplate, string $relatedModelClass, array $expectedStringContains)
    {
        // Create related models dynamically based on class
        $relatedModel = null;
        $testSubmitter = User::factory()->create(['name' => 'Test Submitter Name']);
        $testProject = $this->project; // Use project from setup or create new if needed
        $testPitch = $this->pitch;     // Use pitch from setup
        $testPitchFile = PitchFile::factory()->for($testPitch)->create(['original_file_name' => 'audio-test.mp3']);

        // Prepare data by replacing placeholders
        $data = collect($dataTemplate)->map(function ($value) use ($testSubmitter, $testProject, $testPitch, $testPitchFile) {
            return match ($value) {
                '{submitterName}' => $testSubmitter->name,
                '{projectName}' => $testProject->name,
                '{pitchTitle}' => 'Pitch Title Placeholder', // Use placeholder if title is missing
                '{fileName}' => $testPitchFile->original_file_name,
                '{approverName}' => $testSubmitter->name, // Use submitter as example approver
                '{uploaderName}' => $testSubmitter->name, // Use submitter as example uploader
                '{amount}' => '100.00',
                default => $value, // Keep literal values (like status)
            };
        })->all();

        // Create the related model instance for the notification
        $relatedModel = match ($relatedModelClass) {
            Pitch::class => $testPitch,
            PitchFile::class => $testPitchFile,
            default => $testPitch, // Default case
        };

        $notification = Notification::factory()
            ->for($this->user) // The user receiving the notification
            ->relatedTo($relatedModel)
            ->create([
                'type' => $type,
                'data' => $data,
            ]);

        $description = $notification->getReadableDescription();

        // Prepare expected strings
        $resolvedExpected = collect($expectedStringContains)->map(function ($value) use ($testSubmitter, $testProject, $testPitch, $testPitchFile, $data) {
             return match ($value) {
                '{submitterName}' => $testSubmitter->name,
                '{projectName}' => $testProject->name,
                '{pitchTitle}' => 'Pitch Title Placeholder',
                '{fileName}' => $testPitchFile->original_file_name,
                '{approverName}' => $testSubmitter->name,
                '{uploaderName}' => $testSubmitter->name,
                '{amount}' => '$100.00', // Match formatting in description method
                '{status}' => $data['status'] ?? '',
                default => $value,
            };
        })->all();

        foreach ($resolvedExpected as $expected) {
            $this->assertStringContainsString($expected, $description);
        }
    }

    public static function notificationDescriptionProvider(): array
    {
        // Define templates and expectations using placeholders
        return [
            'pitch_submitted' => [
                Notification::TYPE_PITCH_SUBMITTED,
                ['project_name' => '{projectName}', 'submitter_name' => '{submitterName}'],
                Pitch::class,
                ['{submitterName}', 'submitted a pitch for project', '{projectName}']
            ],
            'pitch_status_change_approved' => [
                Notification::TYPE_PITCH_STATUS_CHANGE,
                ['status' => Pitch::STATUS_APPROVED, 'project_name' => '{projectName}'],
                Pitch::class,
                ['Pitch status updated to "approved" for project', '{projectName}']
            ],
            'pitch_comment' => [
                Notification::TYPE_PITCH_COMMENT,
                ['commenter_name' => '{submitterName}', 'project_name' => '{projectName}'],
                Pitch::class,
                ['{submitterName}', 'commented on your pitch for project', '{projectName}']
            ],
            'pitch_file_comment' => [
                Notification::TYPE_PITCH_FILE_COMMENT,
                ['commenter_name' => '{submitterName}'],
                PitchFile::class,
                ['{submitterName}', 'commented on your audio file']
            ],
            'snapshot_approved' => [
                Notification::TYPE_SNAPSHOT_APPROVED,
                ['project_name' => '{projectName}'],
                Pitch::class,
                ['Your snapshot for pitch on project', '{projectName}', 'was approved']
            ],
            'file_uploaded' => [
                Notification::TYPE_FILE_UPLOADED,
                ['uploader_name' => '{uploaderName}', 'file_name' => '{fileName}', 'project_name' => '{projectName}'],
                Pitch::class,
                ['{uploaderName}', 'uploaded', '{fileName}', 'to a pitch on project', '{projectName}']
            ],
            'payment_processed' => [
                Notification::TYPE_PAYMENT_PROCESSED,
                ['project_name' => '{projectName}', 'amount' => '{amount}'],
                Pitch::class,
                ['Payment processed for your pitch on project', '{projectName}']
            ],
        ];
    }

    /** @test */
    public function get_readable_description_handles_missing_data()
    {
        // Notification with existing type but empty data
        $notification = Notification::factory()->for($this->user)->create([
            'related_id' => $this->pitch->id,
            'related_type' => Pitch::class,
            'type' => Notification::TYPE_PITCH_SUBMITTED, // Use a valid type
            'data' => [], // Empty data
        ]);

        // Should still return a generic message without errors
        $description = $notification->getReadableDescription();
        // Update assertion to match the new default for PITCH_SUBMITTED
        $this->assertEquals('A producer submitted a pitch for project', $description); 
        $this->assertStringNotContainsString('""', $description); // Ensure project name fallback doesn't show empty quotes
    }

} 