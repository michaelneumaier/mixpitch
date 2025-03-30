<?php

namespace App\Http\Requests\Pitch;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Pitch;

class ProcessPitchPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $pitch = $this->route('pitch'); // Assuming route model binding for the pitch

        if (!$pitch instanceof Pitch) {
            // If route model binding fails or isn't used, try finding it another way
            // Or simply return false if the pitch object isn't available
            return false;
        }

        // Authorization rules:
        // 1. User must be the project owner.
        // 2. Pitch must be completed.
        // 3. Pitch payment status must be pending.
        return $this->user()->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_COMPLETED &&
               $pitch->payment_status === Pitch::PAYMENT_STATUS_PENDING;

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
            'payment_method_id' => 'required|string', // The ID from Stripe Elements/JS
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
            'payment_method_id.required' => 'A payment method is required to proceed.',
        ];
    }
} 