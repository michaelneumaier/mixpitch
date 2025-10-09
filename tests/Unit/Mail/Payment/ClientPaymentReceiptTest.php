<?php

namespace Tests\Unit\Mail\Payment;

use App\Mail\Payment\ClientPaymentReceipt;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_with_correct_subject_line()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'title' => 'Epic Production',
            'client_email' => 'client@example.com',
        ]);
        $amount = 250.00;
        $currency = 'USD';
        $transactionId = 'pi_1234567890';

        $mailable = new ClientPaymentReceipt(
            $project,
            'John Client',
            $amount,
            $currency,
            $transactionId,
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        $mailable->assertHasSubject('Payment Confirmation - Epic Production - USD 250.00');
    }

    /** @test */
    public function it_includes_payment_details()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $transactionId = 'pi_test123';

        $mailable = new ClientPaymentReceipt(
            $project,
            'Jane',
            500.50,
            'USD',
            $transactionId,
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        $mailable->assertSeeInHtml('USD 500.50');
        $mailable->assertSeeInHtml($transactionId);
        $mailable->assertSeeInHtml('Payment Details');
    }

    /** @test */
    public function it_uses_correct_template()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);

        $mailable = new ClientPaymentReceipt(
            $project,
            'Client',
            100.00,
            'USD',
            'pi_123',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        $this->assertEquals(
            'emails.payment.client_receipt',
            $mailable->content()->markdown
        );
    }

    /** @test */
    public function it_includes_invoice_and_portal_links()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $invoiceUrl = 'https://example.com/invoice/abc123';
        $portalUrl = 'https://example.com/portal/def456';

        $mailable = new ClientPaymentReceipt(
            $project,
            'Client',
            100.00,
            'USD',
            'pi_123',
            $invoiceUrl,
            $portalUrl
        );

        $mailable->assertSeeInHtml($invoiceUrl, false);
        $mailable->assertSeeInHtml($portalUrl, false);
        $mailable->assertSeeInHtml('View Invoice');
        $mailable->assertSeeInHtml('Access Deliverables');
    }

    /** @test */
    public function it_handles_null_client_name_gracefully()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);

        $mailable = new ClientPaymentReceipt(
            $project,
            null, // No client name
            100.00,
            'USD',
            'pi_123',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        $mailable->assertSeeInHtml('Hello there');
    }

    /** @test */
    public function it_formats_amount_with_two_decimal_places()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);

        $mailable = new ClientPaymentReceipt(
            $project,
            'Client',
            75.5, // Should display as 75.50
            'USD',
            'pi_123',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        // Use assertSeeInText for reliable number format matching
        $mailable->assertSeeInText('USD 75.50');
    }

    /** @test */
    public function it_includes_producer_name_in_content()
    {
        $producer = User::factory()->create(['name' => 'Super Producer']);
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);

        $mailable = new ClientPaymentReceipt(
            $project,
            'Client',
            100.00,
            'USD',
            'pi_123',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        $mailable->assertSeeInHtml('Super Producer');
    }

    /** @test */
    public function it_includes_record_keeping_reminder()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);

        $mailable = new ClientPaymentReceipt(
            $project,
            'Client',
            100.00,
            'USD',
            'pi_123',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        $mailable->assertSeeInHtml('save this email for your records');
    }
}
