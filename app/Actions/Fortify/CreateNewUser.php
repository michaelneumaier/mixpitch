<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Validation\ValidationException;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Validate the reCAPTCHA token if it exists
        if (isset($input['g-recaptcha-response'])) {
            $recaptchaResult = app('recaptcha')->verify($input['g-recaptcha-response'], request()->ip());
            
            if (!$recaptchaResult->isSuccess()) {
                throw ValidationException::withMessages([
                    'recaptcha' => ['The reCAPTCHA verification failed. Please try again.'],
                ]);
            }
            
            // For v3, we should also check the score (0.0 is bot, 1.0 is human)
            if (config('recaptcha.version') === 'v3' && $recaptchaResult->getScore() < 0.5) {
                throw ValidationException::withMessages([
                    'recaptcha' => ['Suspicious activity detected. Please try again later.'],
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'recaptcha' => ['The reCAPTCHA verification is required.'],
            ]);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
