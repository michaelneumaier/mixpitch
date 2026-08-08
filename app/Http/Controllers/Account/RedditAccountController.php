<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class RedditAccountController extends Controller
{
    private const CALLBACK_ROUTE = 'account.reddit.callback';

    /**
     * Kick off the Reddit OAuth flow for account linking.
     * Uses a separate callback URL from the primary auth flow so the
     * SocialiteController isn't invoked (that path is for signup/login).
     */
    public function connect(): RedirectResponse
    {
        if (! config('services.reddit.client_id') || ! config('services.reddit.client_secret')) {
            Log::warning('Reddit account link attempted without OAuth credentials configured', [
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('profile.edit')
                ->with('error', 'Reddit connection is not configured yet.');
        }

        return Socialite::driver('reddit')
            ->redirectUrl(route(self::CALLBACK_ROUTE))
            ->redirect();
    }

    /**
     * Handle the OAuth callback and store Reddit identity on the current user.
     */
    public function callback(): RedirectResponse
    {
        if (request('error') === 'access_denied') {
            return redirect()->route('profile.edit')
                ->with('error', 'You cancelled the Reddit connection.');
        }

        try {
            $providerUser = Socialite::driver('reddit')
                ->redirectUrl(route(self::CALLBACK_ROUTE))
                ->user();
        } catch (\Throwable $e) {
            Log::error('Reddit account link callback failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('profile.edit')
                ->with('error', 'Could not connect your Reddit account. Please try again.');
        }

        $user = Auth::user();
        $redditUserId = $providerUser->getId();

        // Guard: is this Reddit account already linked to a different MixPitch user?
        $conflict = User::where('reddit_user_id', $redditUserId)
            ->where('id', '!=', $user->id)
            ->first();

        if ($conflict) {
            return redirect()->route('profile.edit')
                ->with('error', 'That Reddit account is already linked to another MixPitch user.');
        }

        $raw = $providerUser->getRaw() ?: [];
        $createdUtc = $raw['created_utc'] ?? null;

        $user->update([
            'reddit_username' => $providerUser->getNickname(),
            'reddit_user_id' => $redditUserId,
            'reddit_account_created_at' => $createdUtc ? \Carbon\Carbon::createFromTimestamp((int) $createdUtc) : null,
            'reddit_linked_at' => now(),
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Connected u/'.$providerUser->getNickname().' to your MixPitch profile.');
    }

    /**
     * Unlink the Reddit account from the current user.
     */
    public function disconnect(): RedirectResponse
    {
        Auth::user()->update([
            'reddit_username' => null,
            'reddit_user_id' => null,
            'reddit_account_created_at' => null,
            'reddit_linked_at' => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Disconnected your Reddit account.');
    }
}
