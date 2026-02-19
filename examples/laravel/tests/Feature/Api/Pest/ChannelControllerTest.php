<?php

use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\YoutubeDataApi;

afterEach(fn () => YoutubeDataApi::reset());

it('returns a single channel', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::listWithChannels([
            [
                'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                'snippet' => ['title' => 'Rick Astley'],
                'statistics' => ['subscriberCount' => '3500000'],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channels?ids=UCuAXFkgsw1L7xaCfnd5JJOw');

    $response->assertOk();
    $response->assertJsonCount(1, 'channels');
    $response->assertJsonPath('channels.0.title', 'Rick Astley');
    $response->assertJsonPath('channels.0.subscribers', '3500000');
});

it('returns multiple channels', function () {
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

    $response = $this->getJson('/api/channels?ids=UC_one,UC_two');

    $response->assertOk();
    $response->assertJsonCount(2, 'channels');
    $response->assertJsonPath('channels.0.title', 'Channel One');
    $response->assertJsonPath('channels.1.title', 'Channel Two');

    YoutubeDataApi::assertListedChannels();
});

it('returns empty array when no channels found', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::empty(),
    ]);

    $response = $this->getJson('/api/channels?ids=nonexistent');

    $response->assertOk();
    $response->assertJsonCount(0, 'channels');
});

it('returns channel details', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::list(),
    ]);

    $response = $this->getJson('/api/channels/UCuAXFkgsw1L7xaCfnd5JJOw');

    $response->assertOk();
    $response->assertJsonPath('title', 'Fake Channel');
    $response->assertJsonStructure(['id', 'title', 'description', 'subscribers', 'video_count']);

    YoutubeDataApi::assertListedChannels();
});

it('returns custom channel data', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::listWithChannels([
            [
                'id' => 'UC_custom',
                'snippet' => ['title' => 'My Custom Channel'],
                'statistics' => ['subscriberCount' => '1000000'],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channels/UC_custom');

    $response->assertOk();
    $response->assertJsonPath('title', 'My Custom Channel');
    $response->assertJsonPath('subscribers', '1000000');
});

it('returns 404 when channel not found', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::empty(),
    ]);

    $response = $this->getJson('/api/channels/nonexistent');

    $response->assertNotFound();
});
