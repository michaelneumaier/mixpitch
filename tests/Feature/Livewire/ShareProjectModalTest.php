<?php

use App\Livewire\Project\ShareProjectModal;
use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();

    // The Reddit share section only renders when the r/MixPitch bot account
    // is configured; set it explicitly so these tests don't depend on env.
    config([
        'services.reddit_bot.client_id' => 'test-bot-client-id',
        'services.reddit_bot.client_secret' => 'test-bot-client-secret',
        'services.reddit_bot.username' => 'MixPitchBot',
        'services.reddit_bot.password' => 'test-bot-password',
    ]);
});

it('renders the reddit share section for a standard project', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Standard Share Test',
            'description' => 'Description for standard share test',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSet('showsRedditSection', true)
        ->assertSee('Post to r/MixPitch')
        ->assertSeeHtml('data-testid="share-sink-reddit"');
});

it('hides the reddit share section when the bot account is not configured', function () {
    config(['services.reddit_bot.client_id' => null]);

    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Unconfigured Bot Test',
            'description' => 'Description for unconfigured bot test',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSet('showsRedditSection', false)
        ->assertDontSeeHtml('data-testid="share-sink-reddit"');
});

it('hides the reddit share section for a client management project', function () {
    $client = \App\Models\Client::factory()->for($this->owner, 'producer')->create();

    $project = Project::factory()
        ->for($this->owner)
        ->configureWorkflow(Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
        ->create([
            'title' => 'Client Share Test',
            'description' => 'Client project description',
            'client_id' => $client->id,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSet('showsRedditSection', false)
        ->assertDontSeeHtml('data-testid="share-sink-reddit"');
});

it('exposes the Reddit post body preview via RedditService::buildPostBody', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Preview Body Test',
            'description' => 'This unique preview description should appear in the modal preview',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    $expectedBody = app(RedditService::class)->buildPostBody($project);

    $component = Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project]);

    expect($component->instance()->redditPreviewBody)->toBe($expectedBody);

    $component
        ->assertSee('Preview post')
        ->assertSee('This unique preview description should appear in the modal preview')
        ->assertSeeHtml('data-testid="reddit-preview-body"');
});

it('shows the Connect-your-Reddit-account nudge when the owner has not linked reddit', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Nudge Test',
            'description' => 'Nudge description',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSet('ownerHasLinkedReddit', false)
        ->assertSeeHtml('data-testid="reddit-connect-nudge"')
        ->assertSee('Connect your Reddit account')
        ->assertSeeHtml(route('account.reddit.connect'));
});

it('hides the connect nudge when the owner has linked reddit', function () {
    $this->owner->update([
        'reddit_username' => 'aliceproducer',
        'reddit_user_id' => 't2_abc123',
    ]);

    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Linked Nudge Test',
            'description' => 'Linked description',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSet('ownerHasLinkedReddit', true)
        ->assertDontSeeHtml('data-testid="reddit-connect-nudge"')
        ->assertSee('u/aliceproducer');
});

it('shows the posted state with a permalink when project has been posted', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Already Posted Test',
            'description' => 'Already posted description',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'reddit_post_id' => 'abc123',
            'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/abc123/already-posted-test/',
            'reddit_posted_at' => now()->subMinutes(5),
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSeeHtml('data-testid="reddit-state-posted"')
        ->assertSee('Posted to r/MixPitch')
        ->assertSee('View on Reddit')
        ->assertSee('Remove from Reddit')
        ->assertSeeHtml('https://reddit.com/r/MixPitch/comments/abc123/already-posted-test/');
});

it('shows an unpublished warning when the project is not published', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->create([
            'title' => 'Unpublished Test',
            'description' => 'Unpublished description',
            'is_published' => false,
            'status' => Project::STATUS_UNPUBLISHED,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSeeHtml('data-testid="reddit-state-unpublished"')
        ->assertSee('Publish your project before posting');
});

it('exposes the public project URL for copy-to-clipboard', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'Public URL Test',
            'description' => 'Public URL description',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

    Livewire::actingAs($this->owner)
        ->test(ShareProjectModal::class, ['project' => $project])
        ->assertSee(route('projects.show', $project))
        ->assertSeeHtml('data-testid="share-sink-link"');
});
