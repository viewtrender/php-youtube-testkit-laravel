<?php

namespace App\Http\Controllers\Api;

use Google\Service\Exception;
use Google\Service\YouTube;

class ChannelController
{
    /**
     * @throws Exception
     */
    public function index(YouTube $youtube)
    {
        $ids = request()->query('ids', '');

        $response = $youtube->channels->listChannels('snippet,statistics', ['id' => $ids]);

        $channels = array_map(fn ($channel) => [
            'id' => $channel->getId(),
            'title' => $channel->getSnippet()->getTitle(),
            'description' => $channel->getSnippet()->getDescription(),
            'subscribers' => $channel->getStatistics()->getSubscriberCount(),
            'video_count' => $channel->getStatistics()->getVideoCount(),
        ], $response->getItems());

        return response()->json(['channels' => $channels]);
    }

    /**
     * @throws Exception
     */
    public function show(YouTube $youtube, string $id)
    {
        $response = $youtube->channels->listChannels('snippet,statistics', ['id' => $id]);

        if (empty($response->getItems())) {
            abort(404, 'Channel not found');
        }

        $channel = $response->getItems()[0];

        return response()->json([
            'id' => $channel->getId(),
            'title' => $channel->getSnippet()->getTitle(),
            'description' => $channel->getSnippet()->getDescription(),
            'subscribers' => $channel->getStatistics()->getSubscriberCount(),
            'video_count' => $channel->getStatistics()->getVideoCount(),
        ]);
    }
}
