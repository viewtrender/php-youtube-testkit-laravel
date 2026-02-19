# php-youtube-testkit-laravel

Laravel integration for mocking Google YouTube API responses in tests.

[![Tests](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/viewtrender/php-youtube-testkit-laravel)](https://packagist.org/packages/viewtrender/php-youtube-testkit-laravel)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

This package provides a Laravel service provider, facade, and automatic container swap for [`viewtrender/php-youtube-testkit-core`](https://github.com/viewtrender/php-youtube-testkit-core). When you call `YoutubeDataApi::fake()` in a test, the service provider replaces the `Google\Service\YouTube` container binding with a fake instance — controllers that type-hint `YouTube` receive the fake automatically.

For the full API reference (factories, assertions, error responses), see the [core package README](https://github.com/viewtrender/php-youtube-testkit-core).

## Installation

```bash
composer require --dev viewtrender/php-youtube-testkit-laravel
```

The service provider is auto-discovered. To publish the config file:

```bash
php artisan vendor:publish --tag=youtube-testkit-config
```

## Requirements

- PHP 8.3+
- Laravel 10, 11, or 12
- `google/apiclient` ^2.15

## Quick Start

```php
use Orchestra\Testbench\TestCase;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\YoutubeDataApi;

class VideoControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_index_returns_videos(): void
    {
        // 1. Activate fakes and queue a response
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                [
                    'id' => 'dQw4w9WgXcQ',
                    'snippet' => ['title' => 'Never Gonna Give You Up'],
                    'statistics' => ['viewCount' => '1500000000'],
                ],
            ]),
        ]);

        // 2. Hit a route that injects YouTube via the container
        $response = $this->getJson('/api/videos?ids=dQw4w9WgXcQ');

        // 3. Assert on the HTTP response
        $response->assertOk();
        $response->assertJsonPath('videos.0.title', 'Never Gonna Give You Up');

        // 4. Assert the YouTube API call was made
        YoutubeDataApi::assertListedVideos();
    }
}
```

## How It Works

1. `YoutubeDataApiServiceProvider::register()` calls `YoutubeDataApi::registerContainerSwap()` with a closure.
2. When test code calls `YoutubeDataApi::fake([...])`, the core package invokes that closure.
3. The closure binds the fake `YouTube` instance into the Laravel container via `$this->app->instance(YouTube::class, ...)`.
4. Controllers that inject `YouTube` via the container now receive the fake instance.

## Setup

Register the real `YouTube` service in your `AppServiceProvider` so your application has a binding to swap in tests:

```php
use Google\Client as GoogleClient;
use Google\Service\YouTube;

public function register(): void
{
    $this->app->singleton(YouTube::class, function () {
        $client = new GoogleClient();
        $client->setApplicationName(config('services.youtube.application_name', 'My App'));
        $client->setDeveloperKey(config('services.youtube.api_key'));

        return new YouTube($client);
    });
}
```

Controllers can then type-hint `YouTube` as usual:

```php
use Google\Service\YouTube;

class VideoController extends Controller
{
    public function show(YouTube $youtube, string $id)
    {
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $id]);
        $video = $response->getItems()[0] ?? null;

        return response()->json([
            'id' => $video?->getId(),
            'title' => $video?->getSnippet()?->getTitle(),
            'views' => $video?->getStatistics()?->getViewCount(),
        ]);
    }
}
```

## Facade

Import the Laravel facade instead of the base class when you prefer facade syntax:

```php
use Viewtrender\Youtube\Laravel\Facades\YoutubeDataApi;
```

Available methods:

| Method | Description |
|---|---|
| `YoutubeDataApi::fake(array $responses)` | Activate fakes and queue responses |
| `YoutubeDataApi::youtube()` | Get the fake `YouTube` service instance |
| `YoutubeDataApi::client()` | Get the fake `Google\Client` instance |
| `YoutubeDataApi::reset()` | Clear all fake state |
| `YoutubeDataApi::assertSent(callable $callback)` | At least one request matched the callback |
| `YoutubeDataApi::assertNotSent(callable $callback)` | No request matched the callback |
| `YoutubeDataApi::assertNothingSent()` | No requests were sent |
| `YoutubeDataApi::assertSentCount(int $count)` | Exact number of requests were sent |
| `YoutubeDataApi::assertListedVideos()` | A videos list request was made |
| `YoutubeDataApi::assertListedChannels()` | A channels list request was made |
| `YoutubeDataApi::assertListedPlaylists()` | A playlists list request was made |
| `YoutubeDataApi::assertSearched()` | A search request was made |

## Configuration

After publishing the config:

```bash
php artisan vendor:publish --tag=youtube-testkit-config
```

```php
// config/youtube-testkit.php
return [
    // Path to custom fixture files (null = use package defaults)
    'fixtures_path' => null,

    // Throw StrayRequestException for unqueued requests
    'prevent_stray_requests' => false,
];
```

## Preventing Stray Requests

Enable globally via config to throw an exception when a request is made but no fake response is queued:

```php
// config/youtube-testkit.php
'prevent_stray_requests' => true,
```

Or enable per-test:

```php
$fake = YoutubeDataApi::fake([
    YoutubeVideo::list(),
]);

$fake->preventStrayRequests();
```

When enabled, any unmatched request throws `Viewtrender\Youtube\Exceptions\StrayRequestException`.

## Factories

The core package provides 20 factories for YouTube Data API v3 endpoints:

| Factory | Use Case |
|---------|----------|
| `YoutubeVideo` | Videos list/details |
| `YoutubeChannel` | Channels list/details |
| `YoutubePlaylist` | Playlists list/details |
| `YoutubePlaylistItems` | Playlist items |
| `YoutubeSearchResult` | Search results |
| `YoutubeSubscriptions` | Channel subscriptions |
| `YoutubeComments` | Video/channel comments |
| `YoutubeCommentThreads` | Comment threads |
| `YoutubeActivities` | Channel activity feed |
| `YoutubeCaptions` | Video captions/subtitles |
| `YoutubeChannelSections` | Channel page sections |
| `YoutubeMembers` | Channel members (OAuth) |
| `YoutubeMembershipsLevels` | Membership tiers (OAuth) |
| `YoutubeI18nLanguages` | Supported languages |
| `YoutubeI18nRegions` | Supported regions |
| `YoutubeVideoCategories` | Video categories |
| `YoutubeVideoAbuseReportReasons` | Abuse report reasons |
| `YoutubeGuideCategories` | Guide categories (deprecated) |
| `YoutubeThumbnails` | Thumbnail upload (write-only) |
| `YoutubeWatermarks` | Channel watermarks (write-only) |

Each factory provides `list()`, `listWith{Resource}s()`, `empty()`, and single-item methods.

## Error Responses

Simulate API errors with `ErrorResponse`:

```php
use Viewtrender\Youtube\Responses\ErrorResponse;

YoutubeDataApi::fake([
    ErrorResponse::notFound(),
    ErrorResponse::forbidden(),
    ErrorResponse::unauthorized(),
    ErrorResponse::quotaExceeded(),
    ErrorResponse::badRequest(),
]);
```

See the [core package README](https://github.com/viewtrender/php-youtube-testkit-core) for full documentation.

## Testing

Always reset fake state after each test to prevent leaking between tests.

### Pest

```php
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\YoutubeDataApi;

afterEach(function () {
    YoutubeDataApi::reset();
});
```

#### API (JSON response)

```php
it('returns videos as JSON', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up'],
                'statistics' => ['viewCount' => '1500000000'],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertJsonPath('title', 'Never Gonna Give You Up');
    YoutubeDataApi::assertListedVideos();
});
```

#### Blade view

```php
it('displays videos in a blade view', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up'],
                'statistics' => ['viewCount' => '1500000000'],
            ],
        ]),
    ]);

    $response = $this->get('/videos/dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertViewHas('videos');
    $response->assertSee('Never Gonna Give You Up');
    YoutubeDataApi::assertListedVideos();
});
```

#### Inertia.js

```php
use Inertia\Testing\AssertableInertia as Assert;

it('passes video data to an Inertia page', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up'],
                'statistics' => ['viewCount' => '1500000000'],
            ],
        ]),
    ]);

    $response = $this->get('/videos/dQw4w9WgXcQ');

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Videos/Show')
            ->has('video')
            ->where('video.title', 'Never Gonna Give You Up')
    );
    YoutubeDataApi::assertListedVideos();
});
```

#### Livewire

```php
use Livewire\Livewire;

it('searches for videos in a Livewire component', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up'],
                'statistics' => ['viewCount' => '1500000000'],
            ],
        ]),
    ]);

    Livewire::test(VideoSearch::class)
        ->set('query', 'Never Gonna Give You Up')
        ->call('search')
        ->assertSee('Never Gonna Give You Up');

    YoutubeDataApi::assertListedVideos();
});
```

### PHPUnit

```php
use Orchestra\Testbench\TestCase;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\YoutubeDataApi;

class VideoControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_show_returns_video(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                [
                    'id' => 'dQw4w9WgXcQ',
                    'snippet' => ['title' => 'Never Gonna Give You Up'],
                    'statistics' => ['viewCount' => '1500000000'],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

        $response->assertOk();
        $response->assertJsonPath('title', 'Never Gonna Give You Up');
        YoutubeDataApi::assertListedVideos();
    }
}
```

> The same Blade view, Inertia.js, and Livewire patterns shown in the Pest section above apply in PHPUnit — just wrap them in test methods within your test class.

## License

MIT
