<?php

namespace Tests\Feature\Api;

use Psr\Http\Message\RequestInterface;
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

    public function test_index_returns_single_video(): void
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

        $response = $this->getJson('/api/videos?ids=dQw4w9WgXcQ');

        $response->assertOk();
        $response->assertJsonCount(1, 'videos');
        $response->assertJsonPath('videos.0.title', 'Never Gonna Give You Up');
        $response->assertJsonPath('videos.0.views', '1500000000');
    }

    public function test_index_returns_multiple_videos(): void
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

        $response = $this->getJson('/api/videos?ids=vid_1,vid_2,vid_3');

        $response->assertOk();
        $response->assertJsonCount(3, 'videos');
        $response->assertJsonPath('videos.0.title', 'First Video');
        $response->assertJsonPath('videos.1.title', 'Second Video');
        $response->assertJsonPath('videos.2.title', 'Third Video');

        YoutubeDataApi::assertListedVideos();
    }

    public function test_index_returns_empty_array_when_no_videos(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::empty(),
        ]);

        $response = $this->getJson('/api/videos?ids=nonexistent');

        $response->assertOk();
        $response->assertJsonCount(0, 'videos');
    }

    public function test_show_returns_video_details(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

        $response->assertOk();
        $response->assertJsonPath('title', 'Fake Video Title');
        $response->assertJsonStructure(['id', 'title', 'description', 'channel', 'views', 'likes']);

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

        $response = $this->getJson('/api/videos/abc123');

        $response->assertOk();
        $response->assertJsonPath('title', 'My Custom Video');
        $response->assertJsonPath('views', '999');
    }

    public function test_show_returns_404_when_video_not_found(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::empty(),
        ]);

        $response = $this->getJson('/api/videos/nonexistent');

        $response->assertNotFound();
    }

    public function test_show_handles_api_error(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::quotaExceeded(),
        ]);

        $response = $this->getJson('/api/videos/any');

        $response->assertStatus(500);
    }

    public function test_search_returns_results(): void
    {
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
    }

    public function test_search_returns_empty_when_no_matches(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::empty(),
        ]);

        $response = $this->getJson('/api/videos/search?q=noresults');

        $response->assertOk();
        $response->assertJsonCount(0, 'results');
    }

    public function test_can_assert_exact_request_details(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $this->getJson('/api/videos/dQw4w9WgXcQ');

        YoutubeDataApi::assertSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), '/youtube/v3/videos')
                && str_contains((string) $request->getUri(), 'dQw4w9WgXcQ');
        });
    }

    public function test_multiple_api_calls_in_one_request(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $this->getJson('/api/videos/dQw4w9WgXcQ');

        YoutubeDataApi::assertSentCount(1);
    }
}
