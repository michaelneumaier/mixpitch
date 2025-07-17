<?php

namespace Database\Factories;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        $amount = $this->faker->randomFloat(2, 50, 1000);
        $commissionRate = $this->faker->randomFloat(2, 5, 15); // 5-15% commission
        $commissionAmount = $amount * ($commissionRate / 100);
        $netAmount = $amount - $commissionAmount;

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'pitch_id' => Pitch::factory(),
            'type' => $this->faker->randomElement(['payment', 'refund', 'adjustment', 'bonus']),
            'status' => 'completed',
            'amount' => $amount,
            'net_amount' => $netAmount,
            'commission_amount' => $commissionAmount,
            'commission_rate' => $commissionRate,
            'payment_method' => 'stripe',
            'external_transaction_id' => 'pi_'.$this->faker->lexify('????????????????????????????????'),
            'user_subscription_plan' => 'pro',
            'user_subscription_tier' => 'artist',
            'description' => 'Test transaction',
            'metadata' => [],
            'processed_at' => now(),
        ];
    }

    /**
     * Indicate that the transaction is a payment.
     */
    public function payment()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'payment',
            ];
        });
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'processed_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'processed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the transaction is failed.
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'processed_at' => null,
            ];
        });
    }

    /**
     * Set a specific commission rate.
     */
    public function withCommissionRate($rate)
    {
        return $this->state(function (array $attributes) use ($rate) {
            $amount = $attributes['amount'];
            $commissionAmount = $amount * ($rate / 100);
            $netAmount = $amount - $commissionAmount;

            return [
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
            ];
        });
    }
}
