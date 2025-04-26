<?php

namespace Tests\Unit\Mail;

use App\Mail\GenericNotificationEmail;
use App\Models\Notification;
use App\Models\User;
use App\Models\Pitch; // Assuming Notification::getUrl might relate to Pitch
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GenericNotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_builds_with_content_resolved_from_notification_model(): void
    {
        Mail::fake();

        $user = User::factory()->create(['name' => 'Test User']);
        $pitch = Pitch::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_PITCH_SUBMITTED, // Example type
            'related_id' => $pitch->id,
            'related_type' => Pitch::class,
            'data' => [
                'project_name' => $pitch->project->name ?? 'Default Project Name',
                'producer_name' => $user->name,
                // Add other data relevant to getReadableDescription/getUrl if needed
            ]
        ]);

        // Manually get expected values (might need adjustment based on actual methods)
        $expectedDescription = $notification->getReadableDescription();
        $expectedUrl = $notification->getUrl();
        $expectedSubject = config('app.name') . ': ' . strip_tags($expectedDescription);

        $mailable = new GenericNotificationEmail(
            $user,
            $notification->type,
            $notification->data,
            $notification->id
        );

        // Assert internal properties are set correctly after resolveContent
        $this->assertEquals($expectedDescription, $mailable->description);
        $this->assertEquals($expectedUrl, $mailable->actionUrl);
        $this->assertEquals($expectedSubject, $mailable->envelope()->subject);

        // Assert rendering
        $mailable->assertHasSubject($expectedSubject);
        // Check description is present, ignoring HTML escaping
        $mailable->assertSeeInHtml($expectedDescription, false);
        // Assuming default action text is 'View Details'
        $mailable->assertSeeInHtml($expectedUrl, false); // Check URL is present
        $mailable->assertSeeInHtml('View Details', false); // Check button text is present
    }

    /** @test */
    public function it_builds_with_fallback_content_when_notification_not_found(): void
    {
        Mail::fake();

        $user = User::factory()->create(['name' => 'Test User']);
        $notificationType = 'some_random_type';
        $notificationData = ['info' => 'test'];
        $nonExistentNotificationId = 9999;

        $expectedSubject = config('app.name') . ': You have a new notification';
        $expectedDescription = 'You received a notification of type: ' . $notificationType . '. Please log in to view details.';
        $expectedUrl = route('dashboard');

        $mailable = new GenericNotificationEmail(
            $user,
            $notificationType,
            $notificationData,
            $nonExistentNotificationId // Use non-existent ID
        );

        // Assert internal properties use fallback
        $this->assertStringContainsString('You have a new notification', $mailable->subjectLine);
        $this->assertEquals($expectedDescription, $mailable->description);
        $this->assertEquals($expectedUrl, $mailable->actionUrl);
        $this->assertEquals($expectedSubject, $mailable->envelope()->subject);

        // Assert rendering
        $mailable->assertHasSubject($expectedSubject);
        $mailable->assertSeeInHtml($expectedDescription);
        $mailable->assertSeeInHtml($expectedUrl, false);
        $mailable->assertSeeInHtml('View Details', false);
    }

    /** @test */
    public function it_uses_correct_markdown_view(): void
    {
        $user = User::factory()->create();
        $mailable = new GenericNotificationEmail($user, 'test', [], null);

        $this->assertEquals('emails.notifications.generic', $mailable->content()->markdown);
    }
} 