---
name: youtube-analytics-api
description: Mock YouTube Analytics API for on-demand metrics queries in Laravel tests. Use when testing dashboards, real-time stats, custom date range reports, traffic sources, demographics, geography, or any analytics query.
---

# YouTube Analytics API Testing

Mock the YouTube Analytics API using `YoutubeAnalyticsApi::fake()`.

Use for: Dashboard widgets, on-demand reports, real-time metrics, custom date range queries.

## Quick Start

```php
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;

it('fetches channel overview', function () {
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
    $response->assertOk();
    
    YoutubeAnalyticsApi::assertSentCount(1);
});
```

## Factory Methods

| Method | Use Case |
|--------|----------|
| `channelOverview(array)` | Aggregate channel metrics |
| `dailyMetrics(array)` | Time-series by day |
| `topVideos(array)` | Videos ranked by metric |
| `trafficSources(array)` | Traffic source breakdown |
| `demographics(array)` | Age/gender distribution |
| `geography(array)` | Country breakdown |
| `videoTypes(array)` | VOD vs Shorts vs Live |

## Daily Metrics

```php
YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::dailyMetrics([
        ['day' => '2024-01-01', 'views' => 10000, 'estimatedMinutesWatched' => 30000],
        ['day' => '2024-01-02', 'views' => 12000, 'estimatedMinutesWatched' => 36000],
    ]),
]);
```

## Top Videos

```php
YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::topVideos([
        ['video' => 'dQw4w9WgXcQ', 'views' => 50000, 'estimatedMinutesWatched' => 150000],
        ['video' => 'abc123xyz', 'views' => 30000, 'estimatedMinutesWatched' => 90000],
    ]),
]);
```

## Traffic Sources

```php
YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::trafficSources([
        ['source' => 'RELATED_VIDEO', 'views' => 200000],
        ['source' => 'YT_SEARCH', 'views' => 150000],
        ['source' => 'EXT_URL', 'views' => 50000],
    ]),
]);
```

## Demographics

```php
YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::demographics([
        ['ageGroup' => 'age18-24', 'gender' => 'male', 'viewerPercentage' => 25.5],
        ['ageGroup' => 'age25-34', 'gender' => 'male', 'viewerPercentage' => 22.1],
    ]),
]);
```

## Geography

```php
YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::geography([
        ['country' => 'US', 'views' => 200000],
        ['country' => 'GB', 'views' => 80000],
    ]),
]);
```

## Content Types

```php
YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::videoTypes([
        ['video' => 'abc', 'creatorContentType' => 'VIDEO_ON_DEMAND', 'views' => 180000],
        ['video' => 'def', 'creatorContentType' => 'SHORTS', 'views' => 120000],
    ]),
]);
```

## Available Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `views` | int | Total views |
| `estimatedMinutesWatched` | int | Watch time in minutes |
| `averageViewDuration` | int | Avg duration in seconds |
| `subscribersGained` | int | New subscribers |
| `subscribersLost` | int | Lost subscribers |
| `likes`, `shares`, `comments` | int | Engagement counts |
| `viewerPercentage` | float | Demographics percentage |

## Available Dimensions

| Dimension | Example Values |
|-----------|----------------|
| `day` | `2024-01-01` |
| `video` | Video ID |
| `country` | `US`, `GB`, `CA` |
| `ageGroup` | `age18-24`, `age25-34`, `age35-44` |
| `gender` | `male`, `female` |
| `insightTrafficSourceType` | `RELATED_VIDEO`, `YT_SEARCH`, `EXT_URL` |
| `creatorContentType` | `VIDEO_ON_DEMAND`, `SHORTS`, `LIVE_STREAM` |

## Assertions

```php
YoutubeAnalyticsApi::assertSent(fn ($request) => true);
YoutubeAnalyticsApi::assertSentCount(1);
YoutubeAnalyticsApi::assertNothingSent();
```

## Teardown

```php
protected function tearDown(): void
{
    YoutubeAnalyticsApi::reset();
    parent::tearDown();
}
```
