<?php

namespace Tests\Feature\Web;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Viewtrender\Youtube\YoutubeDataApi;

class VideoControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_index_renders_single_video(): void
    {
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
    }

    public function test_index_renders_multiple_videos(): void
    {
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
    }

    public function test_index_renders_empty_when_no_videos(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::empty(),
        ]);

        $response = $this->get('/videos?ids=nonexistent');

        $response->assertOk();
        $response->assertViewHas('videos', fn ($videos) => count($videos) === 0);
    }

    public function test_show_renders_video_view(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $response = $this->get('/videos/dQw4w9WgXcQ');

        $response->assertOk();
        $response->assertViewIs('videos.show');
        $response->assertViewHas('video');
        $response->assertViewHas('video.title', 'Fake Video Title');

        YoutubeDataApi::assertListedVideos();
    }

    public function test_show_with_custom_video_data(): void
    {
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
    }

    public function test_show_returns_404_when_video_not_found(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::empty(),
        ]);

        $response = $this->get('/videos/nonexistent');

        $response->assertNotFound();
    }

    public function test_show_handles_api_error(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::quotaExceeded(),
        ]);

        $response = $this->get('/videos/any');

        $response->assertStatus(500);
    }

    public function test_search_renders_results_view(): void
    {
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
    }

    public function test_search_renders_empty_results(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::empty(),
        ]);

        $response = $this->get('/videos/search?q=noresults');

        $response->assertOk();
        $response->assertViewHas('results', fn ($results) => count($results) === 0);
    }
}
