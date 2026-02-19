# php-youtube-testkit-laravel

Laravel integration for mocking Google YouTube API responses in tests.

[![Tests](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/viewtrender/php-youtube-testkit-laravel)](https://packagist.org/packages/viewtrender/php-youtube-testkit-laravel)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

This package provides a Laravel service provider, facade, and automatic container swap for [`viewtrender/php-youtube-testkit-core`](https://github.com/viewtrender/php-youtube-testkit-core). When you call `YoutubeDataApi::fake()` in a test, the service provider replaces the `Google\Service\YouTube` container binding with a fake instance — controllers that type-hint `YouTube` receive the fake automatically.

## Installation

```bash
composer require --dev viewtrender/php-youtube-testkit-laravel
```

The service provider is auto-discovered. To publish the config file:

```bash
php artisan vendor:publish --tag=youtube-testkit-config
```

## Requirements

- PHP 8.3+
- Laravel 10, 11, or 12
- `google/apiclient` ^2.15

## Setup

Register the real `YouTube` service in your `AppServiceProvider`:

```php
use Google\Client as GoogleClient;
use Google\Service\YouTube;

public function register(): void
{
    $this->app->singleton(YouTube::class, function () {
        $client = new GoogleClient();
        $client->setApplicationName(config('services.youtube.application_name', 'My App'));
        $client->setDeveloperKey(config('services.youtube.api_key'));

        return new YouTube($client);
    });
}
```

Controllers can then type-hint `YouTube`:

```php
use Google\Service\YouTube;

class VideoController extends Controller
{
    public function show(YouTube $youtube, string $id)
    {
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => $id]);
        return response()->json($response->getItems()[0] ?? null);
    }
}
```

---

## Testing with Pest

### Base Setup

Create a base test file or add to `tests/Pest.php`:

```php
use Viewtrender\Youtube\YoutubeDataApi;

afterEach(function () {
    YoutubeDataApi::reset();
});
```

### Import Factories

```php
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeSubscriptions;
use Viewtrender\Youtube\Factories\YoutubeComments;
use Viewtrender\Youtube\Factories\YoutubeCommentThreads;
use Viewtrender\Youtube\Factories\YoutubeActivities;
use Viewtrender\Youtube\Factories\YoutubeCaptions;
use Viewtrender\Youtube\Factories\YoutubeChannelSections;
use Viewtrender\Youtube\Factories\YoutubeMembers;
use Viewtrender\Youtube\Factories\YoutubeMembershipsLevels;
use Viewtrender\Youtube\Factories\YoutubeI18nLanguages;
use Viewtrender\Youtube\Factories\YoutubeI18nRegions;
use Viewtrender\Youtube\Factories\YoutubeVideoCategories;
use Viewtrender\Youtube\Factories\YoutubeVideoAbuseReportReasons;
use Viewtrender\Youtube\Factories\YoutubeGuideCategories;
use Viewtrender\Youtube\Factories\YoutubeThumbnails;
use Viewtrender\Youtube\Factories\YoutubeWatermarks;
use Viewtrender\Youtube\YoutubeDataApi;
```

---

## Factory Examples — Pest

### Videos

```php
it('fetches video details', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::listWithVideos([
            [
                'id' => 'dQw4w9WgXcQ',
                'snippet' => [
                    'title' => 'Never Gonna Give You Up',
                    'description' => 'Official music video',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'channelTitle' => 'Rick Astley',
                    'publishedAt' => '2009-10-25T06:57:33Z',
                    'thumbnails' => [
                        'default' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg'],
                        'high' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'],
                    ],
                ],
                'statistics' => [
                    'viewCount' => '1500000000',
                    'likeCount' => '15000000',
                    'commentCount' => '3000000',
                ],
                'contentDetails' => [
                    'duration' => 'PT3M33S',
                    'dimension' => '2d',
                    'definition' => 'hd',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

    $response->assertOk()
        ->assertJsonPath('title', 'Never Gonna Give You Up')
        ->assertJsonPath('statistics.viewCount', '1500000000');
    
    YoutubeDataApi::assertListedVideos();
    YoutubeDataApi::assertSentCount(1);
});

it('handles video not found', function () {
    YoutubeDataApi::fake([
        YoutubeVideo::empty(),
    ]);

    $response = $this->getJson('/api/videos/nonexistent');

    $response->assertNotFound();
});
```

### Channels

```php
it('fetches channel details', function () {
    YoutubeDataApi::fake([
        YoutubeChannel::listWithChannels([
            [
                'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                'snippet' => [
                    'title' => 'Rick Astley',
                    'description' => 'Official channel',
                    'customUrl' => '@RickAstleyYT',
                    'publishedAt' => '2006-09-19T01:03:26Z',
                    'thumbnails' => [
                        'default' => ['url' => 'https://yt3.ggpht.com/example/default.jpg'],
                    ],
                    'country' => 'GB',
                ],
                'statistics' => [
                    'viewCount' => '2000000000',
                    'subscriberCount' => '4500000',
                    'videoCount' => '150',
                ],
                'brandingSettings' => [
                    'channel' => [
                        'title' => 'Rick Astley',
                        'keywords' => 'music pop 80s',
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channels/UCuAXFkgsw1L7xaCfnd5JJOw');

    $response->assertOk()
        ->assertJsonPath('snippet.title', 'Rick Astley')
        ->assertJsonPath('statistics.subscriberCount', '4500000');
    
    YoutubeDataApi::assertListedChannels();
});
```

### Playlists

```php
it('fetches user playlists', function () {
    YoutubeDataApi::fake([
        YoutubePlaylist::listWithPlaylists([
            [
                'id' => 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf',
                'snippet' => [
                    'title' => 'My Favorite Videos',
                    'description' => 'A collection of favorites',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'publishedAt' => '2020-01-15T12:00:00Z',
                    'thumbnails' => [
                        'default' => ['url' => 'https://i.ytimg.com/vi/example/default.jpg'],
                    ],
                ],
                'contentDetails' => [
                    'itemCount' => 25,
                ],
                'status' => [
                    'privacyStatus' => 'public',
                ],
            ],
            [
                'id' => 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efde',
                'snippet' => [
                    'title' => 'Watch Later',
                    'description' => 'Videos to watch',
                ],
                'contentDetails' => [
                    'itemCount' => 100,
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/playlists');

    $response->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.snippet.title', 'My Favorite Videos');
    
    YoutubeDataApi::assertListedPlaylists();
});
```

### Playlist Items

```php
it('fetches videos in a playlist', function () {
    YoutubeDataApi::fake([
        YoutubePlaylistItems::listWithPlaylistItems([
            [
                'id' => 'UExmWEZ...',
                'snippet' => [
                    'title' => 'First Video',
                    'description' => 'Description of first video',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'playlistId' => 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf',
                    'position' => 0,
                    'resourceId' => [
                        'kind' => 'youtube#video',
                        'videoId' => 'dQw4w9WgXcQ',
                    ],
                    'thumbnails' => [
                        'default' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg'],
                    ],
                ],
                'contentDetails' => [
                    'videoId' => 'dQw4w9WgXcQ',
                    'videoPublishedAt' => '2009-10-25T06:57:33Z',
                ],
            ],
            [
                'snippet' => [
                    'title' => 'Second Video',
                    'position' => 1,
                    'resourceId' => [
                        'kind' => 'youtube#video',
                        'videoId' => 'abc123xyz',
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/playlists/PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf/items');

    $response->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.snippet.position', 0);
});
```

### Search Results

```php
it('searches for videos', function () {
    YoutubeDataApi::fake([
        YoutubeSearchResult::listWithResults([
            [
                'id' => [
                    'kind' => 'youtube#video',
                    'videoId' => 'dQw4w9WgXcQ',
                ],
                'snippet' => [
                    'title' => 'Never Gonna Give You Up',
                    'description' => 'Official music video',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'channelTitle' => 'Rick Astley',
                    'publishedAt' => '2009-10-25T06:57:33Z',
                    'liveBroadcastContent' => 'none',
                ],
            ],
            [
                'id' => [
                    'kind' => 'youtube#channel',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                ],
                'snippet' => [
                    'title' => 'Rick Astley',
                    'description' => 'Official channel',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/search?q=rick+astley');

    $response->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.id.videoId', 'dQw4w9WgXcQ');
    
    YoutubeDataApi::assertSearched();
});
```

### Subscriptions

```php
it('fetches channel subscriptions', function () {
    YoutubeDataApi::fake([
        YoutubeSubscriptions::listWithSubscriptions([
            [
                'id' => 'subscription123',
                'snippet' => [
                    'title' => 'PewDiePie',
                    'description' => 'Gaming and entertainment',
                    'channelId' => 'UC-lHJZR3Gqxm24_Vd_AJ5Yw',
                    'resourceId' => [
                        'kind' => 'youtube#channel',
                        'channelId' => 'UC-lHJZR3Gqxm24_Vd_AJ5Yw',
                    ],
                    'thumbnails' => [
                        'default' => ['url' => 'https://yt3.ggpht.com/pewdiepie/default.jpg'],
                    ],
                ],
                'contentDetails' => [
                    'totalItemCount' => 4500,
                    'newItemCount' => 3,
                ],
            ],
            [
                'snippet' => [
                    'title' => 'MrBeast',
                    'resourceId' => [
                        'kind' => 'youtube#channel',
                        'channelId' => 'UCX6OQ3DkcsbYNE6H8uQQuVA',
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/subscriptions');

    $response->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.snippet.title', 'PewDiePie');
});
```

### Comments

```php
it('fetches video comments', function () {
    YoutubeDataApi::fake([
        YoutubeComments::listWithComments([
            [
                'id' => 'comment123',
                'snippet' => [
                    'videoId' => 'dQw4w9WgXcQ',
                    'textDisplay' => 'This song is a masterpiece!',
                    'textOriginal' => 'This song is a masterpiece!',
                    'authorDisplayName' => 'MusicFan123',
                    'authorChannelId' => ['value' => 'UCxxx'],
                    'authorProfileImageUrl' => 'https://yt3.ggpht.com/user/default.jpg',
                    'likeCount' => 1500,
                    'publishedAt' => '2023-01-15T10:30:00Z',
                    'updatedAt' => '2023-01-15T10:30:00Z',
                ],
            ],
            [
                'snippet' => [
                    'textDisplay' => 'Classic!',
                    'authorDisplayName' => 'RetroLover',
                    'likeCount' => 500,
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ/comments');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.textDisplay', 'This song is a masterpiece!');
});
```

### Comment Threads

```php
it('fetches comment threads with replies', function () {
    YoutubeDataApi::fake([
        YoutubeCommentThreads::listWithCommentThreads([
            [
                'id' => 'thread123',
                'snippet' => [
                    'videoId' => 'dQw4w9WgXcQ',
                    'topLevelComment' => [
                        'id' => 'comment123',
                        'snippet' => [
                            'textDisplay' => 'Best song ever!',
                            'authorDisplayName' => 'TopCommenter',
                            'likeCount' => 5000,
                        ],
                    ],
                    'canReply' => true,
                    'totalReplyCount' => 50,
                    'isPublic' => true,
                ],
                'replies' => [
                    'comments' => [
                        [
                            'snippet' => [
                                'textDisplay' => 'I agree!',
                                'authorDisplayName' => 'Replier1',
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ/comment-threads');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.totalReplyCount', 50);
});
```

### Activities

```php
it('fetches channel activity feed', function () {
    YoutubeDataApi::fake([
        YoutubeActivities::listWithActivities([
            [
                'id' => 'activity123',
                'snippet' => [
                    'title' => 'Uploaded: New Music Video',
                    'description' => 'Check out my new video',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'channelTitle' => 'Rick Astley',
                    'type' => 'upload',
                    'publishedAt' => '2024-01-15T12:00:00Z',
                    'thumbnails' => [
                        'default' => ['url' => 'https://i.ytimg.com/vi/newvideo/default.jpg'],
                    ],
                ],
                'contentDetails' => [
                    'upload' => [
                        'videoId' => 'newvideo123',
                    ],
                ],
            ],
            [
                'snippet' => [
                    'title' => 'Liked: Amazing Cover',
                    'type' => 'like',
                ],
                'contentDetails' => [
                    'like' => [
                        'resourceId' => [
                            'kind' => 'youtube#video',
                            'videoId' => 'cover123',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channels/UCuAXFkgsw1L7xaCfnd5JJOw/activities');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.type', 'upload');
});
```

### Captions

```php
it('fetches video captions', function () {
    YoutubeDataApi::fake([
        YoutubeCaptions::listWithCaptions([
            [
                'id' => 'caption123',
                'snippet' => [
                    'videoId' => 'dQw4w9WgXcQ',
                    'language' => 'en',
                    'name' => 'English',
                    'audioTrackType' => 'primary',
                    'trackKind' => 'standard',
                    'isDraft' => false,
                    'isAutoSynced' => false,
                    'isCC' => false,
                    'status' => 'serving',
                ],
            ],
            [
                'snippet' => [
                    'videoId' => 'dQw4w9WgXcQ',
                    'language' => 'es',
                    'name' => 'Spanish',
                    'trackKind' => 'standard',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/videos/dQw4w9WgXcQ/captions');

    $response->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.snippet.language', 'en');
});
```

### Channel Sections

```php
it('fetches channel sections', function () {
    YoutubeDataApi::fake([
        YoutubeChannelSections::listWithChannelSections([
            [
                'id' => 'section123',
                'snippet' => [
                    'type' => 'singlePlaylist',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'title' => 'Popular Uploads',
                    'position' => 0,
                ],
                'contentDetails' => [
                    'playlists' => ['PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf'],
                ],
            ],
            [
                'snippet' => [
                    'type' => 'recentActivity',
                    'title' => 'Recent Activity',
                    'position' => 1,
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channels/UCuAXFkgsw1L7xaCfnd5JJOw/sections');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.type', 'singlePlaylist');
});
```

### Members (OAuth Required)

```php
it('fetches channel members', function () {
    YoutubeDataApi::fake([
        YoutubeMembers::listWithMembers([
            [
                'id' => 'member123',
                'snippet' => [
                    'creatorChannelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'memberDetails' => [
                        'channelId' => 'UCfan123',
                        'channelUrl' => 'https://youtube.com/channel/UCfan123',
                        'displayName' => 'SuperFan',
                        'profileImageUrl' => 'https://yt3.ggpht.com/fan/default.jpg',
                    ],
                    'membershipsDetails' => [
                        'highestAccessibleLevel' => 'level1',
                        'highestAccessibleLevelDisplayName' => 'Bronze Member',
                        'memberSince' => '2023-06-01T00:00:00Z',
                        'memberTotalDurationMonths' => 8,
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channel/members');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.memberDetails.displayName', 'SuperFan');
});
```

### Membership Levels (OAuth Required)

```php
it('fetches membership levels', function () {
    YoutubeDataApi::fake([
        YoutubeMembershipsLevels::listWithLevels([
            [
                'id' => 'level1',
                'snippet' => [
                    'creatorChannelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'levelDetails' => [
                        'displayName' => 'Bronze Member',
                    ],
                ],
            ],
            [
                'id' => 'level2',
                'snippet' => [
                    'levelDetails' => [
                        'displayName' => 'Silver Member',
                    ],
                ],
            ],
            [
                'id' => 'level3',
                'snippet' => [
                    'levelDetails' => [
                        'displayName' => 'Gold Member',
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/channel/membership-levels');

    $response->assertOk()
        ->assertJsonCount(3, 'items');
});
```

### I18n Languages

```php
it('fetches supported languages', function () {
    YoutubeDataApi::fake([
        YoutubeI18nLanguages::listWithLanguages([
            [
                'id' => 'en',
                'snippet' => [
                    'hl' => 'en',
                    'name' => 'English',
                ],
            ],
            [
                'id' => 'es',
                'snippet' => [
                    'hl' => 'es',
                    'name' => 'Spanish',
                ],
            ],
            [
                'id' => 'ja',
                'snippet' => [
                    'hl' => 'ja',
                    'name' => 'Japanese',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/languages');

    $response->assertOk()
        ->assertJsonCount(3, 'items')
        ->assertJsonPath('items.0.snippet.name', 'English');
});
```

### I18n Regions

```php
it('fetches supported regions', function () {
    YoutubeDataApi::fake([
        YoutubeI18nRegions::listWithRegions([
            [
                'id' => 'US',
                'snippet' => [
                    'gl' => 'US',
                    'name' => 'United States',
                ],
            ],
            [
                'id' => 'GB',
                'snippet' => [
                    'gl' => 'GB',
                    'name' => 'United Kingdom',
                ],
            ],
            [
                'id' => 'JP',
                'snippet' => [
                    'gl' => 'JP',
                    'name' => 'Japan',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/regions');

    $response->assertOk()
        ->assertJsonCount(3, 'items')
        ->assertJsonPath('items.0.snippet.name', 'United States');
});
```

### Video Categories

```php
it('fetches video categories', function () {
    YoutubeDataApi::fake([
        YoutubeVideoCategories::listWithVideoCategories([
            [
                'id' => '10',
                'snippet' => [
                    'channelId' => 'UCBR8-60-B28hp2BmDPdntcQ',
                    'title' => 'Music',
                    'assignable' => true,
                ],
            ],
            [
                'id' => '20',
                'snippet' => [
                    'title' => 'Gaming',
                    'assignable' => true,
                ],
            ],
            [
                'id' => '22',
                'snippet' => [
                    'title' => 'People & Blogs',
                    'assignable' => true,
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/video-categories?regionCode=US');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.title', 'Music');
});
```

### Video Abuse Report Reasons

```php
it('fetches abuse report reasons', function () {
    YoutubeDataApi::fake([
        YoutubeVideoAbuseReportReasons::listWithReasons([
            [
                'id' => 'S',
                'snippet' => [
                    'label' => 'Spam or misleading',
                    'secondaryReasons' => [
                        ['id' => 'S.1', 'label' => 'Mass advertising'],
                        ['id' => 'S.2', 'label' => 'Misleading thumbnail'],
                    ],
                ],
            ],
            [
                'id' => 'V',
                'snippet' => [
                    'label' => 'Violent or repulsive content',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/abuse-report-reasons');

    $response->assertOk()
        ->assertJsonPath('items.0.snippet.label', 'Spam or misleading');
});
```

### Thumbnails (Write-Only)

```php
it('uploads a video thumbnail', function () {
    YoutubeDataApi::fake([
        YoutubeThumbnails::setWithThumbnail([
            'default' => [
                'url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg',
                'width' => 120,
                'height' => 90,
            ],
            'medium' => [
                'url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
                'width' => 320,
                'height' => 180,
            ],
            'high' => [
                'url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
                'width' => 480,
                'height' => 360,
            ],
        ]),
    ]);

    $response = $this->postJson('/api/videos/dQw4w9WgXcQ/thumbnail', [
        'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg'),
    ]);

    $response->assertOk()
        ->assertJsonPath('items.0.default.url', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg');
});
```

### Watermarks (Write-Only)

```php
it('sets channel watermark', function () {
    YoutubeDataApi::fake([
        YoutubeWatermarks::setWithWatermark([
            'timing' => [
                'type' => 'offsetFromStart',
                'offsetMs' => 15000,
                'durationMs' => 0,
            ],
            'position' => [
                'type' => 'corner',
                'cornerPosition' => 'topRight',
            ],
            'imageUrl' => 'https://example.com/watermark.png',
            'imageBytes' => 'base64data...',
        ]),
    ]);

    $response = $this->postJson('/api/channel/watermark', [
        'image' => UploadedFile::fake()->image('watermark.png'),
    ]);

    $response->assertOk();
});
```

---

## Testing with PHPUnit

### Base Test Case

```php
<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Viewtrender\Youtube\YoutubeDataApi;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }
    
    protected function getPackageProviders($app): array
    {
        return [
            \Viewtrender\Youtube\Laravel\YoutubeDataApiServiceProvider::class,
        ];
    }
}
```

### Video Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\YoutubeDataApi;

class VideoControllerTest extends TestCase
{
    public function test_show_returns_video_details(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                [
                    'id' => 'dQw4w9WgXcQ',
                    'snippet' => [
                        'title' => 'Never Gonna Give You Up',
                        'description' => 'Official music video',
                        'channelTitle' => 'Rick Astley',
                    ],
                    'statistics' => [
                        'viewCount' => '1500000000',
                        'likeCount' => '15000000',
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/videos/dQw4w9WgXcQ');

        $response->assertOk();
        $response->assertJsonPath('title', 'Never Gonna Give You Up');
        $response->assertJsonPath('statistics.viewCount', '1500000000');
        
        YoutubeDataApi::assertListedVideos();
    }

    public function test_show_returns_404_when_video_not_found(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::empty(),
        ]);

        $response = $this->getJson('/api/videos/nonexistent');

        $response->assertNotFound();
    }

    public function test_index_returns_multiple_videos(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                ['id' => 'video1', 'snippet' => ['title' => 'First Video']],
                ['id' => 'video2', 'snippet' => ['title' => 'Second Video']],
                ['id' => 'video3', 'snippet' => ['title' => 'Third Video']],
            ]),
        ]);

        $response = $this->getJson('/api/videos?ids=video1,video2,video3');

        $response->assertOk();
        $response->assertJsonCount(3, 'items');
    }
}
```

### Channel Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\YoutubeDataApi;

class ChannelControllerTest extends TestCase
{
    public function test_show_returns_channel_details(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::listWithChannels([
                [
                    'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'snippet' => [
                        'title' => 'Rick Astley',
                        'customUrl' => '@RickAstleyYT',
                        'country' => 'GB',
                    ],
                    'statistics' => [
                        'subscriberCount' => '4500000',
                        'videoCount' => '150',
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/channels/UCuAXFkgsw1L7xaCfnd5JJOw');

        $response->assertOk();
        $response->assertJsonPath('snippet.title', 'Rick Astley');
        
        YoutubeDataApi::assertListedChannels();
    }
}
```

### Search Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\YoutubeDataApi;

class SearchControllerTest extends TestCase
{
    public function test_search_returns_results(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::listWithResults([
                [
                    'id' => ['kind' => 'youtube#video', 'videoId' => 'abc123'],
                    'snippet' => ['title' => 'Search Result 1'],
                ],
                [
                    'id' => ['kind' => 'youtube#video', 'videoId' => 'def456'],
                    'snippet' => ['title' => 'Search Result 2'],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/search?q=test+query');

        $response->assertOk();
        $response->assertJsonCount(2, 'items');
        
        YoutubeDataApi::assertSearched();
    }

    public function test_search_returns_empty_for_no_results(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::empty(),
        ]);

        $response = $this->getJson('/api/search?q=gibberish+nonsense+xyz');

        $response->assertOk();
        $response->assertJsonCount(0, 'items');
    }
}
```

### Subscription Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeSubscriptions;
use Viewtrender\Youtube\YoutubeDataApi;

class SubscriptionControllerTest extends TestCase
{
    public function test_index_returns_user_subscriptions(): void
    {
        YoutubeDataApi::fake([
            YoutubeSubscriptions::listWithSubscriptions([
                [
                    'snippet' => [
                        'title' => 'PewDiePie',
                        'resourceId' => [
                            'channelId' => 'UC-lHJZR3Gqxm24_Vd_AJ5Yw',
                        ],
                    ],
                ],
                [
                    'snippet' => [
                        'title' => 'MrBeast',
                        'resourceId' => [
                            'channelId' => 'UCX6OQ3DkcsbYNE6H8uQQuVA',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/subscriptions');

        $response->assertOk();
        $response->assertJsonCount(2, 'items');
    }
}
```

### Complete Feature Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Viewtrender\Youtube\YoutubeDataApi;

class YouTubeIntegrationTest extends TestCase
{
    public function test_dashboard_loads_channel_and_recent_videos(): void
    {
        // Queue multiple responses for a complex page load
        YoutubeDataApi::fake([
            // First call: Get channel details
            YoutubeChannel::listWithChannels([
                [
                    'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'snippet' => ['title' => 'My Channel'],
                    'statistics' => ['subscriberCount' => '10000'],
                ],
            ]),
            // Second call: Get recent uploads playlist
            YoutubePlaylist::listWithPlaylists([
                [
                    'id' => 'UUuAXFkgsw1L7xaCfnd5JJOw',
                    'snippet' => ['title' => 'Uploads'],
                ],
            ]),
            // Third call: Get playlist items
            YoutubePlaylistItems::listWithPlaylistItems([
                ['snippet' => ['title' => 'Latest Video', 'position' => 0]],
                ['snippet' => ['title' => 'Previous Video', 'position' => 1]],
            ]),
            // Fourth call: Get video statistics
            YoutubeVideo::listWithVideos([
                ['id' => 'vid1', 'statistics' => ['viewCount' => '5000']],
                ['id' => 'vid2', 'statistics' => ['viewCount' => '3000']],
            ]),
        ]);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('My Channel');
        $response->assertSee('Latest Video');
        
        YoutubeDataApi::assertSentCount(4);
    }

    public function test_handles_api_quota_exceeded(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::quotaExceeded('Daily quota exhausted'),
        ]);

        $response = $this->getJson('/api/videos/any');

        $response->assertStatus(500);
        $response->assertJsonPath('error', 'YouTube API quota exceeded');
    }

    public function test_handles_unauthorized_access(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::unauthorized('Invalid API key'),
        ]);

        $response = $this->getJson('/api/videos/any');

        $response->assertStatus(401);
    }

    public function test_prevents_stray_requests(): void
    {
        $fake = YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);
        $fake->preventStrayRequests();

        // First request succeeds
        $this->getJson('/api/videos/abc');

        // Second request throws because no more responses queued
        $this->expectException(\Viewtrender\Youtube\Exceptions\StrayRequestException::class);
        $this->getJson('/api/videos/xyz');
    }
}
```

---

## Error Responses

Simulate API errors in your tests:

```php
use Viewtrender\Youtube\Responses\ErrorResponse;

// 404 Not Found
YoutubeDataApi::fake([ErrorResponse::notFound()]);
YoutubeDataApi::fake([ErrorResponse::notFound('Video not found')]);

// 403 Forbidden
YoutubeDataApi::fake([ErrorResponse::forbidden()]);
YoutubeDataApi::fake([ErrorResponse::forbidden('Access denied')]);

// 401 Unauthorized
YoutubeDataApi::fake([ErrorResponse::unauthorized()]);
YoutubeDataApi::fake([ErrorResponse::unauthorized('Invalid credentials')]);

// 403 Quota Exceeded
YoutubeDataApi::fake([ErrorResponse::quotaExceeded()]);
YoutubeDataApi::fake([ErrorResponse::quotaExceeded('Daily limit reached')]);

// 400 Bad Request
YoutubeDataApi::fake([ErrorResponse::badRequest()]);
YoutubeDataApi::fake([ErrorResponse::badRequest('Invalid parameter')]);
```

---

## Assertions

```php
// Assert a request was sent matching the callback
YoutubeDataApi::assertSent(function ($request) {
    return str_contains($request->getUri()->getPath(), '/videos');
});

// Assert no request matched the callback
YoutubeDataApi::assertNotSent(function ($request) {
    return str_contains($request->getUri()->getPath(), '/channels');
});

// Assert no requests were sent
YoutubeDataApi::assertNothingSent();

// Assert exact number of requests
YoutubeDataApi::assertSentCount(3);

// Endpoint-specific assertions
YoutubeDataApi::assertListedVideos();
YoutubeDataApi::assertListedChannels();
YoutubeDataApi::assertListedPlaylists();
YoutubeDataApi::assertSearched();
```

---

## Configuration

```php
// config/youtube-testkit.php
return [
    // Custom fixture path (null = package defaults)
    'fixtures_path' => null,

    // Throw exception on unqueued requests
    'prevent_stray_requests' => false,
];
```

---

## License

MIT
