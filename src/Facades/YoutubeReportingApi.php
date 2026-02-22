<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Facades;

use Google\Client;
use Google\Service\YouTubeReporting;
use Illuminate\Support\Facades\Facade;
use Viewtrender\Youtube\YoutubeClient;
use Viewtrender\Youtube\YoutubeReportingApi as BaseYoutubeReportingApi;

/**
 * @method static YoutubeClient fake(array $responses = [])
 * @method static Client client()
 * @method static YouTubeReporting reporting()
 * @method static void assertSent(callable $callback)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void reset()
 *
 * @see BaseYoutubeReportingApi
 */
class YoutubeReportingApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseYoutubeReportingApi::class;
    }
}
