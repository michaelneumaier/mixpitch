# Reddit Integration — Setup Guide

MixPitch integrates with Reddit in two distinct ways, backed by two separate Reddit apps:

1. **Bot posting** — a `u/MixPitch` script-type app used by `RedditService` to submit, edit, comment on, and delete posts in r/MixPitch.
2. **User OAuth** — a web-type app used by Laravel Socialite for "Sign in with Reddit" (primary auth) and "Connect Reddit account" (secondary identity + trust badge).

Both apps live at <https://www.reddit.com/prefs/apps>.

---

## 1. Create the bot Reddit app

1. Log into Reddit as the bot account (`u/MixPitch`).
2. Go to <https://www.reddit.com/prefs/apps> → **create app**.
3. Choose **script** type.
4. Set `redirect uri` to `http://localhost` (unused for password-grant scripts but required by the form).
5. Copy the client ID (under the app name) and secret.

Set these env vars:

```
REDDIT_CLIENT_ID=<script app client id>
REDDIT_CLIENT_SECRET=<script app client secret>
REDDIT_BOT_USERNAME=MixPitch
REDDIT_BOT_PASSWORD=<the bot account password>
REDDIT_USER_AGENT="MixPitch/1.0 (by /u/MixPitch)"
```

Bot config lives at `config/services.php` under the `reddit_bot` key.

The bot account must be a moderator of `r/MixPitch` (or at least an approved submitter) to post there.

---

## 2. Create the OAuth Reddit app (for "Sign in with Reddit")

1. Still at <https://www.reddit.com/prefs/apps> → **create app**.
2. Choose **web app** type.
3. Register **both** redirect URIs (one per line):
   - `https://mixpitch.com/auth/reddit/callback` — primary auth flow
   - `https://mixpitch.com/account/reddit/callback` — secondary account linking
4. For local dev, also register `http://mixpitch.test/auth/reddit/callback` and `http://mixpitch.test/account/reddit/callback` (or your local URL scheme).
5. Copy the client ID and secret.

Set these env vars:

```
REDDIT_OAUTH_CLIENT_ID=<web app client id>
REDDIT_OAUTH_CLIENT_SECRET=<web app client secret>
REDDIT_OAUTH_REDIRECT_URI="${APP_URL}/auth/reddit/callback"
```

OAuth config lives at `config/services.php` under the `reddit` key. It's read automatically by `socialiteproviders/reddit`.

The secondary-link callback (`/account/reddit/callback`) inherits the same client ID/secret; it overrides the redirect at runtime.

---

## 3. Verify the setup

**Bot posting:**
```bash
php artisan tinker
> $project = App\Models\Project::first();
> App\Jobs\PostProjectToReddit::dispatchSync($project);
```
Check `$project->fresh()->reddit_post_id` — should be a Reddit post ID.

**OAuth login:**
Visit `/login` and click "Sign in with Reddit". You should be redirected to Reddit, authorize, and land on `/dashboard`. Check `users` table for a row with `provider='reddit'` and populated `reddit_username`, `reddit_user_id`, `reddit_account_created_at`.

**Account linking:**
Sign in with Google (or any non-Reddit provider), then visit `/profile/edit` and click "Connect Reddit" under Connected Accounts. Same flow, but populates `reddit_*` columns on your existing user.

---

## 4. Subreddit-side setup (r/MixPitch)

Code-side integration is only half of the launch — the subreddit itself needs configuration. This is manual mod work, not automated:

- **Rules** (short, 5-7 max): "Requests must link to a MixPitch project," "One request per week," "No paid gigs (use MixPitch's paid flow)," "Mark solved when done," "No copyrighted content you don't own."
- **Post flair**: `[Request]`, `[Showcase]`, `[Discussion]`, `[Help]`, `[Meta]`.
- **AutoMod rules**: auto-remove `[Request]` posts without a `mixpitch.com` link + reply with instructions.
- **Wiki**: `/how-this-works`, `/how-to-post-a-request`, `/for-producers`, `/rules`.
- **Sidebar / community info**: explain how the subreddit and MixPitch relate.
- **Bot profile**: give `u/MixPitch` a polished bio + avatar so it doesn't look like a shady bot.
- **Mod team**: at least 2 humans beyond the automation.

---

## 5. Where the code lives

- Bot service: `app/Services/RedditService.php`
- Initial post job: `app/Jobs/PostProjectToReddit.php`
- Update-post jobs: `app/Jobs/UpdateRedditPostForPitchAccepted.php`, `app/Jobs/UpdateRedditPostForProjectCompleted.php`
- Delete-post job: `app/Jobs/DeleteRedditPost.php`
- Livewire trait: `app/Livewire/Concerns/HasRedditPosting.php` (used by `ManageStandardProject` and `ManageContestProject`)
- Primary OAuth handler: `app/Http/Controllers/Auth/SocialiteController.php`
- Secondary account linking: `app/Http/Controllers/Account/RedditAccountController.php`
- Trust badge component: `resources/views/components/reddit-badge.blade.php`
- Post lifecycle events: `app/Events/ProjectPitchAccepted.php`, `app/Events/ProjectCompleted.php`
- Listeners: `app/Listeners/SyncRedditPostOnPitchAccepted.php`, `app/Listeners/SyncRedditPostOnProjectCompleted.php`

See also: [reddit-post-lifecycle.md](reddit-post-lifecycle.md).
