# YouTube TestKit

Mock YouTube APIs in Laravel tests. Three APIs supported:

| API | Facade | Use Case |
|-----|--------|----------|
| Data | `YoutubeDataApi` | Videos, channels, playlists, search |
| Analytics | `YoutubeAnalyticsApi` | On-demand metrics queries |
| Reporting | `YoutubeReportingApi` | Bulk CSV exports |

## Quick Start

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Factories\ReportingJob;

// Data API
YoutubeDataApi::fake([YoutubeVideo::listWithVideos([['id' => 'abc', 'snippet' => ['title' => 'Test']]])]);

// Analytics API
YoutubeAnalyticsApi::fake([AnalyticsQueryResponse::channelOverview(['views' => 1000])]);

// Reporting API
YoutubeReportingApi::fake([ReportingJob::list([['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2']])]);
```

## Factories

**Data:** `YoutubeVideo`, `YoutubeChannel`, `YoutubePlaylist`, `YoutubePlaylistItems`, `YoutubeSearchResult`, `YoutubeCommentThreads`, `YoutubeSubscriptions`, `YoutubeActivities`, `YoutubeCaptions`

**Analytics:** `AnalyticsQueryResponse` — methods: `channelOverview()`, `dailyMetrics()`, `topVideos()`, `trafficSources()`, `demographics()`, `geography()`, `videoTypes()`

**Reporting:** `ReportingJob`, `ReportingReport`, `ReportingReportType`, `ReportingMedia`

## Assertions

```php
YoutubeDataApi::assertSentCount(1);
YoutubeDataApi::assertListedVideos();
YoutubeAnalyticsApi::assertSentCount(1);
YoutubeReportingApi::assertSentCount(1);
```

## Errors

```php
use Viewtrender\Youtube\Responses\ErrorResponse;
YoutubeDataApi::fake([ErrorResponse::notFound()]);
YoutubeDataApi::fake([ErrorResponse::quotaExceeded()]);
```

## Full Documentation

See `vendor/viewtrender/php-youtube-testkit-core/`:
- `README.md` — Data API factories & examples
- `docs/ANALYTICS_API.md` — Analytics metrics, dimensions, query patterns
- `docs/REPORTING_API.md` — Report types, CSV columns, pipeline patterns
