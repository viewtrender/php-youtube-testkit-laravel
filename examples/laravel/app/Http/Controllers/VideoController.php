<?php

namespace App\Http\Controllers;

use Google\Service\YouTube;
use Illuminate\View\View;

class VideoController
{
    public function index(YouTube $youtube): View
    {
        $ids = request()->query('ids', '');

        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $ids]);

        $videos = array_map(fn ($video) => [
            'id' => $video->getId(),
            'title' => $video->getSnippet()->getTitle(),
            'description' => $video->getSnippet()->getDescription(),
            'channel' => $video->getSnippet()->getChannelTitle(),
            'views' => $video->getStatistics()->getViewCount(),
            'likes' => $video->getStatistics()->getLikeCount(),
        ], $response->getItems());

        return view('videos.index', [
            'videos' => $videos,
        ]);
    }

    public function show(YouTube $youtube, string $id): View
    {
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $id]);

        if (empty($response->getItems())) {
            abort(404, 'Video not found');
        }

        $video = $response->getItems()[0];

        return view('videos.show', [
            'video' => [
                'id' => $video->getId(),
                'title' => $video->getSnippet()->getTitle(),
                'description' => $video->getSnippet()->getDescription(),
                'channel' => $video->getSnippet()->getChannelTitle(),
                'views' => $video->getStatistics()->getViewCount(),
                'likes' => $video->getStatistics()->getLikeCount(),
            ],
        ]);
    }

    public function search(YouTube $youtube): View
    {
        $query = request()->query('q', '');

        $response = $youtube->search->listSearch('snippet', [
            'q' => $query,
            'maxResults' => 10,
            'type' => 'video',
        ]);

        $results = array_map(fn ($item) => [
            'id' => $item->getId()->getVideoId(),
            'title' => $item->getSnippet()->getTitle(),
            'thumbnail' => $item->getSnippet()->getThumbnails()->getDefault()->getUrl(),
        ], $response->getItems());

        return view('videos.search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
