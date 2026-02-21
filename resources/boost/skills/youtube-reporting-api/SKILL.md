---
name: youtube-reporting-api
description: Mock YouTube Reporting API for bulk data exports in Laravel tests. Use when testing background sync jobs, historical data pipelines, CSV report downloads, or any scheduled reporting workflow.
---

# YouTube Reporting API Testing

Mock the YouTube Reporting API using `YoutubeReportingApi::fake()`.

Use for: Background jobs, historical data pipelines, bulk CSV exports, scheduled reports.

**Workflow:** Create job → Poll for reports → Download CSV → Parse & upsert

## Quick Start

```php
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingMedia;

it('processes reporting pipeline', function () {
    YoutubeReportingApi::fake([
        ReportingReport::list([
            ['id' => 'report-1', 'jobId' => 'job-123', 'downloadUrl' => 'https://...'],
        ]),
        ReportingMedia::download("date,views\n2024-01-01,1000\n"),
    ]);

    // Run your sync job
    $this->artisan('youtube:sync-reports')->assertSuccessful();
    
    YoutubeReportingApi::assertSentCount(2);
});
```

## Factories

| Factory | Methods |
|---------|---------|
| `ReportingJob` | `create(array)`, `list(array)`, `delete()` |
| `ReportingReport` | `list(array)` |
| `ReportingReportType` | `list(array)` |
| `ReportingMedia` | `download(string $csv)` |

## Creating Jobs

```php
use Viewtrender\Youtube\Factories\ReportingJob;

YoutubeReportingApi::fake([
    ReportingJob::create([
        'id' => 'job-123',
        'reportTypeId' => 'channel_basic_a2',
        'name' => 'Daily Channel Stats',
    ]),
]);
```

## Listing Jobs

```php
YoutubeReportingApi::fake([
    ReportingJob::list([
        ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2', 'name' => 'Basic'],
        ['id' => 'job-2', 'reportTypeId' => 'channel_demographics_a1', 'name' => 'Demographics'],
    ]),
]);
```

## Listing Available Reports

```php
use Viewtrender\Youtube\Factories\ReportingReport;

YoutubeReportingApi::fake([
    ReportingReport::list([
        [
            'id' => 'report-1',
            'jobId' => 'job-123',
            'startTime' => '2024-01-01T00:00:00Z',
            'endTime' => '2024-01-02T00:00:00Z',
            'createTime' => '2024-01-02T06:00:00Z',
            'downloadUrl' => 'https://youtubereporting.googleapis.com/v1/media/report-1',
        ],
    ]),
]);
```

## Downloading CSV Reports

```php
use Viewtrender\Youtube\Factories\ReportingMedia;

$csv = "date,channel_id,views,watch_time_minutes,subscribers_gained\n" .
       "2024-01-01,UC123,10000,50000,100\n" .
       "2024-01-02,UC123,12000,60000,120\n";

YoutubeReportingApi::fake([
    ReportingMedia::download($csv),
]);
```

## Listing Report Types

```php
use Viewtrender\Youtube\Factories\ReportingReportType;

YoutubeReportingApi::fake([
    ReportingReportType::list([
        ['id' => 'channel_basic_a2', 'name' => 'Channel Basic'],
        ['id' => 'channel_demographics_a1', 'name' => 'Channel Demographics'],
        ['id' => 'channel_traffic_source_a2', 'name' => 'Channel Traffic Source'],
    ]),
]);
```

## Deleting Jobs

```php
YoutubeReportingApi::fake([
    ReportingJob::delete(),
]);
```

## Common Report Types

| ID | Description |
|----|-------------|
| `channel_basic_a2` | Views, watch time, subscribers |
| `channel_demographics_a1` | Age and gender breakdown |
| `channel_device_os_a2` | Device and OS distribution |
| `channel_traffic_source_a2` | Traffic source breakdown |
| `channel_playback_location_a2` | Where videos are watched |
| `channel_cards_a1` | Card click-through rates |
| `channel_end_screens_a1` | End screen performance |

## CSV Column Names

Reporting API uses `snake_case` (different from Analytics API camelCase):

| Reporting CSV | Analytics API |
|---------------|---------------|
| `views` | `views` |
| `watch_time_minutes` | `estimatedMinutesWatched` |
| `subscribers_gained` | `subscribersGained` |
| `average_view_duration_seconds` | `averageViewDuration` |

## Full Pipeline Test

```php
it('syncs channel data from reporting API', function () {
    YoutubeReportingApi::fake([
        // 1. List jobs
        ReportingJob::list([['id' => 'job-123', 'reportTypeId' => 'channel_basic_a2']]),
        // 2. List reports for job
        ReportingReport::list([
            ['id' => 'report-1', 'jobId' => 'job-123', 'downloadUrl' => 'https://...'],
        ]),
        // 3. Download CSV
        ReportingMedia::download("date,channel_id,views\n2024-01-01,UC123,10000\n"),
    ]);

    dispatch(new SyncChannelReportsJob());

    YoutubeReportingApi::assertSentCount(3);
    $this->assertDatabaseHas('channel_stats', ['views' => 10000]);
});
```

## Data Freshness

- Reports generated daily with 24-72 hour delay
- Historical data available 180+ days
- Use `startTime`/`endTime` to identify report date range

## Assertions

```php
YoutubeReportingApi::assertSent(fn ($request) => true);
YoutubeReportingApi::assertSentCount(3);
YoutubeReportingApi::assertNothingSent();
```

## Teardown

```php
protected function tearDown(): void
{
    YoutubeReportingApi::reset();
    parent::tearDown();
}
```
