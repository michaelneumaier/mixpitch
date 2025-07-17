<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     *
     * @param  string  $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle provider callback.
     *
     * @param  string  $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback($provider)
    {
        try {
            $providerUser = Socialite::driver($provider)->user();

            // Check if user already exists with this provider
            $existingUser = User::where('provider', $provider)
                ->where('provider_id', $providerUser->getId())
                ->first();

            if ($existingUser) {
                // Update the token and ensure email is verified
                $existingUser->update([
                    'provider_token' => $providerUser->token,
                    'provider_refresh_token' => $providerUser->refreshToken,
                    'email_verified_at' => $existingUser->hasVerifiedEmail() ? $existingUser->email_verified_at : now(),
                ]);

                // Refresh the model to ensure changes are loaded
                $existingUser->refresh();

                // Ensure OAuth users are always verified
                if (! $existingUser->hasVerifiedEmail()) {
                    $existingUser->markEmailAsVerified();
                }

                Auth::login($existingUser);

                return redirect()->intended('/dashboard');
            }

            // Check if user exists with same email
            $user = User::where('email', $providerUser->getEmail())->first();

            if ($user) {
                // Update user with provider data and verify email if not already verified
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                    'provider_token' => $providerUser->token,
                    'provider_refresh_token' => $providerUser->refreshToken,
                    'email_verified_at' => $user->hasVerifiedEmail() ? $user->email_verified_at : now(),
                ]);

                // Refresh the model to ensure changes are loaded
                $user->refresh();

                // Double-check that email is verified after linking OAuth
                if (! $user->hasVerifiedEmail()) {
                    \Log::warning('Linked OAuth user not verified, manually marking as verified', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'provider' => $provider,
                    ]);
                    $user->markEmailAsVerified();
                }

                Auth::login($user);

                return redirect()->intended('/dashboard');
            }

            // Create new user
            $username = $this->generateUniqueUsername($providerUser->getNickname() ?? $providerUser->getName());

            $newUser = User::create([
                'name' => $providerUser->getName(),
                'email' => $providerUser->getEmail(),
                'username' => $username,
                'password' => Hash::make(Str::random(16)),
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
                'provider_token' => $providerUser->token,
                'provider_refresh_token' => $providerUser->refreshToken,
                'profile_completed' => false,
                'email_verified_at' => now(), // Auto-verify OAuth users
            ]);

            // Refresh the model to ensure the email_verified_at is properly set
            $newUser->refresh();

            // Ensure the user is fully verified before logging in
            if (! $newUser->hasVerifiedEmail()) {
                \Log::warning('OAuth user created but not verified, manually marking as verified', [
                    'user_id' => $newUser->id,
                    'email' => $newUser->email,
                    'provider' => $provider,
                ]);
                $newUser->markEmailAsVerified();
            }

            Auth::login($newUser);

            // Since this is a new OAuth user, they should go directly to dashboard
            // instead of profile edit to avoid potential verification middleware issues
            return redirect('/dashboard');

        } catch (Exception $e) {
            \Log::error('OAuth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->with('error', 'Something went wrong with social login: '.$e->getMessage());
        }
    }

    /**
     * Generate a unique username based on the provider username or name
     *
     * @param  string  $name
     * @return string
     */
    private function generateUniqueUsername($name)
    {
        // Convert to lowercase and replace spaces with underscores
        $baseUsername = strtolower(str_replace(' ', '_', $name));
        $baseUsername = preg_replace('/[^a-z0-9_]/', '', $baseUsername);

        // If username is too short, add some random characters
        if (strlen($baseUsername) < 3) {
            $baseUsername .= Str::random(5);
        }

        $username = $baseUsername;
        $counter = 1;

        // Keep checking until we find a unique username
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$counter;
            $counter++;
        }

        return $username;
    }
}
