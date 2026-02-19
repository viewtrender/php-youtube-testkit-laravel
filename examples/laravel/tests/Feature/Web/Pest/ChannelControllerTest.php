<?php

use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\YoutubeDataApi;

afterEach(fn () => YoutubeDataApi::reset());

it('renders a single channel', function () {
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
});

it('renders multiple channels', function () {
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
});

it('renders empty when no channels found', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::empty(),
    ]);

    $response = $this->get('/channels?ids=nonexistent');

    $response->assertOk();
    $response->assertViewHas('channels', fn ($channels) => count($channels) === 0);
});

it('renders the channel view', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::list(),
    ]);

    $response = $this->get('/channels/UCuAXFkgsw1L7xaCfnd5JJOw');

    $response->assertOk();
    $response->assertViewIs('channels.show');
    $response->assertViewHas('channel');
    $response->assertViewHas('channel.title', 'Fake Channel');

    YoutubeDataApi::assertListedChannels();
});

it('renders custom channel data in the view', function () {
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
});

it('returns 404 when channel not found', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::empty(),
    ]);

    $response = $this->get('/channels/nonexistent');

    $response->assertNotFound();
});
