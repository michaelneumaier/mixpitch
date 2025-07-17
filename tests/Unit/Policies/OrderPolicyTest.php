<?php

namespace Tests\Unit\Policies;

use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrderPolicy;
    }

    /** @test */
    public function client_can_view_their_orders()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $otherUser = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create order
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
        ]);

        // Assert client can view the order
        $this->assertTrue($this->policy->view($client, $order));

        // Assert other user cannot view the order
        $this->assertFalse($this->policy->view($otherUser, $order));
    }

    /** @test */
    public function producer_can_view_their_orders()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $otherProducer = User::factory()->create(['role' => 'producer']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create order
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
        ]);

        // Assert producer can view the order
        $this->assertTrue($this->policy->view($producer, $order));

        // Assert other producer cannot view the order
        $this->assertFalse($this->policy->view($otherProducer, $order));
    }

    /** @test */
    public function client_can_submit_requirements_in_correct_status()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create order in PENDING_REQUIREMENTS status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_PENDING_REQUIREMENTS,
        ]);

        // Assert client can submit requirements
        $this->assertTrue($this->policy->submitRequirements($client, $order));

        // Assert producer cannot submit requirements
        $this->assertFalse($this->policy->submitRequirements($producer, $order));

        // Update order to a different status
        $order->status = Order::STATUS_IN_PROGRESS;
        $order->save();

        // Assert client cannot submit requirements in wrong status
        $this->assertFalse($this->policy->submitRequirements($client, $order));
    }

    /** @test */
    public function producer_can_deliver_order_in_correct_status()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create order in IN_PROGRESS status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_IN_PROGRESS,
        ]);

        // Assert producer can deliver
        $this->assertTrue($this->policy->deliverOrder($producer, $order));

        // Assert client cannot deliver
        $this->assertFalse($this->policy->deliverOrder($client, $order));

        // Update to REVISIONS_REQUESTED status
        $order->status = Order::STATUS_REVISIONS_REQUESTED;
        $order->save();

        // Assert producer can still deliver
        $this->assertTrue($this->policy->deliverOrder($producer, $order));

        // Update to a non-deliverable status
        $order->status = Order::STATUS_COMPLETED;
        $order->save();

        // Assert producer cannot deliver in completed status
        $this->assertFalse($this->policy->deliverOrder($producer, $order));
    }

    /** @test */
    public function client_can_request_revision_in_correct_status_and_within_limits()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);

        // Create service package with 1 revision
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
            'revisions_included' => 1,
        ]);

        // Create order in READY_FOR_REVIEW status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_READY_FOR_REVIEW,
            'revision_count' => 0,
        ]);

        // Assert client can request revision
        $this->assertTrue($this->policy->requestRevision($client, $order));

        // Assert producer cannot request revision
        $this->assertFalse($this->policy->requestRevision($producer, $order));

        // Update to a non-revisable status
        $order->status = Order::STATUS_IN_PROGRESS;
        $order->save();

        // Assert client cannot request revision in wrong status
        $this->assertFalse($this->policy->requestRevision($client, $order));

        // Set back to READY_FOR_REVIEW but with revision limit reached
        $order->status = Order::STATUS_READY_FOR_REVIEW;
        $order->revision_count = 1; // Used the one allowed revision
        $order->save();

        // Assert client cannot request revision when limit reached
        $this->assertFalse($this->policy->requestRevision($client, $order));
    }

    /** @test */
    public function client_can_accept_delivery_in_correct_status()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create order in READY_FOR_REVIEW status
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
            'status' => Order::STATUS_READY_FOR_REVIEW,
        ]);

        // Assert client can accept delivery
        $this->assertTrue($this->policy->acceptDelivery($client, $order));

        // Assert producer cannot accept delivery
        $this->assertFalse($this->policy->acceptDelivery($producer, $order));

        // Update to a different status
        $order->status = Order::STATUS_IN_PROGRESS;
        $order->save();

        // Assert client cannot accept delivery in wrong status
        $this->assertFalse($this->policy->acceptDelivery($client, $order));
    }

    /** @test */
    public function client_can_cancel_order_in_early_stages()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Test cancellation for each status
        $cancellableStatuses = [
            Order::STATUS_PENDING_REQUIREMENTS,
            Order::STATUS_IN_PROGRESS,
        ];

        $nonCancellableStatuses = [
            Order::STATUS_READY_FOR_REVIEW,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        // Test cancellable statuses
        foreach ($cancellableStatuses as $status) {
            $order = Order::factory()->create([
                'service_package_id' => $servicePackage->id,
                'client_user_id' => $client->id,
                'producer_user_id' => $producer->id,
                'status' => $status,
            ]);

            $this->assertTrue($this->policy->cancelOrder($client, $order), "Client should be able to cancel order in {$status} status");
        }

        // Test non-cancellable statuses
        foreach ($nonCancellableStatuses as $status) {
            $order = Order::factory()->create([
                'service_package_id' => $servicePackage->id,
                'client_user_id' => $client->id,
                'producer_user_id' => $producer->id,
                'status' => $status,
            ]);

            $this->assertFalse($this->policy->cancelOrder($client, $order), "Client should not be able to cancel order in {$status} status");
        }
    }

    /** @test */
    public function admin_can_access_any_order()
    {
        // Create users
        $client = User::factory()->create(['role' => 'owner']);
        $producer = User::factory()->create(['role' => 'producer']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Create service package
        $servicePackage = ServicePackage::factory()->create([
            'user_id' => $producer->id,
        ]);

        // Create order
        $order = Order::factory()->create([
            'service_package_id' => $servicePackage->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id,
        ]);

        // Assert admin can view the order
        $this->assertTrue($this->policy->view($admin, $order));

        // Check various admin permissions
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->submitRequirements($admin, $order));
        $this->assertTrue($this->policy->deliverOrder($admin, $order));
        $this->assertTrue($this->policy->requestRevision($admin, $order));
        $this->assertTrue($this->policy->acceptDelivery($admin, $order));
        $this->assertTrue($this->policy->cancelOrder($admin, $order));
    }
}
