<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Tests;

use Google\Service\YouTube;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Laravel\Facades\YoutubeDataApi;
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
        ];
    }

    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_fake_returns_fake_client(): void
    {
        $fake = YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $this->assertNotNull($fake);
    }

    public function test_fake_swaps_container_and_returns_fake_data(): void
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

    public function test_assert_not_sent(): void
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

    public function test_assert_nothing_sent(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        YoutubeDataApi::assertNothingSent();
    }

    public function test_assert_sent_count(): void
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

    public function test_assert_sent_with_callback(): void
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

    public function test_assert_listed_playlists(): void
    {
        YoutubeDataApi::fake([
            YoutubePlaylist::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->playlists->listPlaylists('snippet', ['channelId' => 'UC123']);

        YoutubeDataApi::assertListedPlaylists();
    }

    public function test_assert_searched(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $youtube->search->listSearch('snippet', ['q' => 'laravel']);

        YoutubeDataApi::assertSearched();
    }
}
