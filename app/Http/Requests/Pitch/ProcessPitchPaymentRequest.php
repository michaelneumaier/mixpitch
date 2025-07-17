<?php

namespace App\Http\Requests\Pitch;

use App\Models\Pitch;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPitchPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $pitch = $this->route('pitch'); // Assuming route model binding for the pitch

        if (! $pitch instanceof Pitch) {
            // If route model binding fails or isn't used, try finding it another way
            // Or simply return false if the pitch object isn't available
            return false;
        }

        // Authorization rules:
        // 1. User must be the project owner.
        // 2. Pitch must be completed.
        // 3. Pitch payment status must be pending, failed, or null/empty (for newly completed pitches).
        // 4. CRITICAL: Producer must have valid Stripe Connect account
        $allowedPaymentStatuses = [
            Pitch::PAYMENT_STATUS_PENDING,
            Pitch::PAYMENT_STATUS_FAILED,
            null,
            '',
        ];

        $producer = $pitch->user;
        $hasValidStripeConnect = $producer->stripe_account_id && $producer->hasValidStripeConnectAccount();

        return $this->user()->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_COMPLETED &&
               in_array($pitch->payment_status, $allowedPaymentStatuses) &&
               $hasValidStripeConnect;

        // Alternatively, use a policy check if defined:
        // return $this->user()->can('processPayment', $pitch);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => 'required_without:payment_method|string',
            'payment_method' => 'required_without:payment_method_id|string',
            // Pitch ID is typically handled by route model binding, no need to validate here unless passed in body
            // 'pitch_id' => 'required|exists:pitches,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method_id.required_without' => 'A payment method is required to proceed.',
            'payment_method.required_without' => 'A payment method is required to proceed.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Handle either payment_method or payment_method_id by consolidating them
        if ($this->has('payment_method') && ! $this->has('payment_method_id')) {
            $this->merge([
                'payment_method_id' => $this->input('payment_method'),
            ]);
        }
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedAuthorization()
    {
        $pitch = $this->route('pitch');

        if ($pitch instanceof Pitch) {
            $producer = $pitch->user;

            // Check specifically for Stripe Connect issues to provide better error message
            if (! $producer->stripe_account_id || ! $producer->hasValidStripeConnectAccount()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'stripe_connect' => "Payment cannot be processed: {$producer->name} needs to complete their Stripe Connect account setup to receive payments. Please ask them to set up their payout account first.",
                ]);
            }
        }

        // Fall back to default authorization failure
        parent::failedAuthorization();
    }
}
