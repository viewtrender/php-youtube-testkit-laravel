## YouTube Testkit

This package provides mock YouTube API clients and factories for testing YouTube Data API, Analytics API, and Reporting API integrations in Laravel applications.

### Features

- **Mock YouTube Data API**: Test video uploads, channel management, playlist operations, search functionality, and comments
- **Mock YouTube Reporting API**: Test bulk analytics data exports, report generation, and scheduled jobs
- **Service Provider Integration**: Automatic container swapping in tests for seamless dependency injection
- **Laravel Facade**: Clean API through `YoutubeDataApi::fake()` with assertion methods
- **Rich Factory System**: Pre-built factories for videos, channels, playlists, search results, and reporting data

### Basic Setup

Register the YouTube service in your `AppServiceProvider`:

@verbatim
<code-snippet name="Service Registration" lang="php">
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
</code-snippet>
@endverbatim

Controllers can then type-hint the YouTube service:

@verbatim
<code-snippet name="Controller Example" lang="php">
use Google\Service\YouTube;

class VideoController extends Controller
{
    public function show(YouTube $youtube, string $id)
    {
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $id]);
        return response()->json($response->getItems()[0] ?? null);
    }
}
</code-snippet>
@endverbatim

### Testing with Factories

Import the factories you need:

@verbatim
<code-snippet name="Factory Imports" lang="php">
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\YoutubeDataApi;
</code-snippet>
@endverbatim

### Video Testing Example

@verbatim
<code-snippet name="Video Test" lang="php">
it('fetches video details', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => [
                    'title' => 'Never Gonna Give You Up',
                    'description' => 'Official music video',
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
        ->assertJsonPath('snippet.title', 'Never Gonna Give You Up')
        ->assertJsonPath('statistics.viewCount', '1500000000');
    
    YoutubeDataApi::assertListedVideos();
});
</code-snippet>
@endverbatim

### Reporting API Testing

@verbatim
<code-snippet name="Reporting Test" lang="php">
it('creates reporting job', function () {
    YoutubeReportingApi::fake([
        ReportingJob::create([
            'reportTypeId' => 'channel_basic_a2',
            'name' => 'My Channel Report',
        ]),
    ]);

    $response = $this->postJson('/api/reporting/jobs', [
        'reportTypeId' => 'channel_basic_a2',
        'name' => 'My Channel Report',
    ]);

    $response->assertOk()
        ->assertJsonPath('reportTypeId', 'channel_basic_a2');
});
</code-snippet>
@endverbatim

### Error Response Testing

@verbatim
<code-snippet name="Error Handling" lang="php">
use Viewtrender\Youtube\Responses\ErrorResponse;

it('handles API quota exceeded', function () {
    YoutubeDataApi::fake([
        ErrorResponse::quotaExceeded('Daily quota exhausted'),
    ]);

    $response = $this->getJson('/api/videos/any');

    $response->assertStatus(500);
});
</code-snippet>
@endverbatim

### Available Assertions

- `YoutubeDataApi::assertSent(callable $callback)`
- `YoutubeDataApi::assertNotSent(callable $callback)` 
- `YoutubeDataApi::assertNothingSent()`
- `YoutubeDataApi::assertSentCount(int $count)`
- `YoutubeDataApi::assertListedVideos()`
- `YoutubeDataApi::assertSearched()`
- `YoutubeDataApi::assertListedChannels()`
- `YoutubeDataApi::assertListedPlaylists()`

### Configuration

Publish and configure the testkit settings:

@verbatim
<code-snippet name="Configuration" lang="php">
// config/youtube-testkit.php
return [
    // Custom fixture path (null = package defaults)
    'fixtures_path' => null,

    // Throw exception on unqueued requests
    'prevent_stray_requests' => false,
];
</code-snippet>
@endverbatim