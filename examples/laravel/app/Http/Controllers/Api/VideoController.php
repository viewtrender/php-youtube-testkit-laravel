<?php

namespace App\Http\Controllers\Api;

use Google\Service\Exception;
use Google\Service\YouTube;
use Illuminate\Http\Request;

class VideoController
{
    /**
     * @throws Exception
     */
    public function index(YouTube $youtube, Request $request)
    {
        $ids = $request->input('ids', '');

        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $ids]);

        $videos = array_map(fn($video) => [
            'id' => $video->getId(),
            'title' => $video->getSnippet()->getTitle(),
            'description' => $video->getSnippet()->getDescription(),
            'channel' => $video->getSnippet()->getChannelTitle(),
            'views' => $video->getStatistics()->getViewCount(),
            'likes' => $video->getStatistics()->getLikeCount(),
        ], $response->getItems());

        return response()->json(['videos' => $videos]);
    }

    /**
     * @throws Exception
     */
    public function show(YouTube $youtube, string $id)
    {
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $id]);

        if (empty($response->getItems())) {
            abort(404, 'Video not found');
        }

        $video = $response->getItems()[0];

        return response()->json([
            'id' => $video->getId(),
            'title' => $video->getSnippet()->getTitle(),
            'description' => $video->getSnippet()->getDescription(),
            'channel' => $video->getSnippet()->getChannelTitle(),
            'views' => $video->getStatistics()->getViewCount(),
            'likes' => $video->getStatistics()->getLikeCount(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function search(YouTube $youtube, Request $request)
    {
        $query = $request->input('q', '');
        $maxResults = $request->input('maxResults', 10);

        $response = $youtube->search->listSearch('snippet', [
            'q' => $query,
            'maxResults' => $maxResults,
            'type' => 'video',
        ]);

        $results = array_map(fn($item) => [
            'id' => $item->getId()->getVideoId(),
            'title' => $item->getSnippet()->getTitle(),
            'thumbnail' => $item->getSnippet()->getThumbnails()->getDefault()->getUrl(),
        ], $response->getItems());

        return response()->json(['results' => $results]);
    }
}
