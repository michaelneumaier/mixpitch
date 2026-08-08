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
     * Providers this application actually supports for social login.
     * Any other value in the {provider} route segment is rejected before
     * ever reaching Socialite::driver(), which throws on unknown drivers.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_PROVIDERS = ['google', 'reddit'];

    /**
     * Redirect the user to the provider authentication page.
     *
     * @param  string  $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($provider)
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return redirect()->route('login')
                ->with('error', 'That sign-in method is not supported.');
        }

        if (! config("services.{$provider}.client_id")) {
            \Log::warning('Social login attempted without OAuth credentials configured', [
                'provider' => $provider,
            ]);

            return redirect()->route('login')
                ->with('error', ucfirst($provider).' sign-in is not configured yet.');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            \Log::error('Failed to initiate social login redirect', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Something went wrong starting sign-in. Please try again.');
        }
    }

    /**
     * Handle provider callback.
     *
     * @param  string  $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback($provider)
    {
        if (request('error') === 'access_denied') {
            return redirect()->route('login')->with('error', 'You cancelled the sign-in.');
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
            $redditAttributes = $this->redditAttributesFor($provider, $providerUser);

            // Check if user already exists with this provider
            $existingUser = User::where('provider', $provider)
                ->where('provider_id', $providerUser->getId())
                ->first();

            // For Reddit primary auth, also match users who have linked Reddit as a secondary
            // identity previously — log them into their existing account rather than failing
            // on the unique constraint.
            if (! $existingUser && $provider === 'reddit') {
                $existingUser = User::where('reddit_user_id', $providerUser->getId())->first();
            }

            if ($existingUser) {
                // Update the token and ensure email is verified
                $existingUser->update(array_merge([
                    'provider_token' => $providerUser->token,
                    'provider_refresh_token' => $providerUser->refreshToken,
                    'email_verified_at' => $existingUser->hasVerifiedEmail() ? $existingUser->email_verified_at : now(),
                ], $redditAttributes));

                // Refresh the model to ensure changes are loaded
                $existingUser->refresh();

                // Ensure OAuth users are always verified
                if (! $existingUser->hasVerifiedEmail()) {
                    $existingUser->markEmailAsVerified();
                }

                Auth::login($existingUser);

                return redirect()->intended('/dashboard');
            }

            // Check if user exists with same email — skip when provider returns no email
            // (e.g. Reddit's `identity` scope does not include the user's email).
            $providerEmail = $providerUser->getEmail();
            $user = $providerEmail ? User::where('email', $providerEmail)->first() : null;

            if ($user) {
                // Update user with provider data and verify email if not already verified
                $user->update(array_merge([
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                    'provider_token' => $providerUser->token,
                    'provider_refresh_token' => $providerUser->refreshToken,
                    'email_verified_at' => $user->hasVerifiedEmail() ? $user->email_verified_at : now(),
                ], $redditAttributes));

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

            // Providers like Reddit don't return an email address; synthesize a placeholder
            // so the NOT NULL / UNIQUE constraint on `email` is satisfied. The user can add
            // a real email later from profile settings.
            $email = $providerEmail ?: sprintf('%s_%s@no-email.mixpitch.local', $provider, $providerUser->getId());

            $newUser = User::create(array_merge([
                'name' => $providerUser->getName() ?: $providerUser->getNickname(),
                'email' => $email,
                'username' => $username,
                'password' => Hash::make(Str::random(16)),
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
                'provider_token' => $providerUser->token,
                'provider_refresh_token' => $providerUser->refreshToken,
                'profile_completed' => false,
                'email_verified_at' => now(), // Auto-verify OAuth users
            ], $redditAttributes));

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

            return redirect()->route('login')->with('error', 'Sign-in failed, please try again.');
        }
    }

    /**
     * Extract Reddit-specific profile columns from a Socialite user payload.
     * Returns [] for non-Reddit providers so array_merge is a no-op.
     */
    private function redditAttributesFor(string $provider, $providerUser): array
    {
        if ($provider !== 'reddit') {
            return [];
        }

        $raw = $providerUser->getRaw() ?: [];
        $createdUtc = $raw['created_utc'] ?? null;

        return [
            'reddit_username' => $providerUser->getNickname(),
            'reddit_user_id' => $providerUser->getId(),
            'reddit_account_created_at' => $createdUtc ? \Carbon\Carbon::createFromTimestamp((int) $createdUtc) : null,
            'reddit_linked_at' => now(),
        ];
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
