<?php

namespace App\Http\Controllers;

use Google\Service\Exception;
use Google\Service\YouTube;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChannelController
{
    /**
     * @throws Exception
     */
    public function index(YouTube $youtube, Request $request): View
    {
        $ids = $request->input('ids', '');

        $response = $youtube->channels->listChannels('snippet,statistics', ['id' => $ids]);

        $channels = array_map(fn($channel) => [
            'id' => $channel->getId(),
            'title' => $channel->getSnippet()->getTitle(),
            'description' => $channel->getSnippet()->getDescription(),
            'subscribers' => $channel->getStatistics()->getSubscriberCount(),
            'video_count' => $channel->getStatistics()->getVideoCount(),
        ], $response->getItems());

        return view('channels.index', [
            'channels' => $channels,
        ]);
    }

    /**
     * @throws Exception
     */
    public function show(YouTube $youtube, string $id): View
    {
        $response = $youtube->channels->listChannels('snippet,statistics', ['id' => $id]);

        if (empty($response->getItems())) {
            abort(404, 'Channel not found');
        }

        $channel = $response->getItems()[0];

        return view('channels.show', [
            'channel' => [
                'id' => $channel->getId(),
                'title' => $channel->getSnippet()->getTitle(),
                'description' => $channel->getSnippet()->getDescription(),
                'subscribers' => $channel->getStatistics()->getSubscriberCount(),
                'video_count' => $channel->getStatistics()->getVideoCount(),
            ],
        ]);
    }
}
