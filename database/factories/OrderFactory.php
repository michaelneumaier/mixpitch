<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ensure we have a ServicePackage and Users to associate with
        $package = ServicePackage::factory()->create();
        $client = User::factory()->create();
        // Use the package owner as the producer, or create another user
        $producer = $package->user ?? User::factory()->create(); 

        return [
            'service_package_id' => $package->id,
            'client_user_id' => $client->id,
            'producer_user_id' => $producer->id, 
            'status' => Order::STATUS_PENDING_REQUIREMENTS, // Default to a common starting status
            'price' => $package->price, // Get price from the package
            'currency' => $package->currency ?? 'USD', // Get currency or default
            'payment_status' => Order::PAYMENT_STATUS_PAID, // Assume paid for most factory scenarios
            'requirements_submitted' => null,
            'revision_count' => 0,
            'completed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            // invoice_id handled separately if needed
        ];
    }

    // Add state methods for different statuses if needed
    public function pendingRequirements(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PENDING_REQUIREMENTS,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);
    }
    
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_IN_PROGRESS,
            'requirements_submitted' => $this->faker->paragraph(),
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);
    }

    public function readyForReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_READY_FOR_REVIEW,
             'requirements_submitted' => $this->faker->paragraph(),
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'delivered_at' => now(),
        ]);
    }

     public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_COMPLETED,
            'requirements_submitted' => $this->faker->paragraph(),
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'delivered_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }

    // Add more states as needed (e.g., cancelled, revisions_requested)
}
