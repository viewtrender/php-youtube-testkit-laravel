# YouTube Data API Testkit

Mock YouTube Data API clients and factories for testing Laravel applications.

## Setup

Register the YouTube service in your `AppServiceProvider`:

```php
use Google\Client as GoogleClient;
use Google\Service\YouTube;

public function register(): void
{
    $this->app->singleton(YouTube::class, function () {
        $client = new GoogleClient();
        $client->setApplicationName(config('services.youtube.application_name'));
        $client->setDeveloperKey(config('services.youtube.api_key'));
        return new YouTube($client);
    });
}
```

## Basic Testing

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;

it('fetches video details', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => [
                    'title' => 'Never Gonna Give You Up',
                    'channelTitle' => 'Rick Astley',
                ],
                'statistics' => [
                    'viewCount' => '1500000000',
                    'likeCount' => '15000000',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

    $response->assertOk()
        ->assertJsonPath('snippet.title', 'Never Gonna Give You Up');
    
    YoutubeDataApi::assertListedVideos();
});
```

## Available Factories

| Factory | Methods |
|---------|---------|
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
it('fetches channel statistics', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::listWithChannels([
            [
                'id' => 'UC123',
                'snippet' => ['title' => 'My Channel'],
                'statistics' => [
                    'subscriberCount' => '1000000',
                    'videoCount' => '500',
                    'viewCount' => '100000000',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channels/UC123');

    $response->assertOk();
    YoutubeDataApi::assertListedChannels();
});
```

## Search Testing

```php
it('searches for videos', function () {
    YoutubeDataApi::fake([
        YoutubeSearchResult::listWithResults([
            ['id' => ['videoId' => 'abc123'], 'snippet' => ['title' => 'Result 1']],
            ['id' => ['videoId' => 'def456'], 'snippet' => ['title' => 'Result 2']],
        ]),
    ]);

    $response = $this->getJson('/api/search?q=laravel');

    $response->assertOk()->assertJsonCount(2, 'items');
    YoutubeDataApi::assertSearched();
});
```

## Error Handling

```php
use Viewtrender\Youtube\Responses\ErrorResponse;

it('handles video not found', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::notFound(),
    ]);

    $response = $this->getJson('/api/videos/invalid');
    $response->assertNotFound();
});

it('handles quota exceeded', function () {
    YoutubeDataApi::fake([
        ErrorResponse::quotaExceeded('Daily quota exhausted'),
    ]);

    $response = $this->getJson('/api/videos/any');
    $response->assertStatus(500);
});
```

## Assertions

- `YoutubeDataApi::assertSent(callable $callback)` - Assert a request was sent matching callback
- `YoutubeDataApi::assertNotSent(callable $callback)` - Assert no request matched callback
- `YoutubeDataApi::assertNothingSent()` - Assert no requests were made
- `YoutubeDataApi::assertSentCount(int $count)` - Assert exact number of requests
- `YoutubeDataApi::assertListedVideos()` - Assert videos.list was called
- `YoutubeDataApi::assertListedChannels()` - Assert channels.list was called
- `YoutubeDataApi::assertListedPlaylists()` - Assert playlists.list was called
- `YoutubeDataApi::assertSearched()` - Assert search.list was called
