<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Tests;

use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics;
use Google\Service\YouTubeReporting;
use Orchestra\Testbench\TestCase;
use Viewtrender\Youtube\Exceptions\StrayRequestException;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Laravel\YoutubeDataApiServiceProvider;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeReportingApi;

class ContainerSwapTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [YoutubeDataApiServiceProvider::class];
    }

    protected function defineRoutes($router): void
    {
        // Data API routes
        $router->get('/channels/{id}', function (YouTube $youtube, string $id) {
            $response = $youtube->channels->listChannels('snippet', ['id' => $id]);

            return response()->json([
                'items' => array_map(
                    fn ($item) => ['id' => $item->getId(), 'title' => $item->getSnippet()->getTitle()],
                    $response->getItems(),
                ),
            ]);
        });

        $router->get('/videos/{id}', function (YouTube $youtube, string $id) {
            $response = $youtube->videos->listVideos('snippet', ['id' => $id]);

            return response()->json([
                'items' => array_map(
                    fn ($item) => ['id' => $item->getId(), 'title' => $item->getSnippet()->getTitle()],
                    $response->getItems(),
                ),
            ]);
        });

        // Analytics API route
        $router->get('/analytics/channel', function (YouTubeAnalytics $analytics) {
            $response = $analytics->reports->query([
                'ids' => 'channel==MINE',
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-31',
                'metrics' => 'views,estimatedMinutesWatched',
            ]);

            return response()->json([
                'kind' => $response->getKind(),
                'rows' => $response->getRows(),
            ]);
        });

        // Reporting API route
        $router->get('/reporting/report-types', function (YouTubeReporting $reporting) {
            $response = $reporting->reportTypes->listReportTypes();

            return response()->json([
                'reportTypes' => $response->getReportTypes(),
            ]);
        });
    }

    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        YoutubeAnalyticsApi::reset();
        YoutubeReportingApi::reset();
        parent::tearDown();
    }

    // ── Data API ──────────────────────────────────────────────

    public function test_data_api_auto_swaps_and_route_returns_fake_channel_data(): void
    {
        YoutubeDataApi::fake([YoutubeChannel::list()]);

        $response = $this->get('/channels/UC123');

        $response->assertOk();
        $response->assertJsonPath('items.0.id', fn ($id) => is_string($id));
        YoutubeDataApi::assertListedChannels();
    }

    public function test_data_api_auto_swaps_and_route_returns_fake_video_data(): void
    {
        YoutubeDataApi::fake([YoutubeVideo::list()]);

        $response = $this->get('/videos/abc123');

        $response->assertOk();
        $response->assertJsonPath('items.0.id', fn ($id) => is_string($id));
        YoutubeDataApi::assertListedVideos();
    }

    public function test_data_api_prevent_stray_requests_via_config(): void
    {
        config()->set('youtube-testkit.prevent_stray_requests', true);

        YoutubeDataApi::fake([]);

        $youtube = $this->app->make(YouTube::class);

        $this->expectException(StrayRequestException::class);
        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);
    }

    public function test_data_api_reset_clears_fake_but_swap_hook_persists(): void
    {
        YoutubeDataApi::fake([YoutubeChannel::list()]);
        $this->app->make(YouTube::class);

        YoutubeDataApi::reset();

        $this->assertNull(YoutubeDataApi::instance());

        YoutubeDataApi::fake([YoutubeChannel::list()]);

        $youtube = $this->app->make(YouTube::class);
        $this->assertInstanceOf(YouTube::class, $youtube);

        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);
        YoutubeDataApi::assertListedChannels();
    }

    // ── Analytics API ─────────────────────────────────────────

    public function test_analytics_api_auto_swaps_and_route_returns_fake_data(): void
    {
        YoutubeAnalyticsApi::fake([AnalyticsQueryResponse::channelOverview()]);

        $response = $this->get('/analytics/channel');

        $response->assertOk();
        $response->assertJsonPath('kind', 'youtubeAnalytics#resultTable');
        YoutubeAnalyticsApi::assertQueriedAnalytics();
    }

    public function test_analytics_api_prevent_stray_requests_via_config(): void
    {
        config()->set('youtube-testkit.prevent_stray_requests', true);

        YoutubeAnalyticsApi::fake([]);

        $analytics = $this->app->make(YouTubeAnalytics::class);

        $this->expectException(StrayRequestException::class);
        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views',
        ]);
    }

    public function test_analytics_api_reset_clears_fake_but_swap_hook_persists(): void
    {
        YoutubeAnalyticsApi::fake([AnalyticsQueryResponse::channelOverview()]);
        $this->app->make(YouTubeAnalytics::class);

        YoutubeAnalyticsApi::reset();

        $this->assertNull(YoutubeAnalyticsApi::instance());

        YoutubeAnalyticsApi::fake([AnalyticsQueryResponse::channelOverview()]);

        $analytics = $this->app->make(YouTubeAnalytics::class);
        $this->assertInstanceOf(YouTubeAnalytics::class, $analytics);

        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views',
        ]);
        YoutubeAnalyticsApi::assertSentCount(1);
    }

    // ── Reporting API ─────────────────────────────────────────

    public function test_reporting_api_auto_swaps_and_route_returns_fake_data(): void
    {
        YoutubeReportingApi::fake([ReportingReportType::list()]);

        $response = $this->get('/reporting/report-types');

        $response->assertOk();
        YoutubeReportingApi::assertSentCount(1);
    }

    public function test_reporting_api_prevent_stray_requests_via_config(): void
    {
        config()->set('youtube-testkit.prevent_stray_requests', true);

        YoutubeReportingApi::fake([]);

        $reporting = $this->app->make(YouTubeReporting::class);

        $this->expectException(StrayRequestException::class);
        $reporting->reportTypes->listReportTypes();
    }

    public function test_reporting_api_reset_clears_fake_but_swap_hook_persists(): void
    {
        YoutubeReportingApi::fake([ReportingReportType::list()]);
        $this->app->make(YouTubeReporting::class);

        YoutubeReportingApi::reset();

        $this->assertNull(YoutubeReportingApi::instance());

        YoutubeReportingApi::fake([ReportingReportType::list()]);

        $reporting = $this->app->make(YouTubeReporting::class);
        $this->assertInstanceOf(YouTubeReporting::class, $reporting);

        $reporting->reportTypes->listReportTypes();
        YoutubeReportingApi::assertSentCount(1);
    }
}
