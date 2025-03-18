<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle provider callback.
     *
     * @param string $provider
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
                // Update the token
                $existingUser->update([
                    'provider_token' => $providerUser->token,
                    'provider_refresh_token' => $providerUser->refreshToken,
                ]);
                
                Auth::login($existingUser);
                return redirect()->intended('/dashboard');
            }

            // Check if user exists with same email
            $user = User::where('email', $providerUser->getEmail())->first();
            
            if ($user) {
                // Update user with provider data
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                    'provider_token' => $providerUser->token,
                    'provider_refresh_token' => $providerUser->refreshToken,
                ]);
                
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
            ]);
            
            Auth::login($newUser);
            return redirect()->route('profile.edit');
            
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Something went wrong with social login: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique username based on the provider username or name
     *
     * @param string $name
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
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
}
