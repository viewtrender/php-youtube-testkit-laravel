<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Tests;

use Google\Service\YouTube;
use Orchestra\Testbench\TestCase;
use Viewtrender\Youtube\Exceptions\StrayRequestException;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Laravel\YoutubeDataApiServiceProvider;
use Viewtrender\Youtube\YoutubeDataApi;

class ContainerSwapTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [YoutubeDataApiServiceProvider::class];
    }

    protected function defineRoutes($router): void
    {
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
    }

    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_fake_auto_swaps_and_route_returns_fake_channel_data(): void
    {
        YoutubeDataApi::fake([YoutubeChannel::list()]);

        $response = $this->get('/channels/UC123');

        $response->assertOk();
        $response->assertJsonPath('items.0.id', fn ($id) => is_string($id));
        YoutubeDataApi::assertListedChannels();
    }

    public function test_fake_auto_swaps_and_route_returns_fake_video_data(): void
    {
        YoutubeDataApi::fake([YoutubeVideo::list()]);

        $response = $this->get('/videos/abc123');

        $response->assertOk();
        $response->assertJsonPath('items.0.id', fn ($id) => is_string($id));
        YoutubeDataApi::assertListedVideos();
    }

    public function test_prevent_stray_requests_via_config(): void
    {
        config()->set('youtube-testkit.prevent_stray_requests', true);

        YoutubeDataApi::fake([]);

        $youtube = $this->app->make(YouTube::class);

        $this->expectException(StrayRequestException::class);
        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);
    }

    public function test_reset_clears_fake_but_swap_hook_persists(): void
    {
        YoutubeDataApi::fake([YoutubeChannel::list()]);
        $this->app->make(YouTube::class);

        YoutubeDataApi::reset();

        $this->assertNull(YoutubeDataApi::instance());

        // Swap hook is still registered, so a new fake() re-swaps the binding
        YoutubeDataApi::fake([YoutubeChannel::list()]);

        $youtube = $this->app->make(YouTube::class);
        $this->assertInstanceOf(YouTube::class, $youtube);

        $youtube->channels->listChannels('snippet', ['id' => 'UC123']);
        YoutubeDataApi::assertListedChannels();
    }
}
