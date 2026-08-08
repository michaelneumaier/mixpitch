# Standard Workflow UX Audit — August 2026

**Scope:** end-to-end Standard project workflow from the perspective of a first-time musician (Alice) and a first-time producer (Bob), with the Reddit integration overlaid as a real distribution channel.

**Method:** code exploration + rendered-page inspection via Puppeteer harness (details in [`browser-harness/README.md`](browser-harness/README.md)). Two personas seeded, real state transitions applied through `PitchWorkflowService` and `ProjectManagementService`, screenshots captured at each state.

**Environment during audit:** `main` branch as of 2026-08-07, local dev (Valet, SQLite, `mixpitch.test`). All findings verified against the running site, not just source.

---

## Executive summary

Six bugs were found and fixed in this pass — one was a pre-existing dead code path, one was a production-blocker Livewire crash on the public project detail page, one was a stray `</div>` that had silently disabled two whole tabs, and three were UX regressions or discoverability gaps. In parallel, the Reddit "Post to r/MixPitch" feature was elevated from a buried admin-dropdown item to a first-class option inside a new Share Project modal — matching the [strategic positioning](../MEMORY.md#L2-L3) of Reddit as MixPitch's primary community/distribution layer.

The underlying UX for the Standard workflow is **stronger than expected**. The QuickProjectModal creation flow, the state-aware Overview card, the Pitches tab with clear multi-state producer rows, the producer's manage-pitch view with embedded feedback and resubmit affordances, and the snapshot review page with audio player + conversation thread — all are well-composed. The bugs were concentrated in edges (dead routes, stray divs, silent state-flip gaps); the core is sound.

---

## What was fixed

### B1 — Dead `/projects/upload` route
**Symptom:** `/projects/upload` returns 404 to authenticated users.

**Root cause:** `Route::get('/projects/{project}', 'show')` at `routes/web.php:67` was registered before `Route::get('/projects/upload', ...)` at `:73`. Laravel matched `/projects/upload` against `{project}` first, tried to resolve `"upload"` as a project slug, failed, and 404'd. The `/projects/upload` route pointed at `ProjectController::createProject()` which returned a Blade view loading an old Bootstrap-era `UploadProjectComponent` — the current creation flow is `App\Livewire\QuickProjectModal` opened via the dashboard's `WorkflowDropdown`.

**Fix:** Rather than reorder the routes, the entire dead chain was removed:
- Deleted `app/Livewire/UploadProjectComponent.php`, `resources/views/livewire/upload-project-component.blade.php`, `resources/views/projects/upload-project.blade.php`, `resources/views/projects/.upload.blade.php`, `resources/views/projects/create_step2.blade.php`, `tests/Feature/Livewire/UploadProjectComponentTest.php`
- Removed `projects.upload`, `projects.createStep2`, `projects.storeStep2` routes from `routes/web.php`
- Removed `createProject()` and `createStep2()` methods from `ProjectController`

**Tests:** All 295 Project tests pass; no regressions. See `docs/browser-harness/screenshots/10a-dashboard-create-dropdown.png` and `11c-quick-modal-standard-forced.png` for the current (correct) creation flow.

---

### B2 — Public project detail page 500s with `ModelNotFoundException`
**Symptom:** `/projects/{slug}` throws `Illuminate\Database\Eloquent\ModelNotFoundException: No query results for model [App\Models\Pitch]` for *every* case except a producer with an existing pitch. Owner viewing own project → crash. Logged-out visitor → crash. Producer without a pitch → crash. In debug mode the Ignition UI is shown; in prod this would be a 500 page.

**Root cause:** `App\Livewire\Project\ProjectHeader::mount()` had the type hint `?Pitch $userPitch = null`. Livewire v3's `ImplicitlyBoundMethod` walks every `mount()` parameter whose type-hint is a `UrlRoutable` (any Eloquent model) and tries to route-model-bind the passed value. When the value is `null`, `resolveRouteBinding(null)` returns null, Livewire throws `ModelNotFoundException`.

**Fix:**
- `app/Livewire/Project/ProjectHeader.php` — dropped `?Pitch` type hint on the `mount()` parameter (with an explanatory PHPDoc). The public property `public ?Pitch $userPitch = null` still enforces the type once mounted, plus a defensive `instanceof Pitch` guard normalizes the assignment.
- `app/Http/Controllers/ProjectController.php@show` — hardened `$canPitch` computation to mirror `PitchPolicy::create()` (owner check, `isOpenForPitches()`, client-management/direct-hire guards) and delegate to `User::canCreatePitchForProject()` for subscription limits.
- `resources/views/livewire/project/project-header.blade.php:105` — one-char guard for a pre-existing crash on logged-out visitors: `$user && $project->user_id === $user->id`.

**Tests:** 4 new Pest feature tests at `tests/Feature/Http/ProjectDetailPageTest.php` covering guest, producer-without-pitch, producer-with-pitch, and owner cases. All 295 Project tests pass. See `screenshots/41a-bob-project-detail-fixed.png` for the fixed state.

---

### B3 — Free-plan project limit too tight (1 → 3)
**Symptom:** Free plan's `max_projects_owned = 1`. As soon as a user created their first project, a persistent red "You have reached your active project limit. Upgrade to Pro..." banner appeared on every dashboard load. For a Reddit-community-driven onboarding, where casual users may want to post multiple small requests, this is punishing.

**Root causes (two):**
1. Limit was seeded in three places (`CompleteSubscriptionLimitsSeeder`, `SubscriptionLimitsSeeder`, `UpdateSubscriptionPricingSeeder`) as `max_projects_owned = 1`.
2. `User::getActiveProjectsCount()` counted every non-completed project including `WORKFLOW_TYPE_CLIENT_MANAGEMENT` projects — which have their own lifecycle and payout model and shouldn't consume free-plan slots.

**Fix:**
- All three seeders bumped `max_projects_owned` from 1 → 3.
- New reversible data migration `database/migrations/2026_08_07_234649_update_free_plan_max_projects_owned_to_three.php` to update the already-seeded prod row.
- `app/Models/User.php:674-690` — `getActiveProjectsCount()` now excludes Client Management projects (with a `whereNull` guard for legacy rows).
- `app/Http/Controllers/DashboardController.php:54-64` — removed the persistent red `error` alert at limit. Downgraded to a single friendly `info` alert that names the actual limit and mentions completing/archiving a project *before* mentioning upgrade. Dropped the 80%-warning as noise.
- `resources/views/dashboard.blade.php:8-38` — added `info` alert level rendered as a soft blue callout with `information-circle` icon and an optional "See Pro Plans" link (only for free plan). Red "Upgrade Now" call-to-action reserved for genuine `error` alerts.

**Tests:** 9 tests in `tests/Feature/SubscriptionLimitsTest.php` covering active/completed counting, Client Management exclusion, HTTP redirect at limit, Pro plan unlimited. All pass.

Pro Artist and Pro Engineer limits are unchanged. Upgrade incentive preserved — just less punishing.

---

### B4 — Files & Project tabs render blank
**Symptom:** On `/manage-standard-project/{slug}`, clicking "Files" or "Project" tabs shifts the underline correctly but the content area is completely empty. Overview and Pitches worked. This was invisible for projects without pitches — the bug surfaced only when the pitch list had at least one item.

**Root cause (beautiful):** `resources/views/components/project/pitch-list.blade.php` had malformed div nesting inside `@if ($pitch->snapshots->count() > 0)`. Two `</div>` tags at lines 695-696 were placed *inside* the snapshots-count conditional but were meant to close the pitch card (opened at :246-247) and the `wire:key="pitch-X"` wrapper (opened at :240) — *outer* elements. Additionally, the `@if completion_feedback` block was emitted outside the pitch card entirely.

Net effect: iterations where a pitch had snapshots leaked 2 extra `</div>`s past the pitches panel. The browser's HTML parser, on receiving extra closes at the `<flux:tab.panel name="pitches">` level, corrected by reparenting the sibling `<flux:tab.panel name="files">` and `<flux:tab.panel name="project">` DOM nodes *inside* the pitches panel. Then Flux's tab-group JS (`UITabGroup.walkPanels`) — which iterates only direct children of `<ui-tab-group>` — couldn't find them, threw `Could not find panel...`, and never marked them `data-selected`. CSS `[&:not([data-selected])]:hidden` kept them permanently hidden.

**Fix:** `resources/views/components/project/pitch-list.blade.php` lines 693-716 — moved the pitch-card `</div>` and `wire:key` `</div>` closes outside the `@if snapshots` block and after the `@if completion_feedback` block. Every iteration now closes both wrappers regardless of which optional blocks fire.

**Tests:** 3 new tests in `tests/Feature/Livewire/ManageStandardProjectTabsTest.php`, including a diff-count regression test that seeds one pitch with snapshot + one without, and asserts the div count between the pitches panel wrapper and the files panel wrapper is balanced (108 before fix vs 106 after). All pass. See `screenshots/final-files.png` and `screenshots/final-project.png`.

---

### B6 — Share Project button disappears once a project has pitches
**Symptom:** The primary action in the Manage dropdown at `resources/views/livewire/project/project-header.blade.php:120-134` was state-conditional: for a Standard project with `status='open'` and zero pitches → "Share Project"; with 1+ pitches → "Review Pitches". The empty-state Share button in `standard-overview-card.blade.php` also disappeared once pitches arrived. Result: after the first pitch, Alice had **zero paths** to the Share modal — meaning no way to post to Reddit or to update her Reddit post.

This is a pre-existing template design issue that was invisible before the Reddit share-modal consolidation because "Post to r/MixPitch" also lived in the dropdown.

**Fix:** `resources/views/livewire/project/project-header.blade.php:600-606` — added a permanent `flux:modal.trigger name="shareProject"` menu item between "View Public" and "Project Settings", gated on `!$project->isDirectHire()` and `$primaryAction['modal'] !== 'shareProject'` (avoids duplication when the primary action is already Share).

**Verification:** See `screenshots/85-b6-dropdown-has-share-with-pitches.png` (Share Project now appears between View Public and Project Settings even when the project has 3 pitches) and `screenshots/86-b6-share-modal-opens-from-dropdown.png` (clicking it opens the Share modal correctly).

Not covered by tests (single-line template guard); manual visual verify.

---

### Reddit — "Post to r/MixPitch" moved into Share Project modal
**Motivation:** Before this change, "Share Project" opened a share dialog and "Post to r/MixPitch" was item #4 in an 8-item Manage dropdown alongside Sync Options, Backup History, and Delete Project. This treated the platform's primary distribution channel like an admin utility. Strategic positioning ([`MEMORY.md`](../MEMORY.md) — "project_positioning_refined") is that Reddit is a first-class share sink.

**What shipped:**
- New `app/Livewire/Project/ShareProjectModal.php` — Livewire component that owns the Reddit posting state via the existing `HasRedditPosting` trait, exposes `publicUrl`, `redditPreviewBody`, `ownerHasLinkedReddit`, `showsRedditSection` computed properties.
- New `resources/views/livewire/project/share-project-modal.blade.php` — Flux modal (`name="shareProject"`, `md:w-2xl`) with three sinks:
  1. **Public link** — input + Copy button
  2. **Post to r/MixPitch** — collapsible post preview showing the exact title + body from `RedditService::buildPostBody()`; a big "Post to r/MixPitch" button; a yellow "Get credited on Reddit — Connect your Reddit account" nudge for users who haven't linked their Reddit account (OAuth conversion moment); posted/posting/ready/unpublished visual states
  3. **Share elsewhere** — Twitter/X, Facebook, LinkedIn, Reddit (manual)
- Reddit section hides entirely for Client Management projects (matches prior gate).
- `HasRedditPosting` trait moved off `ManageStandardProject` and `ManageContestProject` — state now lives only on the modal to avoid duplicate polling broadcasts firing double toasts.
- Removed Reddit items from the Manage dropdown in `resources/views/livewire/project/project-header.blade.php`.
- Empty-state "Share Project" button in `resources/views/livewire/project/component/standard-overview-card.blade.php:84` now opens the modal instead of being a no-op that switched tabs.

**Tests:** 8 new tests in `tests/Feature/Livewire/ShareProjectModalTest.php` covering render/hide gates, preview body wiring, connect nudge, posted-state UI, unpublished warning, public URL. `tests/Feature/Livewire/PostToRedditButtonTest.php` (9 tests) refactored to target the new modal. 63 tests in the `Reddit|ShareProject|PostToReddit` filter, all green.

**Reddit backend unchanged:** the bot posting, edit-back-on-lifecycle-event, comment-back, rate limits, and jobs (`PostProjectToReddit`, `DeleteRedditPost`, `UpdateRedditPostFor*`) are untouched. See [`reddit-post-lifecycle.md`](reddit-post-lifecycle.md) for the mechanics.

See `screenshots/83a-share-modal-opened.png` (default state) and `screenshots/84a-preview-expanded.png` (with post preview open).

---

## Non-bug — B5 (test data error)

**Claim:** snapshot show page `/projects/{slug}/pitches/{pitch-slug}/snapshots/1` returned 404.

**Reality:** Snapshot ID 1 belonged to a different pitch. `App\Livewire\Pitch\Snapshot\ShowSnapshot::mount()` at `app/Livewire/Pitch/Snapshot/ShowSnapshot.php:25-27` correctly rejects `pitch_id`/`project_id` mismatches with `abort(404)`. Requesting snapshot ID 2 (Bob's actual snapshot) → 200 OK. The route, component, and inline authorization all work correctly.

**No production changes.** Tests in `tests/Feature/Livewire/Pitch/Snapshot/ShowSnapshotTest.php` expanded from 1 → 7 covering owner view, pitch owner view, unrelated user 403, guest redirect, snapshot-from-another-pitch 404, pitch-from-another-project 404, and the original renders test. All pass. Total `Snapshot` suite: 46 tests, all green.

**Lesson for the harness:** never hardcode snapshot IDs — always look them up from a fresh tinker call. Same for pitch IDs when multiple runs pile up seed data.

---

## UX wins discovered (this was mostly a good news audit)

While hunting for problems, the audit turned up a lot of already-good design that had gone unappreciated. Worth listing so nobody accidentally regresses them.

### QuickProjectModal (screenshot 11c)
Single-form modal opened via the dashboard's "Create" dropdown. Project Name → Artist/Type → Genre → Description → Collaboration Services chips (Production preselected). Note at the bottom: "You can add more details after creating the project." This is far friendlier than the older 4-step wizard that `CreateProject.php` still implements for the *edit* flow. The empty-state chrome and inline validation feel good.

### Dashboard state awareness (screenshots 05a, 20a)
- 0 projects: "Ready to Start Creating? — Create Project / Browse Projects" empty state
- Quota chip ("Free / 0/1 / 0/3 / 0%") with hoverable details
- Persistent "Complete Your Profile" nag (Username, Bio, Location, Website) with a big orange "Set Up Profile" CTA — perfect surface for the coming "Connect Reddit account" chip when we want to promote the OAuth link
- Billing & Payments section always visible

### Manage Overview (screenshots 12a, 30a2 baseline)
State-aware center card:
- 0 pitches: "Waiting for Pitches" empty state with Share Project button
- 1+ pitches: "Producers Working — 2 producers are currently working on pitches" + View Progress button
- Pitch Status Breakdown (Pending Approval / In Progress / Ready for Review counters)
- Stats tiles (Total Pitches / Project Files / Days Active / Action Needed — the last one in orange with a count is genuinely useful)
- Quick Actions row (View Pitches / Manage Files / Project Settings / View Public Page)

### Pitches tab (screenshot 50a)
When Alice has three producers in different states (Bob ready-for-review, Carol in-progress, Dave pending), the layout shows all three simultaneously with:
- Producer avatar + name
- License Pending badge (when applicable)
- State-specific status badge (Ready for Review / In Progress / Pending)
- State-specific action buttons (Review + Actions dropdown / Remove Access / Allow Access)
- Expandable Snapshots panel with version list
- "Auto-allow access" toggle in the top right (power-user shortcut)

### Producer's manage-pitch view (screenshots 42a, 72b)
- "In Progress" state (42a): title, workflow badge, deadline/file/producer status row, "Ready to Start Working — Your pitch has been approved!" card with Download Project Files button and 25% progress bar, Upload Files section with Uppy dropzone + Google Drive integration, Pitch Files list, Internal Notes textarea, Feedback & Revision History empty state
- "Revisions Requested" state (72b): orange badge, 55% progress bar, "Review Feedback" button, yellow-highlighted "Feedback from Project Owner" card with Alice's exact message, "Your Response (Optional)" textarea, big "Resubmit Pitch" button with red "Upload any new files above before resubmitting" hint

Alice's description is embedded further down the same page (72c-tail) so Bob doesn't need to switch pages to refer to it. Small detail, big UX win.

### Snapshot review page (screenshots 73a, 73b)
- Producer's Pitch title with "Revisions Requested" badge
- "Feedback & Response" section labeled "Conversation thread for this snapshot" — Alice's message as a chat-card with avatar, "Project Owner" tag, "Revision Request" chip, timestamp, full feedback text
- "Pitch Files" section with an inline audio player (00:00 / 00:00 scrubber), per-file Global/View chip for comment scope, comment count
- "Back to Project" button

### Request Revisions modal (screenshot 70c)
Well-composed: title, helper subtitle, prompt heading, labeled textarea with an instructive placeholder, "Minimum 10 characters required" validation hint, Cancel / Send Request buttons. Nothing to fix here.

### Share Project modal (screenshots 83a, 84a, 86)
Covered above under the Reddit-modal item. Genuinely the best-executed piece of work in this batch.

### Mobile — landing and register (screenshots 60a, 61)
- Landing: hero copy stacks vertically, role-toggle chips remain two-column, hamburger icon in the top right, key selling points visible without needing to scroll far
- Register: full-width form, all fields legible, gradient Create Account button prominent, Google/Reddit social logins present (though below the fold — worth promoting on mobile)

Deeper mobile testing (dashboard, manage view, tabs on small screens) was blocked by Rosetta-Chrome instability partway through the mobile run. Landing/register verified only.

---

## Deferred / follow-up items

These surfaced during the audit but were out of scope for the fix pass. Each is a discrete piece of work suitable for a future session.

### High-impact
- ~~**Pitch cover letter / proposal field.**~~ ✅ Done 2026-08-08 — optional cover letter (max 2,000 chars) on a fully rebuilt Flux UI create page; expandable display on owner pitch cards; producer editing gated by `PitchPolicy::updateCoverLetter` (pending / contest-entry pre-deadline). The rebuild also fixed a latent bug: the license checkbox was required by validation but never rendered on the old form. (Reddit badge on pitch cards shipped 2026-08-07.)
- ~~**Musician vs producer role differentiation at signup.**~~ ✅ Done 2026-08-07 — dashboard empty state is now role-aware: producers get "Find Your Next Project" with Browse Projects as primary CTA. (Signup-time role capture itself unchanged.)
- ~~**Stripe Connect prompt timing.**~~ ✅ Done 2026-08-08 — accepted pitches on paid standard projects show a "Set Up Payouts" callout on the producer's manage-pitch page until `stripe_account_id` exists (cheap gate by design; payment-time hard gate still enforces full readiness). CTA → `payouts.setup.index`.
- **Mobile deep verification.** Only landing + register captured. Dashboard, Manage view, Pitches tab on 390px width haven't been checked. Rosetta-Chrome instability blocked the deeper run — retry when arm64 Node is installed.

### Medium
- ~~**Reddit trust badge on pitch cards.**~~ ✅ Done 2026-08-07 — badge renders in both mobile and desktop pitch-card layouts (`pitch-list.blade.php`).
- ~~**"Connect your Reddit" chip in dashboard profile-completion nag.**~~ ✅ Done 2026-08-07 — orange "Not linked" chip in `profile-setup-banner.blade.php` links to `account.reddit.connect`.
- ~~**Contest winner Reddit post-back.**~~ ✅ Done 2026-08-07 — `ContestWinnerSelected` event → `SyncRedditPostOnContestWinnerSelected` listener → `UpdateRedditPostForContestWinner` job. See [`reddit-post-lifecycle.md`](reddit-post-lifecycle.md).
- **"IN PROGRESS" post-back timing on Standard workflow.** The post-back fires on `approveInitialPitch` (permission-to-work), not on `approveSubmittedPitch` (approval-of-actual-work). Worth reconsidering — "IN PROGRESS" implies real work happening, not just permission granted.
- **Scope creep protection.** No revision cap. No "revisions used: 2/3" counter surfaced to either party. Standard workflow assumes revision cycles but doesn't bound them.

### Lower / infra
- **Pest v4 upgrade for browser-based UX regression tests.** The Puppeteer harness in this pass is ad-hoc. Pest 4's browser plugin would let assertions like "Share Project menu item exists in Manage dropdown" live alongside feature tests and run in CI. Requires Pest 2 → 4 upgrade — non-trivial due to lifecycle-hook and dataset changes. Worth a scoped audit before starting.
- ~~**Free-plan `email_verified_at` seed quirk.**~~ ✅ Investigated 2026-08-08 — root cause confirmed: `email_verified_at` is in `$casts` but deliberately NOT in `$fillable` on `User`, so `updateOrCreate` silently drops it. Intentionally left non-fillable (mass-assignable verification timestamps are a hardening risk; matches Laravel convention). The second-`->save()` pattern in the harness README is the correct workaround.
- ~~**`ProjectController::edit` + orphaned edit blades**~~ ✅ Deleted 2026-08-08 — unreachable method (route was commented out), commented route line, and both stale blades removed.

### Won't do (explicit)
- Reddit → MixPitch project auto-creation (would blur the strategic line)
- Two-way pitch/comment mirroring from Reddit into MixPitch (spam risk)
- Cross-posting to other subreddits from within the app (manual mod work by design)

---

## Files changed

Sixteen files created, ~14 modified across 6 fixes + Reddit consolidation. Full list:

**Created:**
- `app/Livewire/Project/ShareProjectModal.php`
- `resources/views/livewire/project/share-project-modal.blade.php`
- `database/migrations/2026_08_07_234649_update_free_plan_max_projects_owned_to_three.php`
- `tests/Feature/Http/ProjectDetailPageTest.php` (4 tests, B2)
- `tests/Feature/SubscriptionLimitsTest.php` (9 tests, B3)
- `tests/Feature/Livewire/ManageStandardProjectTabsTest.php` (3 tests, B4)
- `tests/Feature/Livewire/ShareProjectModalTest.php` (8 tests, Reddit modal)
- `tests/Feature/Livewire/Pitch/Snapshot/ShowSnapshotTest.php` (expanded 1 → 7, B5)
- `docs/browser-harness/README.md` + `lib.js` + `scripts/` + `screenshots/`
- `docs/ux-audit-standard-workflow-2026-08.md` (this document)

**Modified (Standard-workflow-touching):**
- `routes/web.php` (B1: removed 3 routes)
- `app/Http/Controllers/ProjectController.php` (B1: removed 2 methods; B2: hardened show())
- `app/Http/Controllers/DashboardController.php` (B3: alert refactor)
- `app/Models/User.php` (B3: getActiveProjectsCount excludes CM)
- `app/Livewire/Project/ProjectHeader.php` (B2: mount() type-hint fix, removed unused postToReddit)
- `app/Livewire/Project/ManageStandardProject.php` (Reddit trait removed)
- `app/Livewire/Project/ManageContestProject.php` (Reddit trait removed)
- `resources/views/livewire/project/project-header.blade.php` (B2 guard; B6 permanent Share item; Reddit items removed)
- `resources/views/livewire/project/manage-standard-project.blade.php` (share modal include)
- `resources/views/livewire/project/manage-contest-project.blade.php` (share modal include, polling script)
- `resources/views/livewire/project/component/standard-overview-card.blade.php` (empty-state Share button targets modal)
- `resources/views/dashboard.blade.php` (B3: info alert style)
- `resources/views/components/project/pitch-list.blade.php` (B4: div nesting)
- `database/seeders/{CompleteSubscription,Subscription,UpdateSubscriptionPricing}LimitsSeeder.php` (B3: 1 → 3)
- `tests/Feature/Livewire/PostToRedditButtonTest.php` (refactored to new modal)

**Deleted (B1):**
- `app/Livewire/UploadProjectComponent.php`
- `resources/views/livewire/upload-project-component.blade.php`
- `resources/views/projects/{upload-project,.upload,create_step2}.blade.php`
- `tests/Feature/Livewire/UploadProjectComponentTest.php`

---

## Test coverage delta

- 4 new tests — public project detail page (B2)
- 9 new tests — free plan project limit (B3)
- 3 new tests — manage-standard-project tab panels (B4)
- 6 new tests — snapshot show URL (B5, from 1)
- 8 new tests — Share Project modal (Reddit consolidation)
- 9 tests refactored — PostToRedditButton retargeted

Total: **35 net-new tests** plus refactors. Full Project + Reddit + Subscription + Snapshot suites all pass.

---

## Screenshot index

Referenced from `docs/browser-harness/screenshots/`. Naming: `{sequence}{part}-{scene}.png`. Files kept as originally captured — do not renumber, they anchor cross-references in this doc.

| # | Screenshot | Scene |
|---|---|---|
| 01a | landing | Public landing hero |
| 02 | register | Register form (with Google + Reddit social login) |
| 04a | projects-top | Public /projects discovery |
| 05a | dashboard | Alice's dashboard (empty state) |
| 10a | dashboard-create-dropdown | Dashboard "Create" workflow-picker dropdown |
| 11c | quick-modal-standard-forced | Standard-project QuickProjectModal |
| 12a | manage-project-top | Manage view Overview (0 pitches) |
| 13 | manage-dropdown-open | Manage dropdown BEFORE Reddit-consolidation (for reference) |
| 30a2 | alice-pitches | Pitches tab with 3 producers in different states |
| 50a | alice-pitches-ready-review | Pitches tab after Bob submits (Review button + Actions dropdown) |
| 41a | bob-project-detail-fixed | B2 verified — /projects/{slug} renders for Bob |
| 42a | bob-manage-pitch-top | Bob's manage-pitch "In Progress" state |
| 70c | alice-request-revisions-modal | Request Revisions modal |
| 72b | bob-manage-pitch-rev | Bob's view after revisions requested |
| 73a | alice-snapshot-view | Snapshot review page |
| 73b | alice-snapshot-y700 | Snapshot review scrolled — feedback conversation + audio player |
| 82a | fresh-overview | Fresh project (0 pitches) with Share button visible |
| 83a | share-modal-opened | New Share Project modal (default state) |
| 84a | preview-expanded | Share Project modal with Reddit post preview open |
| 85 | b6-dropdown-has-share-with-pitches | B6 verified — Share Project menu item persists |
| 86 | b6-share-modal-opens-from-dropdown | B6 verified — modal opens from dropdown |
| final-files | | B4 verified — Files tab renders |
| final-project | | B4 verified — Project tab renders |
| 60a | mobile-landing | Mobile landing (390px width) |
| 61 | mobile-register | Mobile register form |

---

## Continuing this work in a fresh context

Everything you need to pick up where this audit left off:

1. Read [`docs/browser-harness/README.md`](browser-harness/README.md) first — Quick Start section boots the Puppeteer harness in ~5 minutes.
2. Re-seed test users using the tinker block in the harness doc.
3. Reference this document for what's already done, what's known-good UX, and what's queued (Deferred section).
4. New investigations should extend the harness scripts pattern and follow the same "seed via tinker, screenshot via Puppeteer, verify via Pest" loop.

The follow-up items in the Deferred section are prioritized. The original four highest-leverage moves (Reddit badge on pitch cards, role-aware dashboard empty states, Connect-Reddit chip, contest-winner post-back) all shipped 2026-08-07. Highest-leverage next moves now:

1. ~~Pitch cover letter / proposal field~~ (✅ shipped 2026-08-08)
2. ~~Stripe Connect prompt timing~~ (✅ shipped 2026-08-08)
3. Mobile deep verification (blocked on arm64 Node install for the harness)
4. Revision cap / scope-creep counter for the Standard workflow

None of these require agents or ceremony — each is ~1 hour of focused work.
