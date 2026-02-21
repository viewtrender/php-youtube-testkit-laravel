# YouTube Reporting API Testkit

Mock YouTube Reporting API for testing bulk data exports in Laravel applications.

## Overview

The Reporting API provides scheduled bulk data exports. Use for:
- Background jobs that sync channel data
- Historical data pipelines
- Bulk analytics processing

**Workflow:** Create job → Poll for reports → Download CSV → Parse & upsert

## Setup

```php
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\ReportingMedia;
```

## Creating Jobs

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

## Listing Jobs

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

## Listing Available Reports

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

## Downloading Report Data (CSV)

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

## Listing Report Types

```php
it('lists available report types', function () {
    YoutubeReportingApi::fake([
        ReportingReportType::list([
            [
                'id' => 'channel_basic_a2',
                'name' => 'Channel Basic',
                'deprecateTime' => null,
            ],
            [
                'id' => 'channel_demographics_a1',
                'name' => 'Channel Demographics',
                'deprecateTime' => null,
            ],
        ]),
    ]);

    $response = $this->getJson('/api/reporting/report-types');

    $response->assertOk()
        ->assertJsonCount(2, 'reportTypes');
});
```

## Full Pipeline Test

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

## Common Report Types

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

## CSV Column Naming

Reporting API uses `lowercase_underscore` naming (different from Analytics API camelCase):

| Reporting API (CSV) | Analytics API |
|---------------------|---------------|
| `views` | `views` |
| `watch_time_minutes` | `estimatedMinutesWatched` |
| `subscribers_gained` | `subscribersGained` |
| `subscribers_lost` | `subscribersLost` |
| `average_view_duration_seconds` | `averageViewDuration` |

## Data Freshness

- Reports generated daily with 24-72 hour delay
- Historical data available (180+ days depending on report type)
- Use `startTime`/`endTime` to identify report date range
