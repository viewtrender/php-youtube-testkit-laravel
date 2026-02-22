<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Tests;

use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics;
use Google\Service\YouTubeReporting;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Laravel\Facades\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Laravel\Facades\YoutubeDataApi;
use Viewtrender\Youtube\Laravel\Facades\YoutubeReportingApi;
use Viewtrender\Youtube\Laravel\YoutubeDataApiServiceProvider;

class FacadeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [YoutubeDataApiServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'YoutubeDataApi' => YoutubeDataApi::class,
            'YoutubeAnalyticsApi' => YoutubeAnalyticsApi::class,
            'YoutubeReportingApi' => YoutubeReportingApi::class,
        ];
    }

    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        YoutubeAnalyticsApi::reset();
        YoutubeReportingApi::reset();
        parent::tearDown();
    }

    // ── Data API Facade ───────────────────────────────────────

    public function test_data_api_fake_returns_fake_client(): void
    {
        $fake = YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $this->assertNotNull($fake);
    }

    public function test_data_api_fake_swaps_container_and_returns_fake_data(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $response = $youtube->channels->listChannels('snippet', ['id' => 'UC123']);

        $this->assertNotEmpty($response->getItems());
        $this->assertSame('Fake Channel', $response->getItems()[0]->getSnippet()->getTitle());

        YoutubeDataApi::assertListedChannels();
    }

    public function test_data_api_assert_not_sent(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);

        YoutubeDataApi::assertNotSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), '/youtube/v3/videos');
        });
    }

    public function test_data_api_assert_nothing_sent(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        YoutubeDataApi::assertNothingSent();
    }

    public function test_data_api_assert_sent_count(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
            YoutubeChannel::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);
        $youtube->channels->listChannels('snippet', ['id' => 'UC456']);

        YoutubeDataApi::assertSentCount(2);
    }

    public function test_data_api_assert_sent_with_callback(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);

        YoutubeDataApi::assertSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), '/youtube/v3/channels');
        });
    }

    public function test_data_api_assert_listed_playlists(): void
    {
        YoutubeDataApi::fake([
            YoutubePlaylist::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->playlists->listPlaylists('snippet', ['channelId' => 'UC123']);

        YoutubeDataApi::assertListedPlaylists();
    }

    public function test_data_api_assert_searched(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->search->listSearch('snippet', ['q' => 'laravel']);

        YoutubeDataApi::assertSearched();
    }

    // ── Analytics API Facade ──────────────────────────────────

    public function test_analytics_api_fake_returns_fake_client(): void
    {
        $fake = YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
        ]);

        $this->assertNotNull($fake);
    }

    public function test_analytics_api_fake_swaps_container_and_returns_fake_data(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
        ]);

        $analytics = $this->app->make(YouTubeAnalytics::class);
        $response = $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views,estimatedMinutesWatched',
        ]);

        $this->assertSame('youtubeAnalytics#resultTable', $response->getKind());
        $this->assertNotEmpty($response->getRows());

        YoutubeAnalyticsApi::assertQueriedAnalytics();
    }

    public function test_analytics_api_assert_not_sent(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
        ]);

        $analytics = $this->app->make(YouTubeAnalytics::class);
        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views',
        ]);

        YoutubeAnalyticsApi::assertNotSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), '/youtube/v3/channels');
        });
    }

    public function test_analytics_api_assert_nothing_sent(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
        ]);

        YoutubeAnalyticsApi::assertNothingSent();
    }

    public function test_analytics_api_assert_sent_count(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
            AnalyticsQueryResponse::topVideos(),
        ]);

        $analytics = $this->app->make(YouTubeAnalytics::class);
        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views',
        ]);
        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views',
            'dimensions' => 'video',
            'maxResults' => 10,
            'sort' => '-views',
        ]);

        YoutubeAnalyticsApi::assertSentCount(2);
    }

    public function test_analytics_api_assert_sent_with_callback(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
        ]);

        $analytics = $this->app->make(YouTubeAnalytics::class);
        $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-31',
            'metrics' => 'views',
        ]);

        YoutubeAnalyticsApi::assertSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), 'reports');
        });
    }

    // ── Reporting API Facade ──────────────────────────────────

    public function test_reporting_api_fake_returns_fake_client(): void
    {
        $fake = YoutubeReportingApi::fake([
            ReportingReportType::list(),
        ]);

        $this->assertNotNull($fake);
    }

    public function test_reporting_api_fake_swaps_container_and_returns_fake_data(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list(),
        ]);

        $reporting = $this->app->make(YouTubeReporting::class);
        $response = $reporting->reportTypes->listReportTypes();

        $this->assertNotEmpty($response->getReportTypes());

        YoutubeReportingApi::assertSentCount(1);
    }

    public function test_reporting_api_assert_not_sent(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list(),
        ]);

        $reporting = $this->app->make(YouTubeReporting::class);
        $reporting->reportTypes->listReportTypes();

        YoutubeReportingApi::assertNotSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), '/youtube/v3/channels');
        });
    }

    public function test_reporting_api_assert_nothing_sent(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list(),
        ]);

        YoutubeReportingApi::assertNothingSent();
    }

    public function test_reporting_api_assert_sent_count(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list(),
            ReportingJob::list(),
        ]);

        $reporting = $this->app->make(YouTubeReporting::class);
        $reporting->reportTypes->listReportTypes();
        $reporting->jobs->listJobs();

        YoutubeReportingApi::assertSentCount(2);
    }

    public function test_reporting_api_assert_sent_with_callback(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list(),
        ]);

        $reporting = $this->app->make(YouTubeReporting::class);
        $reporting->reportTypes->listReportTypes();

        YoutubeReportingApi::assertSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), 'reportTypes');
        });
    }
}
