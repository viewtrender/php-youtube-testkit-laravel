---
name: youtube-data-api
description: Mock YouTube Data API (videos, channels, playlists, search, comments, subscriptions, captions) in Laravel tests. Use when writing tests that fetch video metadata, channel stats, playlist items, search results, or any YouTube Data API resource. Includes pagination support for multi-page responses.
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

| Factory | List Method | Item Constructor | Paginated |
|---------|------------|-----------------|-----------|
| `YoutubeVideo` | `listWithVideos(array)` | `video(array)` | ✅ |
| `YoutubeChannel` | `listWithChannels(array)` | `channel(array)` | ❌ |
| `YoutubePlaylist` | `listWithPlaylists(array)` | `playlist(array)` | ✅ |
| `YoutubePlaylistItems` | `listWithPlaylistItems(array)` | `playlistItem(array)` | ✅ |
| `YoutubeSearchResult` | `listWithResults(array)` | `searchResult(array)` | ✅ |
| `YoutubeComments` | `listWithComments(array)` | `comment(array)` | ✅ |
| `YoutubeCommentThreads` | `listWithCommentThreads(array)` | `commentThread(array)` | ✅ |
| `YoutubeSubscriptions` | `listWithSubscriptions(array)` | `subscription(array)` | ✅ |
| `YoutubeActivities` | `listWithActivities(array)` | `activity(array)` | ✅ |
| `YoutubeMembers` | `listWithMembers(array)` | `member(array)` | ✅ |
| `YoutubeCaptions` | `listWithCaptions(array)` | `caption(array)` | ❌ |

All factories also have `list()`, `empty()`, and accept override arrays merged with fixture defaults.

## Pagination

Factories marked ✅ above support multi-page responses via `paginated()` and `pages()`. Both return `array<FakeResponse>` — spread them into `fake([])`.

### `paginated()` — auto-generated items

```php
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;

it('syncs all playlist items across pages', function () {
    YoutubeDataApi::fake([
        ...YoutubePlaylistItems::paginated(pages: 3, perPage: 5),
    ]);

    // Service makes 3 requests. First two have nextPageToken, last does not.
    dispatch(new SyncVideoLibraryJob($channel));

    YoutubeDataApi::assertSentCount(3);
});
```

### `pages()` — explicit items per page

```php
use Viewtrender\Youtube\Factories\YoutubeSubscriptions;

YoutubeDataApi::fake([
    ...YoutubeSubscriptions::pages([
        // Page 1 — has nextPageToken
        [
            YoutubeSubscriptions::subscription(['snippet' => ['title' => 'Channel A']]),
            YoutubeSubscriptions::subscription(['snippet' => ['title' => 'Channel B']]),
        ],
        // Page 2 — last page, no nextPageToken
        [
            YoutubeSubscriptions::subscription(['snippet' => ['title' => 'Channel C']]),
        ],
    ]),
]);
```

Raw override arrays also work with `pages()`:

```php
YoutubeDataApi::fake([
    ...YoutubeComments::pages([
        [
            ['snippet' => ['textDisplay' => 'First comment']],
            ['snippet' => ['textDisplay' => 'Second comment']],
        ],
        [
            ['snippet' => ['textDisplay' => 'Third comment']],
        ],
    ]),
]);
```

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
    YoutubePlaylistItems::listWithPlaylistItems([
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
