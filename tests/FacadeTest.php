<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Laravel\Tests;

use Google\Service\YouTube;
use Orchestra\Testbench\TestCase;
use Viewtrender\Youtube\Factories\YoutubeChannel;
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
        \Viewtrender\Youtube\YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_fake_returns_fake_client(): void
    {
        $fake = \Viewtrender\Youtube\YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $this->assertNotNull($fake);
    }

    public function test_fake_swaps_container_and_returns_fake_data(): void
    {
        \Viewtrender\Youtube\YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $youtube = $this->app->make(YouTube::class);
        $response = $youtube->channels->listChannels('snippet', ['id' => 'UC123']);

        $this->assertNotEmpty($response->getItems());
        $this->assertSame('Fake Channel', $response->getItems()[0]->getSnippet()->getTitle());

        \Viewtrender\Youtube\YoutubeDataApi::assertListedChannels();
    }
}
