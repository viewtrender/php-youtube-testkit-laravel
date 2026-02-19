<?php

use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Viewtrender\Youtube\YoutubeDataApi;

afterEach(fn () => YoutubeDataApi::reset());

it('renders a single video', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up'],
                'statistics' => ['viewCount' => '1500000000'],
            ],
        ]),
    ]);

    $response = $this->get('/videos?ids=dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertViewIs('videos.index');
    $response->assertViewHas('videos', fn ($videos) => count($videos) === 1);
});

it('renders multiple videos', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'vid_1',
                'snippet' => ['title' => 'First Video'],
                'statistics' => ['viewCount' => '100'],
            ],
            [
                'id' => 'vid_2',
                'snippet' => ['title' => 'Second Video'],
                'statistics' => ['viewCount' => '200'],
            ],
            [
                'id' => 'vid_3',
                'snippet' => ['title' => 'Third Video'],
                'statistics' => ['viewCount' => '300'],
            ],
        ]),
    ]);

    $response = $this->get('/videos?ids=vid_1,vid_2,vid_3');

    $response->assertOk();
    $response->assertViewIs('videos.index');
    $response->assertViewHas('videos', fn ($videos) => count($videos) === 3);

    YoutubeDataApi::assertListedVideos();
});

it('renders empty when no videos found', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::empty(),
    ]);

    $response = $this->get('/videos?ids=nonexistent');

    $response->assertOk();
    $response->assertViewHas('videos', fn ($videos) => count($videos) === 0);
});

it('renders the video view', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::list(),
    ]);

    $response = $this->get('/videos/dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertViewIs('videos.show');
    $response->assertViewHas('video');
    $response->assertViewHas('video.title', 'Fake Video Title');

    YoutubeDataApi::assertListedVideos();
});

it('renders custom video data in the view', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'abc123',
                'snippet' => [
                    'title' => 'My Custom Video',
                    'channelTitle' => 'My Channel',
                ],
                'statistics' => [
                    'viewCount' => '999',
                ],
            ],
        ]),
    ]);

    $response = $this->get('/videos/abc123');

    $response->assertOk();
    $response->assertViewHas('video.title', 'My Custom Video');
    $response->assertViewHas('video.views', '999');
});

it('returns 404 when video not found', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::empty(),
    ]);

    $response = $this->get('/videos/nonexistent');

    $response->assertNotFound();
});

it('handles api errors gracefully', function () {
    YoutubeDataApi::fake([
        ErrorResponse::quotaExceeded(),
    ]);

    $response = $this->get('/videos/any');

    $response->assertStatus(500);
});

it('renders search results view', function () {
    YoutubeDataApi::fake([
        YoutubeSearchResult::listWithResults([
            ['snippet' => ['title' => 'First Result']],
            ['snippet' => ['title' => 'Second Result']],
        ]),
    ]);

    $response = $this->get('/videos/search?q=laravel');

    $response->assertOk();
    $response->assertViewIs('videos.search');
    $response->assertViewHas('query', 'laravel');
    $response->assertViewHas('results', fn ($results) => count($results) === 2);

    YoutubeDataApi::assertSearched();
});

it('renders empty search results', function () {
    YoutubeDataApi::fake([
        YoutubeSearchResult::empty(),
    ]);

    $response = $this->get('/videos/search?q=noresults');

    $response->assertOk();
    $response->assertViewHas('results', fn ($results) => count($results) === 0);
});
