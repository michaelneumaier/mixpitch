<?php

namespace Database\Factories;

use App\Models\PayoutSchedule;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutScheduleFactory extends Factory
{
    protected $model = PayoutSchedule::class;

    public function definition()
    {
        $grossAmount = $this->faker->randomFloat(2, 50, 1000);
        $commissionRate = $this->faker->randomFloat(2, 5, 15); // 5-15% commission
        $commissionAmount = $grossAmount * ($commissionRate / 100);
        $netAmount = $grossAmount - $commissionAmount;

        return [
            'producer_user_id' => User::factory(),
            'project_id' => Project::factory(),
            'pitch_id' => Pitch::factory(),
            'gross_amount' => $grossAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'status' => $this->faker->randomElement([
                PayoutSchedule::STATUS_SCHEDULED,
                PayoutSchedule::STATUS_PROCESSING,
                PayoutSchedule::STATUS_COMPLETED,
                PayoutSchedule::STATUS_FAILED,
            ]),
            'hold_release_date' => $this->faker->dateTimeBetween('-30 days', '+7 days'),
            'processed_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'stripe_transfer_id' => null,
            'metadata' => [],
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            $completedAt = $this->faker->dateTimeBetween('-30 days', 'now');

            return [
                'status' => PayoutSchedule::STATUS_COMPLETED,
                'processed_at' => $completedAt,
                'completed_at' => $completedAt,
                'stripe_transfer_id' => 'tr_'.$this->faker->lexify('????????????????????????????????'),
            ];
        });
    }

    public function scheduled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PayoutSchedule::STATUS_SCHEDULED,
                'hold_release_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            ];
        });
    }

    public function processing()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PayoutSchedule::STATUS_PROCESSING,
                'processed_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
                'stripe_transfer_id' => 'tr_'.$this->faker->lexify('????????????????????????????????'),
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PayoutSchedule::STATUS_FAILED,
                'processed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'failed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'failure_reason' => $this->faker->randomElement([
                    'insufficient_balance',
                    'invalid_account',
                    'bank_declined',
                    'stripe_error',
                ]),
            ];
        });
    }

    public function withAmount($amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            $commissionRate = $attributes['commission_rate'] ?? 10;
            $commissionAmount = $amount * ($commissionRate / 100);
            $netAmount = $amount - $commissionAmount;

            return [
                'gross_amount' => $amount,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
            ];
        });
    }

    public function withCommissionRate($rate)
    {
        return $this->state(function (array $attributes) use ($rate) {
            $grossAmount = $attributes['gross_amount'] ?? 100;
            $commissionAmount = $grossAmount * ($rate / 100);
            $netAmount = $grossAmount - $commissionAmount;

            return [
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
            ];
        });
    }
}
