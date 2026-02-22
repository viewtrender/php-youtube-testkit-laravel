<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Tests;

use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics;
use Google\Service\YouTubeReporting;
use Orchestra\Testbench\TestCase;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Laravel\YoutubeDataApiServiceProvider;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeReportingApi;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [YoutubeDataApiServiceProvider::class];
    }

    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        YoutubeAnalyticsApi::reset();
        YoutubeReportingApi::reset();
        parent::tearDown();
    }

    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(YoutubeDataApiServiceProvider::class));
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('youtube-testkit'));
        $this->assertNull(config('youtube-testkit.fixtures_path'));
        $this->assertFalse(config('youtube-testkit.prevent_stray_requests'));
    }

    // --- Data API ---

    public function test_fake_auto_swaps_youtube_container_binding(): void
    {
        YoutubeDataApi::fake([YoutubeChannel::list()]);

        $youtube = $this->app->make(YouTube::class);

        $this->assertInstanceOf(YouTube::class, $youtube);
    }

    public function test_resolved_youtube_uses_mock_handler(): void
    {
        YoutubeDataApi::fake([YoutubeChannel::list()]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);

        YoutubeDataApi::assertListedChannels();
    }

    // --- Analytics API ---

    public function test_fake_auto_swaps_analytics_container_binding(): void
    {
        YoutubeAnalyticsApi::fake([AnalyticsQueryResponse::channelOverview()]);

        $analytics = $this->app->make(YouTubeAnalytics::class);

        $this->assertInstanceOf(YouTubeAnalytics::class, $analytics);
    }

    public function test_resolved_analytics_uses_mock_handler(): void
    {
        YoutubeAnalyticsApi::fake([AnalyticsQueryResponse::channelOverview()]);

        $analytics = $this->app->make(YouTubeAnalytics::class);
        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views,estimatedMinutesWatched',
        ]);

        YoutubeAnalyticsApi::assertQueriedAnalytics();
    }

    // --- Reporting API ---

    public function test_fake_auto_swaps_reporting_container_binding(): void
    {
        YoutubeReportingApi::fake([ReportingReportType::list()]);

        $reporting = $this->app->make(YouTubeReporting::class);

        $this->assertInstanceOf(YouTubeReporting::class, $reporting);
    }

    public function test_resolved_reporting_uses_mock_handler(): void
    {
        YoutubeReportingApi::fake([ReportingReportType::list()]);

        $reporting = $this->app->make(YouTubeReporting::class);
        $reporting->reportTypes->listReportTypes();

        YoutubeReportingApi::assertSentCount(1);
    }

    // --- Boost ---

    public function test_boost_guidelines_exist(): void
    {
        $basePath = __DIR__ . '/../resources/boost/';

        // Core guidelines file
        $this->assertFileExists($basePath . 'guidelines/core.blade.php');

        // Skill files
        $this->assertFileExists($basePath . 'skills/youtube-data-api/SKILL.md');
        $this->assertFileExists($basePath . 'skills/youtube-analytics-api/SKILL.md');
        $this->assertFileExists($basePath . 'skills/youtube-reporting-api/SKILL.md');

        // Skills contain their respective fake() examples
        $dataContent = file_get_contents($basePath . 'skills/youtube-data-api/SKILL.md');
        $analyticsContent = file_get_contents($basePath . 'skills/youtube-analytics-api/SKILL.md');
        $reportingContent = file_get_contents($basePath . 'skills/youtube-reporting-api/SKILL.md');

        $this->assertStringContainsString('YoutubeDataApi::fake(', $dataContent);
        $this->assertStringContainsString('YoutubeAnalyticsApi::fake(', $analyticsContent);
        $this->assertStringContainsString('YoutubeReportingApi::fake(', $reportingContent);
    }
}
