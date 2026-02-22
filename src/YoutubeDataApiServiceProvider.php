<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel;

use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics;
use Google\Service\YouTubeReporting;
use Illuminate\Support\ServiceProvider;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeReportingApi;

class YoutubeDataApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/youtube-testkit.php', 'youtube-testkit');

        // YouTube Data API
        YoutubeDataApi::registerContainerSwap(function () {
            $this->app->instance(YouTube::class, YoutubeDataApi::youtube());

            if ($this->app['config']->get('youtube-testkit.prevent_stray_requests')) {
                YoutubeDataApi::instance()->preventStrayRequests();
            }
        });

        // YouTube Analytics API
        YoutubeAnalyticsApi::registerContainerSwap(function () {
            $this->app->instance(YouTubeAnalytics::class, YoutubeAnalyticsApi::analytics());

            if ($this->app['config']->get('youtube-testkit.prevent_stray_requests')) {
                YoutubeAnalyticsApi::instance()->preventStrayRequests();
            }
        });

        // YouTube Reporting API
        YoutubeReportingApi::registerContainerSwap(function () {
            $this->app->instance(YouTubeReporting::class, YoutubeReportingApi::reporting());

            if ($this->app['config']->get('youtube-testkit.prevent_stray_requests')) {
                YoutubeReportingApi::instance()->preventStrayRequests();
            }
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/youtube-testkit.php' => config_path('youtube-testkit.php'),
            ], 'youtube-testkit-config');
        }

        // Register Boost guidelines for Laravel AI assistant
        // These get appended to CLAUDE.md/AGENTS.md when running boost:update
        $this->loadViewsFrom(__DIR__ . '/../resources/boost/guidelines', 'youtube-testkit-boost-guidelines');
    }
}
