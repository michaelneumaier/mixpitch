<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Try to associate with an order if possible, otherwise just a user
        $user = User::factory()->create();
        $order = Order::factory()->create(['client_user_id' => $user->id]); // Create an order for this user

        return [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'stripe_invoice_id' => 'in_'.$this->faker->unique()->regexify('[a-zA-Z0-9]{24}'), // Example Stripe ID
            'status' => Invoice::STATUS_PENDING, // Default status
            'amount' => $order->price ?? $this->faker->numberBetween(1000, 50000) / 100, // Use order price or random
            'currency' => $order->currency ?? 'USD',
            'due_date' => null,
            'paid_at' => null,
            'pdf_url' => null,
        ];
    }

    // State for a paid invoice
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    // State for a failed invoice
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_FAILED,
        ]);
    }

    // State for void invoice
    public function void(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_VOID,
        ]);
    }
}
