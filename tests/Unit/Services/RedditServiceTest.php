<?php

use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.reddit_bot.client_id' => 'test-client-id',
        'services.reddit_bot.client_secret' => 'test-client-secret',
        'services.reddit_bot.username' => 'MixPitchBot',
        'services.reddit_bot.password' => 'test-password',
        'services.reddit_bot.user_agent' => 'MixPitch/1.0-test',
    ]);

    Cache::forget('reddit_access_token_'.md5('test-client-id'));
});

function fakeRedditRedirectResponse(string $postId = 'abc123'): array
{
    $url = "https://reddit.com/r/MixPitch/comments/{$postId}/test_post/";

    return [
        'success' => true,
        'jquery' => [
            [1, 10, 'attr', 'redirect'],
            [2, 11, 'call', [$url]],
        ],
    ];
}

it('fetches and caches the access token from Reddit', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ], 200),
    ]);

    $service = app(RedditService::class);

    expect($service->getAccessToken())->toBe('test-token');
    expect(Cache::get('reddit_access_token_'.md5('test-client-id')))->toBe('test-token');

    $service->getAccessToken();
    Http::assertSentCount(1);
});

it('throws when Reddit authentication fails', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['error' => 'invalid_grant'], 401),
    ]);

    app(RedditService::class)->getAccessToken();
})->throws(\Exception::class, 'Reddit authentication failed');

it('throws when Reddit returns no access token in the response', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response([], 200),
    ]);

    app(RedditService::class)->getAccessToken();
})->throws(\Exception::class, 'No access token received');

it('submits a standard project and parses the Reddit response', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/submit' => Http::response(fakeRedditRedirectResponse('xyz789'), 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'title' => 'My Standard Project',
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'genre' => 'Rock',
        'budget' => 500,
    ]);

    $result = app(RedditService::class)->submitProject($project);

    expect($result['json']['data']['id'])->toBe('xyz789');
    expect($result['json']['data']['url'])->toContain('/r/MixPitch/comments/xyz789/');
    expect($result['json']['data']['permalink'])->toContain('/r/MixPitch/comments/xyz789/');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/api/submit')) {
            return true;
        }

        $data = $request->data();

        return $data['sr'] === 'MixPitch'
            && $data['kind'] === 'self'
            && str_contains($data['title'], '🎛️ Project: My Standard Project')
            && str_contains($data['title'], '[Rock]')
            && str_contains($data['title'], '[$500')
            && str_contains($data['text'], 'READY TO SUBMIT YOUR PITCH');
    });
});

it('formats the title with the contest emoji and workflow type', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/submit' => Http::response(fakeRedditRedirectResponse(), 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()
        ->for($user)
        ->configureWorkflow(Project::WORKFLOW_TYPE_CONTEST)
        ->create([
            'title' => 'Best Beat Contest',
            'genre' => 'Hip Hop',
        ]);

    app(RedditService::class)->submitProject($project);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/api/submit')) {
            return true;
        }

        return str_contains($request->data()['title'], '🏆 Contest: Best Beat Contest')
            && str_contains($request->data()['title'], '[Hip Hop]');
    });
});

it('throws when Reddit submission returns a non-successful HTTP status', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/submit' => Http::response('Server error', 500),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'X']);

    app(RedditService::class)->submitProject($project);
})->throws(\Exception::class, 'Reddit submission failed');

it('throws when Reddit returns errors in the JSON response body', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/submit' => Http::response([
            'json' => [
                'errors' => [['SUBREDDIT_NOEXIST', 'that subreddit does not exist', 'sr']],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'X']);

    app(RedditService::class)->submitProject($project);
})->throws(\Exception::class, 'Reddit API errors');

it('edits a post via /api/editusertext with the correct thing_id', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/editusertext' => Http::response(['json' => ['errors' => []]], 200),
    ]);

    app(RedditService::class)->editPost('t3_abc123', 'New body content');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/api/editusertext')) {
            return true;
        }
        $data = $request->data();

        return $data['thing_id'] === 't3_abc123'
            && $data['text'] === 'New body content'
            && $data['api_type'] === 'json'
            && str_contains($request->header('Authorization')[0], 'test-token');
    });
});

it('throws when editPost gets a non-successful HTTP response', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/editusertext' => Http::response('forbidden', 403),
    ]);

    app(RedditService::class)->editPost('t3_abc', 'x');
})->throws(\Exception::class, 'Reddit editusertext failed');

it('throws when editPost gets errors in the JSON response body', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/editusertext' => Http::response([
            'json' => ['errors' => [['NOT_AUTHOR', 'not your submission', 'thing_id']]],
        ], 200),
    ]);

    app(RedditService::class)->editPost('t3_abc', 'x');
})->throws(\Exception::class, 'Reddit editusertext errors');

it('posts a top-level comment via /api/comment', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/comment' => Http::response([
            'json' => ['errors' => [], 'data' => ['things' => [['data' => ['id' => 'c1', 'permalink' => '/r/x/comments/y/z/']]]]],
        ], 200),
    ]);

    $result = app(RedditService::class)->postComment('t3_parent', 'Hello world');

    expect($result['json']['errors'])->toBe([]);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/api/comment')) {
            return true;
        }
        $data = $request->data();

        return $data['thing_id'] === 't3_parent'
            && $data['text'] === 'Hello world'
            && $data['api_type'] === 'json';
    });
});

it('throws when postComment gets errors in the JSON response body', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/comment' => Http::response([
            'json' => ['errors' => [['RATELIMIT', 'try later', null]]],
        ], 200),
    ]);

    app(RedditService::class)->postComment('t3_x', 'anything');
})->throws(\Exception::class, 'Reddit comment errors');

it('deletes a post via /api/del', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/del' => Http::response([], 200),
    ]);

    app(RedditService::class)->deletePost('t3_delete_me');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/api/del')) {
            return true;
        }

        return $request->data()['id'] === 't3_delete_me';
    });
});

it('throws when deletePost gets a non-successful HTTP response', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/del' => Http::response('forbidden', 403),
    ]);

    app(RedditService::class)->deletePost('t3_x');
})->throws(\Exception::class, 'Reddit del failed');

it('builds a post body without throwing when the raw deadline is an ISO-8601 string with microseconds', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'title' => 'Microsecond Deadline Project',
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
    ]);

    // Simulate a raw DB value shaped like MySQL/driver output that includes
    // microseconds / ISO-8601 formatting instead of plain 'Y-m-d H:i:s'.
    $project->setRawAttributes(array_merge($project->getAttributes(), [
        'deadline' => '2026-09-01T12:34:56.123456Z',
    ]), true);

    $body = app(RedditService::class)->buildPostBody($project);

    expect($body)->toContain('Deadline:');
});

it('returns null instead of throwing when parseDeadlineForOwner is given an unparseable string', function () {
    $user = User::factory()->create();

    $service = app(RedditService::class);
    $method = new \ReflectionMethod($service, 'parseDeadlineForOwner');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'not-a-real-date', $user);

    expect($result)->toBeNull();
});

it('scopes the access token cache key to the configured client id', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
    ]);

    app(RedditService::class)->getAccessToken();

    expect(Cache::has('reddit_access_token_'.md5('test-client-id')))->toBeTrue();
    expect(Cache::has('reddit_access_token'))->toBeFalse();
});

it('returns null post id when the jQuery response contains no redirect URL', function () {
    Http::fake([
        'reddit.com/api/v1/access_token' => Http::response(['access_token' => 'test-token'], 200),
        'oauth.reddit.com/api/submit' => Http::response([
            'success' => true,
            'jquery' => [[1, 2, 'attr', 'something']],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'X']);

    $result = app(RedditService::class)->submitProject($project);

    expect($result['json']['data']['id'])->toBeNull();
    expect($result['json']['data']['url'])->toBeNull();
});
