<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\ServicePackage;
use App\Models\User;
use App\Notifications\Orders\OrderCancelled;
use App\Notifications\Orders\OrderCompleted;
use App\Notifications\Orders\OrderDelivered;
use App\Notifications\Orders\OrderRequirementsSubmitted;
use App\Notifications\Orders\RevisionRequested;
use App\Services\OrderWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(OrderWorkflowService::class);
        Notification::fake();
    }

    /** @test */
    public function submit_requirements_updates_order_status_and_creates_event()
    {
        // Create client, producer and service package
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create an order in the PENDING_REQUIREMENTS status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_PENDING_REQUIREMENTS,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // Define requirements text
        $requirementsText = 'These are my detailed requirements for the service.';

        // Call the service method
        $result = $this->service->submitRequirements($order, $client, $requirementsText);

        // Assert the result is the updated order
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals(Order::STATUS_IN_PROGRESS, $result->status);
        $this->assertEquals($requirementsText, $result->requirements_submitted);

        // Assert an event was created
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $client->id,
            'event_type' => OrderEvent::EVENT_REQUIREMENTS_SUBMITTED,
            'status_from' => Order::STATUS_PENDING_REQUIREMENTS,
            'status_to' => Order::STATUS_IN_PROGRESS,
        ]);

        // Check that notifications would be sent (actual notification check should be in a Feature test)
        Notification::assertSentTo(
            $producer,
            OrderRequirementsSubmitted::class
        );
    }

    /** @test */
    public function submit_requirements_throws_exception_for_incorrect_user()
    {
        $this->expectException(AuthorizationException::class);

        // Create client, producer and service package
        $client = User::factory()->create(['role' => 'owner']);
        $wrongUser = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create an order
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_PENDING_REQUIREMENTS,
        ]);

        // Call with the wrong user - should throw AuthorizationException
        $this->service->submitRequirements($order, $wrongUser, 'Requirements text');
    }

    /** @test */
    public function deliver_order_uploads_files_updates_status_and_creates_event()
    {
        // Create client, producer and service package
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create an order in the IN_PROGRESS status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'requirements_submitted' => 'Requirements text',
        ]);

        // Mock file data
        $uploadedFilesData = [
            [
                'path' => 'orders/1/deliveries/file1.pdf',
                'name' => 'file1.pdf',
                'mime' => 'application/pdf',
                'size' => 1024,
            ],
            [
                'path' => 'orders/1/deliveries/file2.jpg',
                'name' => 'file2.jpg',
                'mime' => 'image/jpeg',
                'size' => 2048,
            ],
        ];

        $deliveryMessage = 'Here is the delivery with two files attached.';

        // Call the service method
        $result = $this->service->deliverOrder($order, $producer, $uploadedFilesData, $deliveryMessage);

        // Assert the result is the updated order
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals(Order::STATUS_READY_FOR_REVIEW, $result->status);

        // Assert an event was created
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $producer->id,
            'event_type' => OrderEvent::EVENT_DELIVERY_SUBMITTED,
            'status_from' => Order::STATUS_IN_PROGRESS,
            'status_to' => Order::STATUS_READY_FOR_REVIEW,
        ]);

        // Check that the comment contains the delivery message
        $event = OrderEvent::where('order_id', $order->id)
            ->where('event_type', OrderEvent::EVENT_DELIVERY_SUBMITTED)
            ->first();
        $this->assertNotNull($event);
        $this->assertStringContainsString($deliveryMessage, $event->comment);

        // Assert files were created in the database
        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'uploader_user_id' => $producer->id,
            'file_path' => 'orders/1/deliveries/file1.pdf',
            'file_name' => 'file1.pdf',
            'mime_type' => 'application/pdf',
            'type' => 'delivery',
        ]);

        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'uploader_user_id' => $producer->id,
            'file_path' => 'orders/1/deliveries/file2.jpg',
            'file_name' => 'file2.jpg',
            'mime_type' => 'image/jpeg',
            'type' => 'delivery',
        ]);

        // Check notifications would be sent
        Notification::assertSentTo(
            $client,
            OrderDelivered::class
        );
    }

    /** @test */
    public function request_revision_increments_count_updates_status_and_creates_event()
    {
        // Create client, producer and service package with revisions
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
            'revisions_included' => 2, // Allow 2 revisions
        ]);

        // Create an order in the READY_FOR_REVIEW status with no revisions used yet
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_READY_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'revision_count' => 0,
        ]);

        $revisionFeedback = 'Please make these changes to the deliverables.';

        // Call the service method
        $result = $this->service->requestRevision($order, $client, $revisionFeedback);

        // Assert the result is the updated order
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals(Order::STATUS_REVISIONS_REQUESTED, $result->status);
        $this->assertEquals(1, $result->revision_count); // Should be incremented

        // Assert an event was created with the feedback
        $event = OrderEvent::where('order_id', $order->id)
            ->where('event_type', OrderEvent::EVENT_REVISIONS_REQUESTED)
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals($client->id, $event->user_id);
        $this->assertStringContainsString($revisionFeedback, $event->comment);

        // Check notifications would be sent
        Notification::assertSentTo(
            $producer,
            RevisionRequested::class
        );
    }

    /** @test */
    public function request_revision_throws_exception_when_revision_limit_reached()
    {
        $this->expectException(\Exception::class); // Or use a more specific exception if available

        // Create client, producer and service package with no revisions
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
            'revisions_included' => 1, // Allow only 1 revision
        ]);

        // Create an order that has already used its one revision
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_READY_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'revision_count' => 1, // Already used the revision
        ]);

        // Should throw exception due to revision limit
        $this->service->requestRevision($order, $client, 'This should fail');
    }

    /** @test */
    public function accept_delivery_completes_order_and_creates_event()
    {
        // Create client, producer and service package
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create an order in the READY_FOR_REVIEW status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_READY_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // Call the service method
        $result = $this->service->acceptDelivery($order, $client);

        // Assert the result is the updated order
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals(Order::STATUS_COMPLETED, $result->status);
        $this->assertNotNull($result->completed_at);

        // Assert an event was created
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $client->id,
            'event_type' => OrderEvent::EVENT_DELIVERY_ACCEPTED,
            'status_from' => Order::STATUS_READY_FOR_REVIEW,
            'status_to' => Order::STATUS_COMPLETED,
        ]);

        // Check notifications would be sent
        Notification::assertSentTo(
            $producer,
            OrderCompleted::class
        );
    }

    /** @test */
    public function cancel_order_updates_status_and_creates_cancellation_event()
    {
        // Create client, producer and service package
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create an order in a cancellable status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        $cancellationReason = 'Project requirements have changed.';

        // Call the service method
        $result = $this->service->cancelOrder($order, $client, $cancellationReason);

        // Assert the result is the updated order
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals(Order::STATUS_CANCELLED, $result->status);
        $this->assertNotNull($result->cancelled_at);

        // Assert an event was created
        $event = OrderEvent::where('order_id', $order->id)
            ->where('event_type', OrderEvent::EVENT_ORDER_CANCELLED)
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals($client->id, $event->user_id);
        $this->assertStringContainsString($cancellationReason, $event->comment);

        // Check notifications would be sent
        Notification::assertSentTo(
            $producer,
            OrderCancelled::class
        );
    }
}
