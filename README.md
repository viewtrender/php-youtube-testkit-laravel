# php-youtube-testkit-laravel

Laravel integration for mocking Google YouTube API responses in tests.

[![Tests](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/viewtrender/php-youtube-testkit-laravel)](https://packagist.org/packages/viewtrender/php-youtube-testkit-laravel)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

**php-youtube-testkit** provides fake YouTube Data API responses for testing code that uses [`google/apiclient`](https://github.com/googleapis/google-api-php-client) without hitting real APIs. Queue fake responses, call your code as normal, then assert exactly which requests were made.

The project ships as two packages:

| Package | Use case |
|---|---|
| `viewtrender/php-youtube-testkit-core` | Framework-agnostic core — works with any PHP project |
| `viewtrender/php-youtube-testkit-laravel` | Laravel integration — auto-swaps the container binding |

## Installation

### Core (any PHP project)

```bash
composer require --dev viewtrender/php-youtube-testkit-core
```

### Laravel

```bash
composer require --dev viewtrender/php-youtube-testkit-laravel
```

The service provider is auto-discovered. To publish the config file:

```bash
php artisan vendor:publish --tag=youtube-testkit-config
```

### Requirements

- PHP 8.3+
- `google/apiclient` ^2.15

## Quick Start

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

// 1. Activate fakes and queue a response
YoutubeDataApi::fake([
    YoutubeVideo::list(),
]);

// 2. Use the YouTube service as normal
$youtube = YoutubeDataApi::youtube();
$response = $youtube->videos->listVideos('snippet,statistics', ['id' => 'dQw4w9WgXcQ']);

// 3. Assert the request was made
YoutubeDataApi::assertListedVideos();
YoutubeDataApi::assertSentCount(1);

// 4. Clean up
YoutubeDataApi::reset();
```

## Usage — Framework-Agnostic

### Setting up fakes

Call `YoutubeDataApi::fake()` with an array of responses. Each response is consumed in order as your code makes requests:

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;

YoutubeDataApi::fake([
    YoutubeVideo::list(),      // first request gets this
    YoutubeChannel::list(),    // second request gets this
]);
```

### Getting a YouTube service

You can get a pre-configured `Google\Service\YouTube` instance directly:

```php
$youtube = YoutubeDataApi::youtube();
```

Or create one from the fake Google Client:

```php
use Google\Service\YouTube;

$youtube = new YouTube(YoutubeDataApi::client());
```

### Resetting in tearDown

Always reset the fake state after each test:

```php
protected function tearDown(): void
{
    YoutubeDataApi::reset();
    parent::tearDown();
}
```

## Usage — Laravel

### Register the real YouTube binding

In your `AppServiceProvider`, register the real `YouTube` service for production use:

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

### Faking in tests

When `YoutubeDataApi::fake()` is called, the Laravel service provider automatically replaces the container's `YouTube::class` binding with a fake instance:

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

public function test_index_returns_videos(): void
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

    $response = $this->getJson('/api/videos?ids=dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertJsonPath('videos.0.title', 'Never Gonna Give You Up');

    YoutubeDataApi::assertListedVideos();
}
```

Controllers can type-hint `YouTube` as usual — the container resolves the fake automatically:

```php
use Google\Service\YouTube;

class VideoController extends Controller
{
    public function show(YouTube $youtube, string $id)
    {
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $id]);
        // ...
    }
}
```

## Factories

Four factories are available, each backed by realistic fixture data:

| Factory | List method | List with items | Single item | Empty |
|---|---|---|---|---|
| `YoutubeVideo` | `list()` | `listWithVideos()` | `video()` | `empty()` |
| `YoutubeChannel` | `list()` | `listWithChannels()` | `channel()` | `empty()` |
| `YoutubePlaylist` | `list()` | `listWithPlaylists()` | `playlist()` | `empty()` |
| `YoutubeSearchResult` | `list()` | `listWithResults()` | `searchResult()` | `empty()` |

### Default response

Returns the full fixture with realistic defaults:

```php
YoutubeVideo::list();
```

### Custom items

Pass an array of items — only specify the fields you care about. Unspecified fields use fixture defaults via deep merge:

```php
YoutubeVideo::listWithVideos([
    [
        'id' => 'abc123',
        'snippet' => ['title' => 'My Custom Title'],
        'statistics' => ['viewCount' => '999'],
    ],
    [
        'id' => 'def456',
        'snippet' => ['title' => 'Another Video'],
    ],
]);
```

### Single item builder

Build a single item array (useful for composing custom responses):

```php
$video = YoutubeVideo::video(['id' => 'abc123']);
```

### Empty response

Returns a valid API response with zero items:

```php
YoutubeVideo::empty();
```

## Error Responses

Simulate YouTube API errors:

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

Each method accepts an optional custom message:

```php
ErrorResponse::notFound('Video not found.');
ErrorResponse::quotaExceeded('Daily quota exhausted.');
```

## Assertions

### Request assertions

```php
// At least one request matched the callback
YoutubeDataApi::assertSent(function (RequestInterface $request): bool {
    return str_contains($request->getUri()->getPath(), '/youtube/v3/videos')
        && str_contains((string) $request->getUri(), 'dQw4w9WgXcQ');
});

// No request matched the callback
YoutubeDataApi::assertNotSent(function (RequestInterface $request): bool {
    return str_contains($request->getUri()->getPath(), '/youtube/v3/channels');
});

// No requests were sent at all
YoutubeDataApi::assertNothingSent();

// Exact number of requests
YoutubeDataApi::assertSentCount(2);
```

### Path shorthand assertions

```php
YoutubeDataApi::assertListedVideos();      // /youtube/v3/videos
YoutubeDataApi::assertSearched();          // /youtube/v3/search
YoutubeDataApi::assertListedChannels();    // /youtube/v3/channels
YoutubeDataApi::assertListedPlaylists();   // /youtube/v3/playlists
YoutubeDataApi::assertCalledPath('/youtube/v3/custom');
```

## Preventing Stray Requests

Throw an exception when a request is made but no fake response is queued:

```php
$fake = YoutubeDataApi::fake([
    YoutubeVideo::list(),
]);

$fake->preventStrayRequests();
```

In Laravel, enable it globally via config:

```php
// config/youtube-testkit.php
'prevent_stray_requests' => true,
```

When enabled, any unmatched request throws `Viewtrender\Youtube\Exceptions\StrayRequestException`.

## Configuration (Laravel)

After publishing the config (`php artisan vendor:publish --tag=youtube-testkit-config`):

```php
// config/youtube-testkit.php
return [
    // Path to custom fixture files (null = use package defaults)
    'fixtures_path' => null,

    // Throw StrayRequestException for unqueued requests
    'prevent_stray_requests' => false,
];
```

## Testing

### PHPUnit

```php
use Viewtrender\Youtube\YoutubeDataApi;

class MyTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_it_fetches_videos(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        // ... your test logic

        YoutubeDataApi::assertListedVideos();
    }
}
```

### Pest

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

afterEach(function () {
    YoutubeDataApi::reset();
});

it('fetches videos', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::list(),
    ]);

    // ... your test logic

    YoutubeDataApi::assertListedVideos();
});
```

## License

MIT
