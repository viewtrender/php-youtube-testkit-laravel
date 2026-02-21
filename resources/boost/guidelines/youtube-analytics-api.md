# YouTube Analytics API Testkit

Mock YouTube Analytics API for testing on-demand analytics queries in Laravel applications.

## Overview

The Analytics API provides real-time query access to channel metrics. Use for:
- Dashboard widgets showing current stats
- On-demand reports with custom date ranges
- Real-time metrics display

## Setup

```php
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
```

## Basic Query Testing

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

## Daily Metrics

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

## Top Videos

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

## Traffic Sources

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

## Demographics

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

## Geography

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

## Content Types

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

## Available Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `views` | INTEGER | Total video views |
| `estimatedMinutesWatched` | INTEGER | Total watch time in minutes |
| `averageViewDuration` | INTEGER | Average view duration in seconds |
| `subscribersGained` | INTEGER | New subscribers |
| `subscribersLost` | INTEGER | Lost subscribers |
| `likes` | INTEGER | Total likes |
| `dislikes` | INTEGER | Total dislikes |
| `shares` | INTEGER | Total shares |
| `comments` | INTEGER | Total comments |
| `viewerPercentage` | FLOAT | Percentage of viewers (demographics) |

## Available Dimensions

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
