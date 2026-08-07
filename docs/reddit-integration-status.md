# Reddit Integration — Status and Next Steps

**Last updated:** 2026-08-06
**Scope:** everything discussed and shipped in the "Track B — Reddit Integration Polish" engagement, plus what remains before public launch.

---

## Context

MixPitch has a Reddit integration surface centered on `r/MixPitch`. The strategic framing (decided 2026-08-06) is:

- **MixPitch** stays a broad open platform — do-what-you-want, not artificially narrowed for launch.
- **r/MixPitch** is the curated, more-ordered community layer with rules, AutoMod, and mods.
- **Flow direction is one-way**: project created on MixPitch → optionally cross-posted to r/MixPitch. Never the reverse.
- **Launch tone**: quiet organic on r/MixPitch, with periodic "loud" announcements in adjacent subs (e.g. r/mixingmastering, r/WeAreTheMusicMakers) once the sub has baseline activity.
- **Vision motivator**: the r/PhotoshopRequests model — low-friction, free-first, community-driven, tips optional — applied to music with MixPitch providing the file/audio/versioning plumbing Reddit lacks.

Track B took the existing Reddit integration (a one-shot bot broadcast) and made it a first-class two-way integration: OAuth login, trust badge, live post-back on lifecycle events, and safe teardown.

---

## What shipped in Track B

Everything below is code-complete, tested, and merged into the working branch.

### Phase 1 — Baseline hygiene
- Reddit bot env vars added to `.env.example`.
- Contest workflow gap fixed: `postToReddit()` and `checkRedditStatus()` extracted from `ManageStandardProject.php` into `app/Livewire/Concerns/HasRedditPosting.php`. Now used by both `ManageStandardProject` and `ManageContestProject`. Contest projects can now post to Reddit — UI already supported it, backend method was missing.
- Test coverage added for existing behavior (was zero): `RedditServiceTest`, `PostProjectToRedditTest`, `PostToRedditButtonTest`.

### Phase 2 — Reddit OAuth login (Sign in with Reddit)
- Installed `socialiteproviders/reddit` package.
- Config split cleanly: `services.reddit_bot` (existing bot vars, unchanged env names) + `services.reddit` (new OAuth vars). No breaking rename.
- `EventServiceProvider` registers the Reddit Socialite extension.
- `SocialiteController::callback()` handles Reddit's no-email quirk: skips the email-based existing-user linking branch when the provider returns null, and synthesizes a placeholder email (`reddit_{provider_id}@no-email.mixpitch.local`) so the NOT NULL email constraint is satisfied.
- "Sign in with Reddit" buttons added to `login.blade.php` and `register.blade.php` with the Reddit snoo SVG.
- 4 tests in `RedditSocialiteTest` cover new signup, existing user login, nickname-as-name fallback, and non-linking to unrelated email accounts.

### Phase 3 — Account linking (trust badge as secondary identity)
- Migration adds four columns to `users`: `reddit_username` (indexed), `reddit_user_id` (unique), `reddit_account_created_at`, `reddit_linked_at`. Also adds `reddit_original_body` (text) to `projects` for the Phase 5 edit updates.
- `User` model: fillable + casts updated. Two helpers added: `hasLinkedReddit()`, `getRedditProfileUrl()`.
- `SocialiteController` auto-populates the `reddit_*` columns on Reddit-primary signup — pulls `created_utc` from the raw provider payload for account age.
- Safety net: if someone signs in with Reddit and a MixPitch user already has that Reddit account linked as *secondary*, we log them into that existing user instead of failing on the unique constraint.
- New `RedditAccountController` at `app/Http/Controllers/Account/RedditAccountController.php` with connect/callback/disconnect actions. Uses Socialite's `redirectUrl()` runtime override so both flows share the same Reddit web app (register both callback URLs on the Reddit app).
- Refuses to link a Reddit account already owned by another MixPitch user.
- Anonymous Blade component `<x-reddit-badge :user="$user" />` renders "u/username · since YYYY" with a link to the Reddit profile. Renders nothing when unlinked.
- Badge live on the public `/@{username}` profile page.
- "Connected Accounts" section added to profile edit view (Flux UI) with Connect/Disconnect buttons and success/error flash callouts.
- 9 tests: `ConnectRedditTest` (5) + `RedditBadgeTest` (4).

### Phase 4 — Extended RedditService
- Added `editPost(string $fullname, string $newBody)` — POST `/api/editusertext`.
- Added `postComment(string $parentFullname, string $body)` — POST `/api/comment`.
- Added `deletePost(string $fullname)` — POST `/api/del`.
- Added `buildPostBody(Project $project)` public wrapper around `formatText()` so jobs can snapshot the initial post body.
- All new methods use the existing bot token flow. Handle 403/404 by throwing so callers can log + swallow gracefully.
- 7 new tests added to `RedditServiceTest`.

### Phase 5 — Live-artifact post-back (the "posts stay fresh" work)
- New events: `App\Events\ProjectPitchAccepted` (payload: Project + Pitch + User), `App\Events\ProjectCompleted` (payload: Project).
- Dispatched from `PitchWorkflowService::approveInitialPitch()` and `ProjectManagementService::completeProject()`.
- Queued listeners `SyncRedditPostOnPitchAccepted` + `SyncRedditPostOnProjectCompleted` (registered in `EventServiceProvider`). Guard on `reddit_post_id` before enqueuing; skip silently if project was never posted.
- Two update jobs: `UpdateRedditPostForPitchAccepted` and `UpdateRedditPostForProjectCompleted`. Each:
  - Prepends a status header to `reddit_original_body` and edits the Reddit post via `editPost`
  - Posts a top-level bot comment via `postComment`
  - Producer attribution uses `u/reddit_username` if the producer has linked Reddit, falls back to `@mixpitch_username`
  - Edit failure logged + swallowed (comment is the more visible signal); comment failure retries with 15-minute backoff (max 3 tries)
- `PostProjectToReddit` now also captures `reddit_original_body` on first submit so updates have a clean base to edit against.
- 11 tests: two update-job test files + listener test file.

### Phase 6 — Remove-from-Reddit action
- `unpostFromReddit()` method added to `HasRedditPosting` trait.
- New `DeleteRedditPost` job: calls `RedditService::deletePost`, then clears `reddit_post_id`/`reddit_permalink`/`reddit_original_body`. Preserves `reddit_posted_at` as an audit signal.
- "Remove from Reddit" menu item added to `project-header.blade.php` (shown only when posted, includes confirm prompt).
- 3 job tests + 2 Livewire tests.

### Phase 7 — Documentation
- `docs/reddit-integration.md` — setup guide covering both Reddit apps (bot + OAuth), env vars, verification, subreddit-side to-dos, code map.
- `docs/reddit-post-lifecycle.md` — reference explaining what triggers posts, edits, comments, deletions; failure modes; workflow support matrix.

### Test totals
- **55 Reddit-specific tests, 155 assertions**, all green
- 63 workflow / regression tests verified still green (0 breakage on touched areas)
- `vendor/bin/pint` clean on all modified files

---

## Files delivered

**Created (18):**
- `app/Livewire/Concerns/HasRedditPosting.php`
- `app/Http/Controllers/Account/RedditAccountController.php`
- `app/Events/ProjectPitchAccepted.php`
- `app/Events/ProjectCompleted.php`
- `app/Listeners/SyncRedditPostOnPitchAccepted.php`
- `app/Listeners/SyncRedditPostOnProjectCompleted.php`
- `app/Jobs/UpdateRedditPostForPitchAccepted.php`
- `app/Jobs/UpdateRedditPostForProjectCompleted.php`
- `app/Jobs/DeleteRedditPost.php`
- `resources/views/components/reddit-badge.blade.php`
- `database/migrations/2026_08_06_203600_add_reddit_link_fields_to_users_table.php`
- `database/migrations/2026_08_06_203601_add_reddit_original_body_to_projects_table.php`
- `docs/reddit-integration.md`
- `docs/reddit-post-lifecycle.md`
- `tests/Unit/Services/RedditServiceTest.php`
- `tests/Feature/Jobs/PostProjectToRedditTest.php`
- `tests/Feature/Jobs/UpdateRedditPostForPitchAcceptedTest.php`
- `tests/Feature/Jobs/UpdateRedditPostForProjectCompletedTest.php`
- `tests/Feature/Jobs/DeleteRedditPostTest.php`
- `tests/Feature/Events/RedditPostBackListenersTest.php`
- `tests/Feature/Auth/RedditSocialiteTest.php`
- `tests/Feature/Account/ConnectRedditTest.php`
- `tests/Feature/Components/RedditBadgeTest.php`
- `tests/Feature/Livewire/PostToRedditButtonTest.php`

**Modified:**
- `.env.example`
- `composer.json` / `composer.lock`
- `config/services.php`
- `app/Providers/EventServiceProvider.php`
- `app/Http/Controllers/Auth/SocialiteController.php`
- `app/Models/User.php`
- `app/Models/Project.php` (fillable)
- `app/Services/RedditService.php`
- `app/Services/PitchWorkflowService.php` (event dispatch)
- `app/Services/Project/ProjectManagementService.php` (event dispatch)
- `app/Jobs/PostProjectToReddit.php` (capture original body)
- `app/Livewire/Project/ManageStandardProject.php` (use trait, remove duplicated code)
- `app/Livewire/Project/ManageContestProject.php` (use trait — this is the Contest fix)
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/livewire/project/project-header.blade.php` (Remove from Reddit menu item)
- `resources/views/livewire/user-profile-edit.blade.php` (Connected Accounts section)
- `resources/views/user-profile/show.blade.php` (badge on public profile)
- `routes/web.php` (RedditAccountController routes)

---

## What's needed from here — pre-launch checklist

Three parallel tracks. Track A is the biggest blocker; Track B (this engagement) is done; Track C is manual Reddit config.

### Track A — Platform release blockers (NOT started)
These block a public launch of MixPitch itself, independent of the Reddit integration. From the initial audit:

- [ ] **Rotate every secret in `.env`** — AWS, Stripe, Google, Cloudflare, reCAPTCHA, Reddit bot creds are all committed to git history.
- [ ] **Scrub `.env` from git history** with BFG or `git filter-repo`.
- [ ] **Add `.env` to `.gitignore`**; verify no future commits can re-add it.
- [ ] **Set `APP_DEBUG=false`** in production; remove `LOG_LEVEL=debug` from committed env.
- [ ] **Remove `database/database.sqlite`** (1.5MB) from repo; add to `.gitignore`.
- [ ] **Delete the 9 loose `test_*.php` / `debug_*.php` scripts** in repo root (or move to `tests/`).
- [ ] **Create `privacy.blade.php` and `/privacy` route** — GDPR/CCPA blocker; `terms.blade.php` and `about.blade.php` already exist.
- [ ] **Finish or feature-flag the stubbed logic**:
  - `ServicePackageController` — authorization TODOs
  - `OrderWorkflowService::triggerPayout()` — TODO stub
  - `RefundRequestService` — Stripe refund not implemented (marked "Phase 3" in code)
- [ ] **Integrate Sentry or equivalent** error tracking.
- [ ] **Set up basic CI** — `.github/workflows/` with Pest + Pint on PR.
- [ ] **Audit debug noise**: 1629 `Log::` calls (target <300 in prod code paths), 58 `console.log` calls in resources.

### Track B — Reddit integration going live (config only)
Code is done. What's left:

- [ ] **Create the OAuth "web" Reddit app** at <https://www.reddit.com/prefs/apps>.
  - Register BOTH redirect URIs: `https://mixpitch.com/auth/reddit/callback` (primary auth) and `https://mixpitch.com/account/reddit/callback` (secondary link).
  - Also register local-dev variants (e.g. `http://mixpitch.test/auth/reddit/callback` and `.../account/reddit/callback`).
- [ ] **Add OAuth env vars to production `.env`**:
  ```
  REDDIT_OAUTH_CLIENT_ID=<web app client id>
  REDDIT_OAUTH_CLIENT_SECRET=<web app client secret>
  REDDIT_OAUTH_REDIRECT_URI="${APP_URL}/auth/reddit/callback"
  ```
- [ ] **Verify the u/MixPitch bot has moderator or approved-submitter status** in r/MixPitch (required to post).
- [ ] **Deploy** (migrations will run automatically on deploy).
- [ ] **Smoke test in production**:
  - Sign in with Reddit as a fresh user → verify user created with `reddit_username` populated
  - Sign in with Google (existing flow), then link Reddit from settings → verify badge appears on `/@{username}`
  - Create + publish a Standard project, click "Post to r/MixPitch" → verify thread appears
  - Approve a pitch → verify Reddit post gets edited header + bot comment
  - Complete the project → verify Reddit post gets COMPLETED header + comment
  - Click "Remove from Reddit" → verify post deleted, database cleared

### Track C — Subreddit setup (manual mod work, non-code)
None of this is code — it's Reddit configuration and community seeding. Realistically a focused weekend + ongoing.

- [ ] **Write 5–7 subreddit rules**. Suggested: "Requests must link to a MixPitch project," "One request per user per week," "No paid gigs (use MixPitch's paid workflow)," "Mark solved when done," "No copyrighted content you don't own," "Be professional and constructive."
- [ ] **Configure post flair**: `[Request]`, `[Showcase]`, `[Discussion]`, `[Help]`, `[Meta]`.
- [ ] **Set up AutoMod rules**:
  - Auto-remove `[Request]` posts without a `mixpitch.com` link + reply with instructions
  - Rate limit per user per week
  - Sticky comment on every request with "How this works" boilerplate
  - Auto-flair posts based on title keywords
- [ ] **Build the wiki**:
  - `/how-this-works`
  - `/how-to-post-a-request`
  - `/for-producers`
  - `/rules`
- [ ] **Sidebar / community info**: what MixPitch is, how the sub relates, link to main site.
- [ ] **Polish the u/MixPitch bot profile**: proper bio, avatar, so it doesn't look like a shady bot.
- [ ] **Recruit 1–2 mods** beyond yourself.
- [ ] **Prepare seed posts**: welcome sticky, weekly discussion template, 3–5 example showcases.

---

## Suggested launch sequencing

Rough order to reduce risk:

1. **Week 0 (do first, no matter what):** Track A — rotate secrets, scrub history, kill debug mode. This is non-negotiable and doesn't need Reddit.
2. **Weeks 1–2:** Rest of Track A — privacy policy, Sentry, CI, finish or hide the stubbed features, cull debug noise.
3. **Weekend after Track A:** Track C — subreddit setup. Non-code, can be done in parallel by whoever runs the community. Don't launch the sub before code is deployment-ready.
4. **Deploy + Track B config:** Register the OAuth Reddit app, add env vars, deploy. Smoke test in production.
5. **Quiet organic phase:** You seed r/MixPitch yourself for weeks. Post 2–3 real requests, do the work as a producer, respond to others. Prove the loop with yourself as user zero.
6. **First "loud" announcement:** When the sub has baseline activity (~50–100 members, a handful of live threads), post a short intro in r/mixingmastering. See what breaks.
7. **Iterate.** Watch what drops off, what confuses new users, what the community wants that MixPitch doesn't do yet.

---

## Deferred / follow-up items (documented so they don't get lost)

These were explicitly excluded from Track B and are worth capturing for later:

**Product / UX follow-ups:**
- Badge placement on pitch cards and other producer attribution surfaces (badge is currently only on the primary public profile — deliberately, to validate the design before scattering it everywhere).
- Prompting synthesized-email users (`reddit_{id}@no-email.mixpitch.local`) to add a real email in an onboarding step. Currently they can update their email in profile settings but nothing nudges them.
- Contest winner announcement post-back: `PitchWorkflowService::selectContestWinner()` doesn't currently dispatch a Reddit update. Small addition using the same `editPost` + `postComment` primitives.
- Live Reddit karma display on the trust badge (would need a periodic refresh cron).

**Architectural follow-ups:**
- Multi-provider social_accounts table: current design puts one primary provider on `users` and a Reddit-only secondary link. If we later want Twitter/Discord/Instagram linking, this becomes a refactor.
- Post-as-user (Reddit posts under the owner's account rather than the bot) — requires user OAuth token management, refresh handling, revocation handling. Bigger scope.

**Explicitly won't-do:**
- Reddit → MixPitch project auto-creation. Product decision: never. AutoMod on the sub enforces the reverse direction.
- Two-way pitch/comment mirroring between MixPitch and Reddit threads. Spam risk, sync headaches, no clear user benefit.
- Cross-posting to other subreddits from within MixPitch. Manual mod work if desired; don't code it.

---

## Where things live (quick reference)

- **Setup guide:** `docs/reddit-integration.md`
- **Lifecycle reference:** `docs/reddit-post-lifecycle.md`
- **This status doc:** `docs/reddit-integration-status.md`
- **Original engagement plan:** `~/.claude/plans/snazzy-orbiting-sphinx.md` (approved plan file — reference for what was scoped)

Related code entry points:
- Bot HTTP layer: `app/Services/RedditService.php`
- Livewire trait for posting/unposting: `app/Livewire/Concerns/HasRedditPosting.php`
- Primary OAuth: `app/Http/Controllers/Auth/SocialiteController.php`
- Secondary account linking: `app/Http/Controllers/Account/RedditAccountController.php`
- Trust badge: `resources/views/components/reddit-badge.blade.php`
- Event listeners: `app/Providers/EventServiceProvider.php` (`$listen` array)
