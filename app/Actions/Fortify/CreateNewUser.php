<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

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
        // Only validate reCAPTCHA if it's enabled and keys are set
        if (config('recaptcha.api_site_key') && config('recaptcha.api_secret_key') && env('RECAPTCHA_ENABLED', true)) {
            // Validate the reCAPTCHA token if it exists
            if (isset($input['g-recaptcha-response'])) {
                // Get reCAPTCHA response
                $response = app('recaptcha')->validate($input['g-recaptcha-response'], request()->ip());

                if (! $response) {
                    throw ValidationException::withMessages([
                        'recaptcha' => ['The reCAPTCHA verification failed. Please try again.'],
                    ]);
                }
            } else {
                throw ValidationException::withMessages([
                    'recaptcha' => ['The reCAPTCHA verification is required.'],
                ]);
            }
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
