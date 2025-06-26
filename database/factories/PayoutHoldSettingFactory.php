<?php

namespace Database\Factories;

use App\Models\PayoutHoldSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayoutHoldSetting>
 */
class PayoutHoldSettingFactory extends Factory
{
    protected $model = PayoutHoldSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enabled' => true,
            'default_days' => 1,
            'workflow_days' => [
                'standard' => 1,
                'contest' => 0,
                'client_management' => 0,
            ],
            'business_days_only' => true,
            'processing_time' => '09:00:00',
            'minimum_hold_hours' => 0,
            'allow_admin_bypass' => true,
            'require_bypass_reason' => true,
            'log_bypasses' => true,
        ];
    }

    /**
     * Create settings with hold periods disabled
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
            'minimum_hold_hours' => 0,
        ]);
    }

    /**
     * Create settings with longer hold periods for testing
     */
    public function withLongHoldPeriods(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_days' => 7,
            'workflow_days' => [
                'standard' => 3,
                'contest' => 5,
                'client_management' => 7,
            ],
        ]);
    }

    /**
     * Create settings with calendar days instead of business days
     */
    public function calendarDays(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_days_only' => false,
        ]);
    }

    /**
     * Create settings with admin bypass disabled
     */
    public function noAdminBypass(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_admin_bypass' => false,
            'require_bypass_reason' => false,
            'log_bypasses' => false,
        ]);
    }

    /**
     * Create settings with minimum hold hours when disabled
     */
    public function withMinimumHold(int $hours = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_hold_hours' => $hours,
        ]);
    }
}
