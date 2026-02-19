<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Facades;

use Google\Client;
use Google\Service\YouTube;
use Illuminate\Support\Facades\Facade;
use Viewtrender\Youtube\YoutubeClient;
use Viewtrender\Youtube\YoutubeDataApi as BaseYoutubeDataApi;

/**
 * @method static YoutubeClient fake(array $responses = [])
 * @method static Client client()
 * @method static YouTube youtube()
 * @method static void assertSent(callable $callback)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertListedVideos()
 * @method static void assertSearched()
 * @method static void assertListedChannels()
 * @method static void assertListedPlaylists()
 * @method static void reset()
 *
 * @see BaseYoutubeDataApi
 */
class YoutubeDataApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseYoutubeDataApi::class;
    }
}
