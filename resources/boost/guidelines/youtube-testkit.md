# YouTube TestKit

Mock YouTube Data, Analytics, and Reporting APIs for testing Laravel applications.

---

## YouTube Data API

Mock YouTube Data API clients and factories for testing.

### Setup

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

### Basic Testing

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

### Available Factories

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

### Channel Testing

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

### Search Testing

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

### Error Handling

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

### Data API Assertions

- `YoutubeDataApi::assertSent(callable $callback)` - Assert a request was sent matching callback
- `YoutubeDataApi::assertNotSent(callable $callback)` - Assert no request matched callback
- `YoutubeDataApi::assertNothingSent()` - Assert no requests were made
- `YoutubeDataApi::assertSentCount(int $count)` - Assert exact number of requests
- `YoutubeDataApi::assertListedVideos()` - Assert videos.list was called
- `YoutubeDataApi::assertListedChannels()` - Assert channels.list was called
- `YoutubeDataApi::assertListedPlaylists()` - Assert playlists.list was called
- `YoutubeDataApi::assertSearched()` - Assert search.list was called

---

## YouTube Analytics API

Mock YouTube Analytics API for testing on-demand analytics queries.

Use for: Dashboard widgets, on-demand reports with custom date ranges, real-time metrics display.

### Setup

```php
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
```

### Basic Query Testing

```php
it('fetches channel overview metrics', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::channelOverview([
            'views' => 500000,
            'estimatedMinutesWatched' => 1500000,
            'averageViewDuration' => 180,
            'subscribersGained' => 1000,
            'subscribersLost' => 50,
        ]),
    ]);

    $response = $this->getJson('/api/analytics/overview');

    $response->assertOk()
        ->assertJsonPath('rows.0.0', 500000);
});
```

### Daily Metrics

```php
it('fetches daily metrics', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::dailyMetrics([
            ['day' => '2024-01-01', 'views' => 1000, 'estimatedMinutesWatched' => 3000],
            ['day' => '2024-01-02', 'views' => 1200, 'estimatedMinutesWatched' => 3600],
        ]),
    ]);

    $response = $this->getJson('/api/analytics/daily?start=2024-01-01&end=2024-01-02');

    $response->assertOk()
        ->assertJsonCount(2, 'rows');
});
```

### Top Videos

```php
it('fetches top videos by views', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::topVideos([
            ['video' => 'dQw4w9WgXcQ', 'views' => 180000, 'estimatedMinutesWatched' => 540000],
            ['video' => 'jNQXAC9IVRw', 'views' => 150000, 'estimatedMinutesWatched' => 450000],
        ]),
    ]);

    $response = $this->getJson('/api/analytics/top-videos');

    $response->assertOk();
});
```

### Traffic Sources

```php
it('fetches traffic source breakdown', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::trafficSources([
            ['source' => 'RELATED_VIDEO', 'views' => 500000],
            ['source' => 'YT_SEARCH', 'views' => 300000],
            ['source' => 'EXT_URL', 'views' => 100000],
        ]),
    ]);

    $response = $this->getJson('/api/analytics/traffic-sources');

    $response->assertOk();
});
```

### Demographics

```php
it('fetches audience demographics', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::demographics([
            ['ageGroup' => 'age18-24', 'gender' => 'male', 'viewerPercentage' => 25.5],
            ['ageGroup' => 'age25-34', 'gender' => 'male', 'viewerPercentage' => 30.2],
            ['ageGroup' => 'age18-24', 'gender' => 'female', 'viewerPercentage' => 15.3],
        ]),
    ]);

    $response = $this->getJson('/api/analytics/demographics');

    $response->assertOk();
});
```

### Geography

```php
it('fetches geographic distribution', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::geography([
            ['country' => 'US', 'views' => 300000],
            ['country' => 'GB', 'views' => 150000],
            ['country' => 'CA', 'views' => 100000],
        ]),
    ]);

    $response = $this->getJson('/api/analytics/geography');

    $response->assertOk();
});
```

### Content Types

```php
it('fetches metrics by content type', function () {
    YoutubeAnalyticsApi::fake([
        AnalyticsQueryResponse::videoTypes([
            ['video' => 'abc123', 'creatorContentType' => 'VIDEO_ON_DEMAND', 'views' => 180000],
            ['video' => 'def456', 'creatorContentType' => 'SHORTS', 'views' => 120000],
            ['video' => 'ghi789', 'creatorContentType' => 'LIVE_STREAM', 'views' => 50000],
        ]),
    ]);

    $response = $this->getJson('/api/analytics/content-types');

    $response->assertOk();
});
```

### Available Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `views` | INTEGER | Total video views |
| `estimatedMinutesWatched` | INTEGER | Total watch time in minutes |
| `averageViewDuration` | INTEGER | Average view duration in seconds |
| `subscribersGained` | INTEGER | New subscribers |
| `subscribersLost` | INTEGER | Lost subscribers |
| `likes` | INTEGER | Total likes |
| `shares` | INTEGER | Total shares |
| `comments` | INTEGER | Total comments |
| `viewerPercentage` | FLOAT | Percentage of viewers (demographics) |

### Available Dimensions

| Dimension | Values |
|-----------|--------|
| `day` | Date string `YYYY-MM-DD` |
| `video` | Video ID |
| `country` | ISO 3166-1 alpha-2 code |
| `ageGroup` | `age13-17`, `age18-24`, `age25-34`, `age35-44`, `age45-54`, `age55-64`, `age65-` |
| `gender` | `male`, `female`, `user_specified` |
| `deviceType` | `DESKTOP`, `MOBILE`, `TABLET`, `TV`, `GAME_CONSOLE` |
| `insightTrafficSourceType` | `RELATED_VIDEO`, `YT_SEARCH`, `EXT_URL`, `NOTIFICATION`, `PLAYLIST`, etc. |
| `creatorContentType` | `VIDEO_ON_DEMAND`, `SHORTS`, `LIVE_STREAM`, `STORY` |

---

## YouTube Reporting API

Mock YouTube Reporting API for testing bulk data exports.

Use for: Background jobs that sync channel data, historical data pipelines, bulk analytics processing.

**Workflow:** Create job → Poll for reports → Download CSV → Parse & upsert

### Setup

```php
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\ReportingMedia;
```

### Creating Jobs

```php
it('creates a reporting job', function () {
    YoutubeReportingApi::fake([
        ReportingJob::create([
            'id' => 'job-123',
            'reportTypeId' => 'channel_basic_a2',
            'name' => 'My Channel Report',
        ]),
    ]);

    $response = $this->postJson('/api/reporting/jobs', [
        'reportTypeId' => 'channel_basic_a2',
        'name' => 'My Channel Report',
    ]);

    $response->assertOk()
        ->assertJsonPath('id', 'job-123')
        ->assertJsonPath('reportTypeId', 'channel_basic_a2');
});
```

### Listing Jobs

```php
it('lists reporting jobs', function () {
    YoutubeReportingApi::fake([
        ReportingJob::list([
            ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2', 'name' => 'Job 1'],
            ['id' => 'job-2', 'reportTypeId' => 'channel_demographics_a1', 'name' => 'Job 2'],
        ]),
    ]);

    $response = $this->getJson('/api/reporting/jobs');

    $response->assertOk()
        ->assertJsonCount(2, 'jobs');
});
```

### Listing Available Reports

```php
it('lists available reports for a job', function () {
    YoutubeReportingApi::fake([
        ReportingReport::list([
            [
                'id' => 'report-1',
                'jobId' => 'job-123',
                'startTime' => '2024-01-01T00:00:00Z',
                'endTime' => '2024-01-02T00:00:00Z',
                'downloadUrl' => 'https://youtube.com/reporting/v1/media/report-1',
            ],
            [
                'id' => 'report-2',
                'jobId' => 'job-123',
                'startTime' => '2024-01-02T00:00:00Z',
                'endTime' => '2024-01-03T00:00:00Z',
                'downloadUrl' => 'https://youtube.com/reporting/v1/media/report-2',
            ],
        ]),
    ]);

    $response = $this->getJson('/api/reporting/jobs/job-123/reports');

    $response->assertOk()
        ->assertJsonCount(2, 'reports');
});
```

### Downloading Report Data (CSV)

```php
it('downloads report CSV data', function () {
    $csvContent = "date,channel_id,views,watch_time_minutes,subscribers_gained\n" .
                  "2024-01-01,UC123,1000,5000,50\n" .
                  "2024-01-01,UC456,2000,10000,100\n";

    YoutubeReportingApi::fake([
        ReportingMedia::download($csvContent),
    ]);

    $response = $this->getJson('/api/reporting/download/report-1');

    $response->assertOk();
    // Parse CSV and process data
});
```

### Listing Report Types

```php
it('lists available report types', function () {
    YoutubeReportingApi::fake([
        ReportingReportType::list([
            ['id' => 'channel_basic_a2', 'name' => 'Channel Basic'],
            ['id' => 'channel_demographics_a1', 'name' => 'Channel Demographics'],
        ]),
    ]);

    $response = $this->getJson('/api/reporting/report-types');

    $response->assertOk()
        ->assertJsonCount(2, 'reportTypes');
});
```

### Full Pipeline Test

```php
it('processes reporting pipeline', function () {
    // 1. List available reports
    YoutubeReportingApi::fake([
        ReportingReport::list([
            [
                'id' => 'report-1',
                'jobId' => 'job-123',
                'startTime' => '2024-01-01T00:00:00Z',
                'endTime' => '2024-01-02T00:00:00Z',
                'downloadUrl' => 'https://youtube.com/reporting/v1/media/report-1',
            ],
        ]),
    ]);

    $reports = $this->getJson('/api/reporting/jobs/job-123/reports');
    $reports->assertOk();

    // 2. Download and parse CSV
    YoutubeReportingApi::fake([
        ReportingMedia::download(
            "date,channel_id,views,watch_time_minutes\n" .
            "2024-01-01,UC123,1000,5000\n"
        ),
    ]);

    $download = $this->getJson('/api/reporting/download/report-1');
    $download->assertOk();
});
```

### Common Report Types

| Report Type ID | Description |
|----------------|-------------|
| `channel_basic_a2` | Core channel metrics (views, watch time, subscribers) |
| `channel_demographics_a1` | Viewer age and gender breakdown |
| `channel_device_os_a2` | Device and OS distribution |
| `channel_traffic_source_a2` | Traffic source breakdown |
| `channel_playback_location_a2` | Where videos are watched |
| `channel_annotations_a1` | Annotation performance |
| `channel_cards_a1` | Card click-through rates |
| `channel_end_screens_a1` | End screen performance |

### CSV Column Naming

Reporting API uses `lowercase_underscore` naming (different from Analytics API camelCase):

| Reporting API (CSV) | Analytics API |
|---------------------|---------------|
| `views` | `views` |
| `watch_time_minutes` | `estimatedMinutesWatched` |
| `subscribers_gained` | `subscribersGained` |
| `subscribers_lost` | `subscribersLost` |
| `average_view_duration_seconds` | `averageViewDuration` |

### Data Freshness

- Reports generated daily with 24-72 hour delay
- Historical data available (180+ days depending on report type)
- Use `startTime`/`endTime` to identify report date range
