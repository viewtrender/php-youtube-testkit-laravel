---
name: youtube-data-api
description: Mock YouTube Data API (videos, channels, playlists, search, comments, subscriptions, captions) in Laravel tests. Use when writing tests that fetch video metadata, channel stats, playlist items, search results, or any YouTube Data API resource.
---

# YouTube Data API Testing

Mock the YouTube Data API using `YoutubeDataApi::fake()`.

## Quick Start

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

it('fetches video details', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up', 'channelTitle' => 'Rick Astley'],
                'statistics' => ['viewCount' => '1500000000', 'likeCount' => '15000000'],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ');
    $response->assertOk();
    
    YoutubeDataApi::assertListedVideos();
});
```

## Factories

| Factory | Common Methods |
|---------|----------------|
| `YoutubeVideo` | `list()`, `listWithVideos(array)`, `notFound()` |
| `YoutubeChannel` | `list()`, `listWithChannels(array)`, `notFound()` |
| `YoutubePlaylist` | `list()`, `listWithPlaylists(array)` |
| `YoutubePlaylistItems` | `list()`, `listWithItems(array)` |
| `YoutubeSearchResult` | `list()`, `listWithResults(array)` |
| `YoutubeCommentThreads` | `list()`, `listWithThreads(array)` |
| `YoutubeSubscriptions` | `list()`, `listWithSubscriptions(array)` |
| `YoutubeActivities` | `list()`, `listWithActivities(array)` |
| `YoutubeCaptions` | `list()`, `listWithCaptions(array)` |

## Channel Testing

```php
use Viewtrender\Youtube\Factories\YoutubeChannel;

YoutubeDataApi::fake([
    YoutubeChannel::listWithChannels([
        [
            'id' => 'UC123',
            'snippet' => ['title' => 'My Channel'],
            'statistics' => ['subscriberCount' => '1000000', 'videoCount' => '500'],
        ],
    ]),
]);
```

## Search Testing

```php
use Viewtrender\Youtube\Factories\YoutubeSearchResult;

YoutubeDataApi::fake([
    YoutubeSearchResult::listWithResults([
        ['id' => ['videoId' => 'abc123'], 'snippet' => ['title' => 'Result 1']],
        ['id' => ['videoId' => 'def456'], 'snippet' => ['title' => 'Result 2']],
    ]),
]);
```

## Playlist Items

```php
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;

YoutubeDataApi::fake([
    YoutubePlaylistItems::listWithItems([
        ['snippet' => ['resourceId' => ['videoId' => 'vid1'], 'title' => 'First Video']],
        ['snippet' => ['resourceId' => ['videoId' => 'vid2'], 'title' => 'Second Video']],
    ]),
]);
```

## Error Responses

```php
use Viewtrender\Youtube\Responses\ErrorResponse;

// 404 Not Found
YoutubeDataApi::fake([ErrorResponse::notFound()]);

// 403 Quota Exceeded
YoutubeDataApi::fake([ErrorResponse::quotaExceeded()]);

// 401 Unauthorized
YoutubeDataApi::fake([ErrorResponse::unauthorized()]);
```

## Assertions

```php
YoutubeDataApi::assertSent(fn ($request) => str_contains($request->getUri(), 'videos'));
YoutubeDataApi::assertNotSent(fn ($request) => str_contains($request->getUri(), 'channels'));
YoutubeDataApi::assertNothingSent();
YoutubeDataApi::assertSentCount(2);
YoutubeDataApi::assertListedVideos();
YoutubeDataApi::assertListedChannels();
YoutubeDataApi::assertListedPlaylists();
YoutubeDataApi::assertSearched();
```

## Prevent Stray Requests

```php
$fake = YoutubeDataApi::fake([YoutubeVideo::list()]);
$fake->preventStrayRequests();

// First request succeeds, second throws StrayRequestException
```

## Teardown

Always reset in tearDown or afterEach:

```php
protected function tearDown(): void
{
    YoutubeDataApi::reset();
    parent::tearDown();
}
```
