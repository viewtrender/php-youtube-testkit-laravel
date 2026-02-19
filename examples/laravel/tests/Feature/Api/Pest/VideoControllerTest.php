<?php

use Psr\Http\Message\RequestInterface;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Viewtrender\Youtube\YoutubeDataApi;

afterEach(fn () => YoutubeDataApi::reset());

it('returns a single video', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => ['title' => 'Never Gonna Give You Up'],
                'statistics' => ['viewCount' => '1500000000'],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos?ids=dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertJsonCount(1, 'videos');
    $response->assertJsonPath('videos.0.title', 'Never Gonna Give You Up');
    $response->assertJsonPath('videos.0.views', '1500000000');
});

it('returns multiple videos', function () {
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

    $response = $this->getJson('/api/videos?ids=vid_1,vid_2,vid_3');

    $response->assertOk();
    $response->assertJsonCount(3, 'videos');
    $response->assertJsonPath('videos.0.title', 'First Video');
    $response->assertJsonPath('videos.1.title', 'Second Video');
    $response->assertJsonPath('videos.2.title', 'Third Video');

    YoutubeDataApi::assertListedVideos();
});

it('returns empty array when no videos found', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::empty(),
    ]);

    $response = $this->getJson('/api/videos?ids=nonexistent');

    $response->assertOk();
    $response->assertJsonCount(0, 'videos');
});

it('returns video details', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::list(),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

    $response->assertOk();
    $response->assertJsonPath('title', 'Fake Video Title');
    $response->assertJsonStructure(['id', 'title', 'description', 'channel', 'views', 'likes']);

    YoutubeDataApi::assertListedVideos();
});

it('returns custom video data', function () {
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

    $response = $this->getJson('/api/videos/abc123');

    $response->assertOk();
    $response->assertJsonPath('title', 'My Custom Video');
    $response->assertJsonPath('views', '999');
});

it('returns 404 when video not found', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::empty(),
    ]);

    $response = $this->getJson('/api/videos/nonexistent');

    $response->assertNotFound();
});

it('handles api errors gracefully', function () {
    YoutubeDataApi::fake([
        ErrorResponse::quotaExceeded(),
    ]);

    $response = $this->getJson('/api/videos/any');

    $response->assertStatus(500);
});

it('returns search results', function () {
    YoutubeDataApi::fake([
        YoutubeSearchResult::listWithResults([
            ['snippet' => ['title' => 'First Result']],
            ['snippet' => ['title' => 'Second Result']],
        ]),
    ]);

    $response = $this->getJson('/api/videos/search?q=laravel');

    $response->assertOk();
    $response->assertJsonCount(2, 'results');

    YoutubeDataApi::assertSearched();
});

it('returns empty results when no matches', function () {
    YoutubeDataApi::fake([
        YoutubeSearchResult::empty(),
    ]);

    $response = $this->getJson('/api/videos/search?q=noresults');

    $response->assertOk();
    $response->assertJsonCount(0, 'results');
});

it('can assert exact request details', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::list(),
    ]);

    $this->getJson('/api/videos/dQw4w9WgXcQ');

    YoutubeDataApi::assertSent(function (RequestInterface $request): bool {
        return str_contains($request->getUri()->getPath(), '/youtube/v3/videos')
            && str_contains((string) $request->getUri(), 'dQw4w9WgXcQ');
    });
});

it('tracks the number of api calls', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::list(),
    ]);

    $this->getJson('/api/videos/dQw4w9WgXcQ');

    YoutubeDataApi::assertSentCount(1);
});
