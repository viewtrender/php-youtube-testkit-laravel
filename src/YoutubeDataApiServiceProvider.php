<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel;

use Google\Service\YouTube;
use Illuminate\Support\ServiceProvider;
use Viewtrender\Youtube\YoutubeDataApi;

class YoutubeDataApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/youtube-fake.php', 'youtube-fake');

        YoutubeDataApi::registerContainerSwap(function () {
            $this->app->instance(YouTube::class, YoutubeDataApi::youtube());

            if ($this->app['config']->get('youtube-fake.prevent_stray_requests')) {
                YoutubeDataApi::instance()->preventStrayRequests();
            }
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/youtube-fake.php' => config_path('youtube-fake.php'),
            ], 'youtube-fake-config');
        }
    }
}
