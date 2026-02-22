<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Facades;

use Google\Client;
use Google\Service\YouTubeAnalytics;
use Illuminate\Support\Facades\Facade;
use Viewtrender\Youtube\YoutubeClient;
use Viewtrender\Youtube\YoutubeAnalyticsApi as BaseYoutubeAnalyticsApi;

/**
 * @method static YoutubeClient fake(array $responses = [])
 * @method static Client client()
 * @method static YouTubeAnalytics analytics()
 * @method static void assertSent(callable $callback)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void reset()
 *
 * @see BaseYoutubeAnalyticsApi
 */
class YoutubeAnalyticsApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseYoutubeAnalyticsApi::class;
    }
}
