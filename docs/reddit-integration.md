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
REDDIT_USER_AGENT="MixPitch/1.0"
```

Bot config lives at `config/services.php` under the `reddit_bot` key. `REDDIT_USER_AGENT` must match `.env.example`'s format (`"MixPitch/1.0"`) — Reddit's API guidelines want a descriptive UA, but the bot's HTTP client (`RedditService`) sends this string verbatim, so keep it in sync with whatever `.env`/`.env.example` actually declares rather than a longer variant.

Note: the OAuth flow (Socialite `reddit` driver) does **not** use `REDDIT_USER_AGENT` — it builds its own user agent from the `platform`, `app_id`, and `version_string` keys under `config('services.reddit')`.

The bot account must be a moderator of `r/MixPitch` (or at least an approved submitter) to post there.

---

## 2. Create the OAuth Reddit app (for "Sign in with Reddit")

Reddit's app registration form only accepts **one** redirect URI per app, so MixPitch uses a single web-type Reddit app for both entry points (primary sign-in and secondary account linking) and switches the redirect URI at request time.

1. Still at <https://www.reddit.com/prefs/apps> → **create app**.
2. Choose **web app** type.
3. Register the primary callback as the redirect uri: `https://mixpitch.com/auth/reddit/callback` (for local dev, `http://mixpitch.test/auth/reddit/callback` or your local URL scheme).
4. Copy the client ID and secret.

Set these env vars:

```
REDDIT_OAUTH_CLIENT_ID=<web app client id>
REDDIT_OAUTH_CLIENT_SECRET=<web app client secret>
REDDIT_OAUTH_REDIRECT_URI="${APP_URL}/auth/reddit/callback"
```

OAuth config lives at `config/services.php` under the `reddit` key (defaults to `/auth/reddit/callback`, resolved against `APP_URL` by Socialite, if `REDDIT_OAUTH_REDIRECT_URI` is unset). It's read automatically by `socialiteproviders/reddit`.

The secondary-link flow (`RedditAccountController`) reuses the same client ID/secret but calls `->redirectUrl(route('account.reddit.callback'))` to point Socialite at `/account/reddit/callback` instead — it overrides the redirect at request time rather than requiring a second registered app. If Reddit ever starts rejecting that override as a redirect_uri mismatch, register a second Reddit app dedicated to `/account/reddit/callback` and give `RedditAccountController` its own client ID/secret pair.

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

## 4. Deploying / troubleshooting

- **After changing any `REDDIT_*` env var**, run `php artisan config:clear` (or, if the deploy pipeline uses `config:cache`, rebuild it — a stale cached config will keep serving the old client ID/secret/redirect URI even after the `.env` file is updated).
- **Reddit posting requires a queue worker** when `QUEUE_CONNECTION` is not `sync` — `PostProjectToReddit`, `DeleteRedditPost`, and the `UpdateRedditPostFor*` jobs are all queued, so nothing will actually reach Reddit until a worker (`php artisan queue:work`) is running and processing them.
- **After a deploy that adds routes** (e.g. the `account.reddit.connect` / `account.reddit.callback` routes), `php artisan route:cache` must be rebuilt on the server. A stale route cache is the usual cause of "Connect Reddit" 404ing or redirecting nowhere in production even though the code is deployed and correct locally.

---

## 5. Subreddit-side setup (r/MixPitch)

Code-side integration is only half of the launch — the subreddit itself needs configuration. This is manual mod work, not automated:

- **Rules** (short, 5-7 max): "Requests must link to a MixPitch project," "One request per week," "No paid gigs (use MixPitch's paid flow)," "Mark solved when done," "No copyrighted content you don't own."
- **Post flair**: `[Request]`, `[Showcase]`, `[Discussion]`, `[Help]`, `[Meta]`.
- **AutoMod rules**: auto-remove `[Request]` posts without a `mixpitch.com` link + reply with instructions.
- **Wiki**: `/how-this-works`, `/how-to-post-a-request`, `/for-producers`, `/rules`.
- **Sidebar / community info**: explain how the subreddit and MixPitch relate.
- **Bot profile**: give `u/MixPitch` a polished bio + avatar so it doesn't look like a shady bot.
- **Mod team**: at least 2 humans beyond the automation.

---

## 6. Where the code lives

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
