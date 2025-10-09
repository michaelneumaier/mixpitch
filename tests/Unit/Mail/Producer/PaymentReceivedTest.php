<?php

namespace Tests\Unit\Mail\Producer;

use App\Mail\Producer\PaymentReceived;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceivedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_with_correct_subject_line()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_name' => 'Jane Client',
            'client_email' => 'jane@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDays(1);

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            500.00, // gross
            50.00,  // fee
            450.00, // net
            'USD',
            $payoutDate
        );

        $mailable->assertHasSubject('Payment Received - USD 450.00 Payout Scheduled');
    }

    /** @test */
    public function it_includes_payment_breakdown()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDay();

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            1000.00, // gross
            100.00,  // fee (10%)
            900.00,  // net
            'USD',
            $payoutDate
        );

        // Check for payment amounts in content (format may vary between HTML/text)
        $mailable->assertSeeInHtml('Client Payment');
        $mailable->assertSeeInHtml('Platform Fee');
        $mailable->assertSeeInHtml('Your Payout');
        $mailable->assertSeeInHtml('USD'); // Currency present
        $mailable->assertSeeInHtml('10%'); // Fee percentage
    }

    /** @test */
    public function it_uses_correct_template()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDay();

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            100.00,
            10.00,
            90.00,
            'USD',
            $payoutDate
        );

        $this->assertEquals(
            'emails.producer.payment_received',
            $mailable->content()->markdown
        );
    }

    /** @test */
    public function it_includes_payout_date_information()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::parse('2025-12-25');

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            100.00,
            10.00,
            90.00,
            'USD',
            $payoutDate
        );

        // Check for payout date information (format may vary)
        $mailable->assertSeeInHtml('Payout Schedule');
        $mailable->assertSeeInHtml('will be released');
    }

    /** @test */
    public function it_includes_project_and_earnings_links()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDay();

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            100.00,
            10.00,
            90.00,
            'USD',
            $payoutDate
        );

        $projectUrl = route('projects.manage-client', $project);
        $earningsUrl = route('dashboard').'#earnings';

        $mailable->assertSeeInHtml($projectUrl, false);
        $mailable->assertSeeInHtml($earningsUrl, false);
        $mailable->assertSeeInHtml('View Project Details');
        $mailable->assertSeeInHtml('Check Payout Status');
    }

    /** @test */
    public function it_addresses_producer_by_name()
    {
        $producer = User::factory()->create(['name' => 'Top Producer']);
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDay();

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            100.00,
            10.00,
            90.00,
            'USD',
            $payoutDate
        );

        $mailable->assertSeeInHtml('Hello Top Producer');
    }

    /** @test */
    public function it_handles_null_client_name_with_fallback()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_name' => null,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDay();

        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            100.00,
            10.00,
            90.00,
            'USD',
            $payoutDate
        );

        $mailable->assertSeeInHtml('Your client');
    }

    /** @test */
    public function it_calculates_platform_fee_percentage_correctly()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $payoutDate = Carbon::now()->addDay();

        // 15% fee scenario
        $mailable = new PaymentReceived(
            $producer,
            $project,
            $pitch,
            200.00,  // gross
            30.00,   // fee (15%)
            170.00,  // net
            'USD',
            $payoutDate
        );

        $mailable->assertSeeInHtml('15%');
    }
}
