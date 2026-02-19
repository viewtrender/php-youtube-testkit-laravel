<?php

namespace Tests\Feature\Web;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\YoutubeDataApi;

class ChannelControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_index_renders_single_channel(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::listWithChannels([
                [
                    'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'snippet' => ['title' => 'Rick Astley'],
                    'statistics' => ['subscriberCount' => '3500000'],
                ],
            ]),
        ]);

        $response = $this->get('/channels?ids=UCuAXFkgsw1L7xaCfnd5JJOw');

        $response->assertOk();
        $response->assertViewIs('channels.index');
        $response->assertViewHas('channels', fn ($channels) => count($channels) === 1);
    }

    public function test_index_renders_multiple_channels(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::listWithChannels([
                [
                    'id' => 'UC_one',
                    'snippet' => ['title' => 'Channel One'],
                    'statistics' => ['subscriberCount' => '5000'],
                ],
                [
                    'id' => 'UC_two',
                    'snippet' => ['title' => 'Channel Two'],
                    'statistics' => ['subscriberCount' => '10000'],
                ],
            ]),
        ]);

        $response = $this->get('/channels?ids=UC_one,UC_two');

        $response->assertOk();
        $response->assertViewIs('channels.index');
        $response->assertViewHas('channels', fn ($channels) => count($channels) === 2);

        YoutubeDataApi::assertListedChannels();
    }

    public function test_index_renders_empty_when_no_channels(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::empty(),
        ]);

        $response = $this->get('/channels?ids=nonexistent');

        $response->assertOk();
        $response->assertViewHas('channels', fn ($channels) => count($channels) === 0);
    }

    public function test_show_renders_channel_view(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $response = $this->get('/channels/UCuAXFkgsw1L7xaCfnd5JJOw');

        $response->assertOk();
        $response->assertViewIs('channels.show');
        $response->assertViewHas('channel');
        $response->assertViewHas('channel.title', 'Fake Channel');

        YoutubeDataApi::assertListedChannels();
    }

    public function test_show_with_custom_channel_data(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::listWithChannels([
                [
                    'id' => 'UC_custom',
                    'snippet' => ['title' => 'My Custom Channel'],
                    'statistics' => ['subscriberCount' => '1000000'],
                ],
            ]),
        ]);

        $response = $this->get('/channels/UC_custom');

        $response->assertOk();
        $response->assertViewHas('channel.title', 'My Custom Channel');
        $response->assertViewHas('channel.subscribers', '1000000');
    }

    public function test_show_returns_404_when_channel_not_found(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::empty(),
        ]);

        $response = $this->get('/channels/nonexistent');

        $response->assertNotFound();
    }
}
