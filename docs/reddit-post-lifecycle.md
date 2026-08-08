# Reddit Post Lifecycle

A r/MixPitch post created by MixPitch is a **live artifact** — it reflects project state changes back to Reddit until the project is complete or the owner removes it.

## Creation

**Trigger:** the project owner clicks "Post to r/MixPitch" in the project header dropdown (`ManageStandardProject` or `ManageContestProject`).

**Preconditions:**
- Project is `is_published = true`
- Project has a title and description
- Project has NOT already been posted (`reddit_post_id` is null)
- User has posted fewer than 3 projects to Reddit in the past hour

**What happens:**
1. `HasRedditPosting::postToReddit()` dispatches `PostProjectToReddit` (queued, 3 tries, 15-min backoff).
2. Job calls `RedditService::submitProject()` → POST to `/api/submit` on r/MixPitch as `u/MixPitch` bot.
3. On success the job stores `reddit_post_id`, `reddit_permalink`, `reddit_posted_at`, and `reddit_original_body` (the full formatted body — used later for edit updates).
4. Frontend polls every 3s for up to 5 minutes; once `reddit_post_id` is set, the button changes to "View on Reddit" and a "Remove from Reddit" option appears.

**Not eligible:** Client Management projects. The UI hides the option; the trait's guards would reject the action if invoked directly.

## Updates (post-back on lifecycle events)

The Reddit post is edited (post body) AND commented on (top-level bot comment) at two moments:

### Pitch accepted

**Trigger:** `PitchWorkflowService::approveInitialPitch()` succeeds — dispatches `ProjectPitchAccepted` event.

**Listener:** `SyncRedditPostOnPitchAccepted` (queued). Skips if `reddit_post_id` is null. Otherwise dispatches `UpdateRedditPostForPitchAccepted` job.

**Effect on the Reddit thread:**
- Post body: prepended with `---\n🎧 **IN PROGRESS** — accepted [date]. Producer: {u/producer-if-linked / @producer on MixPitch}\n---\n{original body}`.
- Top-level comment posted by `u/MixPitch`: "This project has been accepted by [producer]. Follow along on MixPitch → [url]".

If the producer has linked their Reddit account, attribution uses `u/their_reddit_username`. Otherwise it uses their MixPitch handle.

### Contest winner selected

**Trigger:** `PitchWorkflowService::selectContestWinner()` succeeds — dispatches `ContestWinnerSelected` event.

**Listener:** `SyncRedditPostOnContestWinnerSelected` (queued). Skips if `reddit_post_id` is null. Otherwise dispatches `UpdateRedditPostForContestWinner` job.

**Effect on the Reddit thread:**
- Post body: prepended with `---\n🏆 **WINNER SELECTED** — [date]. Winner: {u/winner-if-linked / @winner on MixPitch}\n---\n{original body}`.
- Top-level comment posted by `u/MixPitch`: "This contest has a winner! Congratulations to [winner]. See the results on MixPitch → [url]".

### Project completed

**Trigger:** `ProjectManagementService::completeProject()` succeeds — dispatches `ProjectCompleted` event.

**Listener:** `SyncRedditPostOnProjectCompleted` (queued). Skips if `reddit_post_id` is null. Otherwise dispatches `UpdateRedditPostForProjectCompleted` job.

**Effect on the Reddit thread:**
- Post body: prepended with `---\n✅ **COMPLETED** on [date]\n---\n{original body}`. (If a pitch-accepted header was previously prepended, it gets replaced — we always edit against `reddit_original_body`.)
- Top-level comment posted: "This project is now complete. See the finished work on MixPitch → [url]".

## Deletion

**Trigger:** project owner clicks "Remove from Reddit" in the project header dropdown. Requires a confirmation prompt.

**What happens:**
1. `HasRedditPosting::unpostFromReddit()` dispatches `DeleteRedditPost` (queued, 3 tries, 15-min backoff).
2. Job calls `RedditService::deletePost()` → POST to `/api/del`.
3. On success, clears `reddit_post_id`, `reddit_permalink`, `reddit_original_body`. Preserves `reddit_posted_at` as an audit signal ("this project *was* posted at some point").

The UI reverts to showing "Post to r/MixPitch" (since `reddit_post_id` is null again, the owner could re-post if they wanted to).

## Failure modes

**Bot post was manually deleted from Reddit:**
- Update jobs call `editPost` and `postComment` which will 403 from Reddit.
- `UpdateRedditPost*` jobs log the edit failure and continue to the comment; if the comment also fails, the job releases and retries (up to 3 tries).
- No user-visible failure — the project workflow completes normally.

**Bot rate-limited by Reddit:**
- API returns 429 or `RATELIMIT` error in the JSON body.
- Job releases with 15-minute × attempt-number exponential backoff.
- After 3 failed attempts the job fails permanently and lands in `failed_jobs` for manual retry.

**Bot loses posting privileges (banned / lost mod status):**
- All Reddit API calls will 403.
- Update jobs log and either swallow (edit) or retry (comment).
- The initial post job (`PostProjectToReddit`) will fail permanently; the UI will show the "posting in progress" indicator until the 5-minute polling timeout, then warn the user.

**User revokes Reddit OAuth link on Reddit's side:**
- The MixPitch `reddit_*` columns and Socialite `provider_token` become stale but not harmful. The trust badge continues to display (we're not calling Reddit to validate).
- Next "Sign in with Reddit" will re-authorize normally and refresh the token.

## Which workflows can be posted

| Workflow          | Can post? | Post-back on pitch accepted? | Post-back on completion? |
|-------------------|-----------|------------------------------|--------------------------|
| Standard          | ✅        | ✅ (via `approveInitialPitch`) | ✅                        |
| Direct Hire       | ✅        | N/A (no accept step)         | ✅                        |
| Contest           | ✅        | ✅ (winner selection, see above) | ✅                     |
| Client Management | ❌        | N/A                          | N/A                      |

**Contest note:** contests don't have an "accept a pitch" moment — they select a winner via `PitchWorkflowService::selectContestWinner()`, which triggers the winner-announcement post-back described in "Contest winner selected" above.
