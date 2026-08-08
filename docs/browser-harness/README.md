# MixPitch Browser Testing Harness

Puppeteer-based scripts that log in as real users, navigate the app, and screenshot rendered pages. Used for UX audits, bug reproduction, and visual sanity-checks alongside Pest tests.

> **What this is for:** rapid ad-hoc exploration where you need to *see* the app under specific state (e.g., "what does Alice's Pitches tab look like with three producers in different states?"). Not a replacement for tests — a complement.
>
> **What this is NOT:** a permanent CI harness. If you find yourself repeatedly asserting the same UI element exists, promote that check into a Pest v2 Livewire test, or plan the Pest v4 upgrade (browser testing is first-class in Pest 4).

---

## Contents of this folder

- `README.md` — this file
- `lib.js` — shared helpers (launch, login, shot, goto). Copy this into your scratch dir.
- `scripts/` — canonical example scripts illustrating each pattern
- `screenshots/` — 25 curated PNGs from the August 2026 audit, referenced by [`docs/ux-audit-standard-workflow-2026-08.md`](../ux-audit-standard-workflow-2026-08.md)

---

## Quick start (fresh machine or fresh context)

```bash
# 1. Working directory
mkdir -p /tmp/mixpitch-browser/shots && cd /tmp/mixpitch-browser

# 2. Puppeteer install — Chrome bundle is already cached at ~/.cache/puppeteer
npm init -y >/dev/null
PUPPETEER_SKIP_DOWNLOAD=1 npm install puppeteer

# 3. If Chrome not yet cached, install the exact version puppeteer expects
npx puppeteer browsers install chrome@151.0.7922.71   # match this to your installed puppeteer version

# 4. Copy the harness lib
cp /Users/michaelneumaier/Documents/Sites/mixpitch/docs/browser-harness/lib.js .

# 5. Write your investigation script (see scripts/ for templates) and run
node your-script.js
```

That's it. Everything below is context and gotchas.

---

## Test users

Seeded via tinker. All have `email_verified_at` set and use `password123`. Local dev only — do not deploy.

| Email | Role | Purpose |
|---|---|---|
| `alice-test@mixpitch.test` | musician | project owner (Alice) |
| `bob-test@mixpitch.test` | producer | primary producer for pitch flows (Bob) |
| `carol-test@mixpitch.test` | producer | second producer for multi-pitch scenes |
| `dave-test@mixpitch.test` | producer | third producer (usually left in `pending` for state variety) |

Recreate them with:

```bash
php artisan tinker --execute="
foreach ([
  ['alice-test@mixpitch.test','Alice Musician','client'],
  ['bob-test@mixpitch.test','Bob Producer','producer'],
  ['carol-test@mixpitch.test','Carol Mixer','producer'],
  ['dave-test@mixpitch.test','Dave Mastering','producer'],
] as [\$e,\$n,\$r]) {
  \$u = App\Models\User::updateOrCreate(['email'=>\$e], ['name'=>\$n,'password'=>bcrypt('password123'),'role'=>\$r]);
  \$u->email_verified_at = now(); \$u->save();
  echo \$u->email.' id='.\$u->id.PHP_EOL;
}
"
```

**Important:** `email_verified_at` MUST be set via a second `->save()` — passing it in `updateOrCreate` doesn't stick (likely a fillable/hidden guard). Otherwise login lands on `/email/verify` instead of `/dashboard`.

---

## Environment gotchas (Apple Silicon)

### Chrome runs via Rosetta 2

Puppeteer downloads x64 Chrome by default; the system Node is x64 too. On arm64 Macs, Chrome is translated via Rosetta — it works but is slow. This drives the following requirements:

- `protocolTimeout: 300000` (5 minutes) on `puppeteer.launch()`
- `waitForInitialPage: false` on launch
- **Never use `fullPage: true`** on screenshots — they consistently time out. Instead take multiple viewport-sized screenshots at different `window.scrollTo()` positions.
- Expect ~7 second launch time per `puppeteer.launch()`. Reuse one browser instance across multiple pages if you can.

To upgrade later: install arm64 Node (`brew install node`, or use `n`/`nvm` with arm64 arch), which will make puppeteer download arm64 Chrome and run natively.

### Valet self-signed cert

`APP_URL=https://mixpitch.test`. Launch puppeteer with:

```js
acceptInsecureCerts: true,
args: ['--ignore-certificate-errors', '--no-sandbox', '--disable-gpu']
```

---

## The login helper — non-obvious workaround

**Do NOT type into the login form with `page.type()`.** The Livewire v3 form uses live-model directives that re-render the input elements as you type, which destroys Puppeteer's DOM handles and throws `Execution context was destroyed`.

Working pattern (see `lib.js:login()`):

1. `goto('/login')`, extract CSRF from `<meta name="csrf-token">`
2. `page.evaluate()` — POST to `/login` via `fetch()` from *inside* the page so cookies stick
3. After the fetch resolves, explicitly `goto('/dashboard')` to give Puppeteer's page model a clean state

This works. Don't refactor it.

---

## Screenshot approach

- Default viewport: 1280x900 (desktop) or 390x844 (mobile via `{ mobile: true }` option to `launch()`)
- Take multiple scroll-position shots per page — `shotScrolled(page, name, 700)` scrolls to y=700 then screenshots
- Chrome is fast enough to scroll+shoot in ~1.5s but leave a buffer

### Wrap actions in `safe(name, fn)`

Any one action can fail (session lost, page navigated out, Livewire race). Wrap each in try/catch so one failure doesn't kill the run:

```js
async function safe(name, fn) { try { await fn(); } catch (e) { console.log('  ! failed', name, ':', e.message); } }
```

Every canonical script uses this pattern — copy it.

### Clicking tabs / dropdowns

Prefer selector-based clicks; text-match as fallback. Flux tabs use `<button name="{tabName}">` — click directly.

```js
await page.evaluate(() => document.querySelector('button[name="files"]').click());
await new Promise(r => setTimeout(r, 2500));  // wait for Livewire response
```

For arbitrary text matching, filter by `offsetHeight > 0` to avoid hidden elements.

### Filling forms after login page — same Livewire caveat

Livewire's `wire:model.live` will interrupt `page.type()` on any input. Workarounds:
- POST via `fetch()` (like login) when the endpoint is a plain form action
- Trigger Livewire methods directly via `Livewire.dispatch(event, ...)` — see `debug-tabs2.js` for finding the right component
- Use the service layer via `php artisan tinker --execute="..."` to seed state, then screenshot the outcome

Seeding-via-tinker is often the most reliable path. Puppeteer's job is to *observe* rendered state; PHP's job is to *create* the state.

---

## Common seed patterns

### Standard project owned by Alice, published, open for pitches

```bash
php artisan tinker --execute="
\$alice = App\Models\User::where('email','alice-test@mixpitch.test')->first();
\$p = App\Models\Project::factory()->for(\$alice)->create([
  'name' => 'YOUR PROJECT NAME',
  'title' => 'YOUR PROJECT NAME',
  'description' => 'Short description here.',
  'workflow_type' => App\Models\Project::WORKFLOW_TYPE_STANDARD,
  'is_published' => true,
  'status' => App\Models\Project::STATUS_OPEN,
  'genre' => 'Folk',
  'collaboration_type' => ['Mixing','Mastering'],
  'budget' => 0,
]);
echo 'slug: '.\$p->slug.PHP_EOL;
"
```

### Pitches in mixed states on a project

```bash
php artisan tinker --execute="
use App\Models\Project; use App\Models\Pitch; use App\Models\User;
\$p = Project::where('slug', 'YOUR-SLUG')->first();
\$svc = app(App\Services\PitchWorkflowService::class);
\$owner = \$p->user;
foreach ([10,11,12] as \$pid) {   // Bob, Carol, Dave user ids
  \$producer = User::find(\$pid);
  \$svc->createPitch(\$p, \$producer, ['agreed_to_terms'=>true]);
}
// Approve Bob + Carol, leave Dave pending
\$svc->approveInitialPitch(Pitch::where('project_id',\$p->id)->where('user_id',10)->first(), \$owner);
\$svc->approveInitialPitch(Pitch::where('project_id',\$p->id)->where('user_id',11)->first(), \$owner);
"
```

### Bob submits work for review (creates a snapshot)

```bash
php artisan tinker --execute="
use App\Models\Pitch; use App\Models\PitchFile;
\$bob = Pitch::find(BOB_PITCH_ID);
PitchFile::create([
  'pitch_id' => \$bob->id, 'user_id' => \$bob->user_id,
  'file_path' => 'pitch_files/'.\$bob->id.'/test-track.mp3',
  'file_name' => 'test-track.mp3',
  'mime_type' => 'audio/mpeg', 'size' => 36864,
]);
app(App\Services\PitchWorkflowService::class)->submitPitchForReview(\$bob, \$bob->user);
echo 'now '.\$bob->fresh()->status.PHP_EOL;
"
```

### Small audio file for real upload testing

```bash
ffmpeg -f lavfi -i "sine=frequency=440:duration=3" -c:a libmp3lame -b:a 96k /tmp/test-track.mp3 -y
```

3 seconds of 440Hz sine, ~36KB. Enough to exercise upload validation without hitting size limits.

### Alice requests revisions

```bash
php artisan tinker --execute="
\$bob = App\Models\Pitch::find(BOB_PITCH_ID);
\$alice = App\Models\User::find(ALICE_ID);
app(App\Services\PitchWorkflowService::class)->requestPitchRevisions(
  \$bob, \$bob->current_snapshot_id, \$alice,
  'Feedback text (must be >= 10 chars).'
);
"
```

---

## Key URLs (Alice's viewpoint)

- `/login`, `/register`, `/dashboard`, `/profile/edit`
- `/projects` — public discovery
- `/projects/{slug}` — public project detail (Livewire ProjectHeader — fixed 2026-08-07, was 500ing)
- `/manage-standard-project/{slug}` — owner manage view (tabs: Overview / Pitches / Files / Project)
- `/manage-project/{slug}` — router that redirects to the workflow-specific manage component
- `/projects/{slug}/pitches/create` — start-a-pitch form (single checkbox)
- `/projects/{slug}/pitches/{pitch-slug}` — producer's manage-pitch view
- `/projects/{slug}/pitches/{pitch-slug}/snapshots/{snapshot-id}` — snapshot review (id, not slug — see B5 in audit doc)
- `/projects/{slug}/pitches/{pitch-slug}/payment/overview` — payment overview
- `/account/reddit/connect` — OAuth secondary link
- `/auth/reddit/callback` — OAuth primary auth

---

## Cache invalidation

Whenever an agent (or you) modifies Blade files, clear the compiled view cache before re-verifying visually:

```bash
php artisan view:clear && php artisan cache:clear
```

If you modify Tailwind classes or JS modules, ensure `npm run dev` is running. Backend-only edits (routes, controllers, migrations, seeders) don't need Vite.

---

## Cleanup

To reset the local dataset between explorations:

```bash
php artisan migrate:fresh --seed   # WARNING: wipes local DB
# Then re-run the user-seeding block above
```

---

## Reproducing the August 2026 audit

The [UX audit doc](../ux-audit-standard-workflow-2026-08.md) references the 25 screenshots in `screenshots/`. To regenerate them:

1. Set up harness (Quick start above)
2. Seed users, project "please help mix my acoustic demo" for Alice, three pitches from Bob/Carol/Dave with Bob+Carol approved
3. Run each script in `scripts/` in order — they write to `/tmp/mixpitch-browser/shots/`
4. Compare against `screenshots/`

Any drift is either a real regression or an intentional design change worth noting.
