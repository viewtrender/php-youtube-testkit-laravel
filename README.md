# php-youtube-testkit-laravel

Laravel integration for mocking YouTube Data, Analytics, and Reporting APIs in tests.

[![Tests](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/viewtrender/php-youtube-testkit-laravel/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/viewtrender/php-youtube-testkit-laravel)](https://packagist.org/packages/viewtrender/php-youtube-testkit-laravel)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

Laravel service provider, facades, and automatic container swaps for [`viewtrender/php-youtube-testkit-core`](https://github.com/viewtrender/php-youtube-testkit-core). Supports three YouTube APIs:

- **YouTube Data API** — videos, channels, playlists, search, comments
- **YouTube Analytics API** — on-demand metrics queries
- **YouTube Reporting API** — bulk data exports and scheduled jobs

When you call `fake()` on any API facade, the service provider replaces the Google Service container binding with a fake instance — controllers that type-hint the service receive the fake automatically.

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

Register the real Google services in your `AppServiceProvider`:

```php
use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics;
use Google\Service\YouTubeReporting;

public function register(): void
{
    // Shared Google Client (configure once)
    $this->app->singleton(GoogleClient::class, function () {
        $client = new GoogleClient();
        $client->setApplicationName(config('services.youtube.application_name', 'My App'));
        $client->setDeveloperKey(config('services.youtube.api_key'));
        // For Analytics/Reporting, also set OAuth credentials
        return $client;
    });

    // YouTube Data API
    $this->app->singleton(YouTube::class, function ($app) {
        return new YouTube($app->make(GoogleClient::class));
    });

    // YouTube Analytics API
    $this->app->singleton(YouTubeAnalytics::class, function ($app) {
        return new YouTubeAnalytics($app->make(GoogleClient::class));
    });

    // YouTube Reporting API
    $this->app->singleton(YouTubeReporting::class, function ($app) {
        return new YouTubeReporting($app->make(GoogleClient::class));
    });
}
```

Controllers can then type-hint any service:

```php
use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics;

class DashboardController extends Controller
{
    public function index(YouTube $youtube, YouTubeAnalytics $analytics)
    {
        $videos = $youtube->videos->listVideos('snippet', ['chart' => 'mostPopular']);
        $stats = $analytics->reports->query([...]);
        
        return view('dashboard', compact('videos', 'stats'));
    }
}
```

---

## Testing with Pest

### Base Setup

Create a base test file or add to `tests/Pest.php`:

```php
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeReportingApi;

afterEach(function () {
    YoutubeDataApi::reset();
    YoutubeAnalyticsApi::reset();
    YoutubeReportingApi::reset();
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
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeReportingApi;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        YoutubeAnalyticsApi::reset();
        YoutubeReportingApi::reset();
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

## YouTube Analytics API

For on-demand metrics queries — dashboards, real-time stats, custom date ranges.

### Base Test Case (Analytics)

```php
<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Viewtrender\Youtube\YoutubeAnalyticsApi;

abstract class AnalyticsTestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        YoutubeAnalyticsApi::reset();
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

### Channel Analytics Tests

```php
<?php

namespace Tests\Feature;

use Tests\AnalyticsTestCase;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\YoutubeAnalyticsApi;

class ChannelAnalyticsTest extends AnalyticsTestCase
{
    public function test_fetches_channel_overview_metrics(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview([
                'views' => 500000,
                'estimatedMinutesWatched' => 1500000,
                'averageViewDuration' => 180,
                'subscribersGained' => 1000,
                'subscribersLost' => 50,
            ]),
        ]);

        $response = $this->getJson('/api/analytics/overview?startDate=2024-01-01&endDate=2024-01-31');

        $response->assertOk();
        $response->assertJsonPath('views', 500000);
        $response->assertJsonPath('subscribersGained', 1000);

        YoutubeAnalyticsApi::assertSentCount(1);
    }

    public function test_fetches_daily_metrics(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::dailyMetrics([
                ['day' => '2024-01-01', 'views' => 10000, 'estimatedMinutesWatched' => 30000],
                ['day' => '2024-01-02', 'views' => 12000, 'estimatedMinutesWatched' => 36000],
                ['day' => '2024-01-03', 'views' => 11000, 'estimatedMinutesWatched' => 33000],
            ]),
        ]);

        $response = $this->getJson('/api/analytics/daily');

        $response->assertOk();
        $response->assertJsonCount(3, 'rows');
    }

    public function test_fetches_top_videos(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::topVideos([
                ['video' => 'dQw4w9WgXcQ', 'views' => 50000, 'estimatedMinutesWatched' => 150000],
                ['video' => 'abc123xyz', 'views' => 30000, 'estimatedMinutesWatched' => 90000],
            ]),
        ]);

        $response = $this->getJson('/api/analytics/top-videos');

        $response->assertOk();
        $response->assertJsonPath('rows.0.0', 'dQw4w9WgXcQ');
    }

    public function test_fetches_traffic_sources(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::trafficSources([
                ['source' => 'RELATED_VIDEO', 'views' => 200000],
                ['source' => 'YT_SEARCH', 'views' => 150000],
                ['source' => 'EXT_URL', 'views' => 50000],
                ['source' => 'SUBSCRIBER', 'views' => 30000],
            ]),
        ]);

        $response = $this->getJson('/api/analytics/traffic-sources');

        $response->assertOk();
        $response->assertJsonCount(4, 'rows');
    }

    public function test_fetches_demographics(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::demographics([
                ['ageGroup' => 'age18-24', 'gender' => 'male', 'viewerPercentage' => 25.5],
                ['ageGroup' => 'age18-24', 'gender' => 'female', 'viewerPercentage' => 15.2],
                ['ageGroup' => 'age25-34', 'gender' => 'male', 'viewerPercentage' => 22.1],
                ['ageGroup' => 'age25-34', 'gender' => 'female', 'viewerPercentage' => 18.3],
            ]),
        ]);

        $response = $this->getJson('/api/analytics/demographics');

        $response->assertOk();
        $response->assertJsonPath('rows.0.2', 25.5);
    }

    public function test_fetches_geography(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::geography([
                ['country' => 'US', 'views' => 200000],
                ['country' => 'GB', 'views' => 80000],
                ['country' => 'CA', 'views' => 50000],
            ]),
        ]);

        $response = $this->getJson('/api/analytics/geography');

        $response->assertOk();
        $response->assertJsonPath('rows.0.0', 'US');
    }

    public function test_fetches_content_type_breakdown(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::videoTypes([
                ['video' => 'abc123', 'creatorContentType' => 'VIDEO_ON_DEMAND', 'views' => 180000],
                ['video' => 'def456', 'creatorContentType' => 'SHORTS', 'views' => 120000],
                ['video' => 'ghi789', 'creatorContentType' => 'LIVE_STREAM', 'views' => 50000],
            ]),
        ]);

        $response = $this->getJson('/api/analytics/content-types');

        $response->assertOk();
        $response->assertJsonCount(3, 'rows');
    }
}
```

---

## YouTube Reporting API

For bulk data exports — background jobs, historical data pipelines, scheduled reports.

**Workflow:** Create job → Poll for reports → Download CSV → Parse & upsert

### Base Test Case (Reporting)

```php
<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Viewtrender\Youtube\YoutubeReportingApi;

abstract class ReportingTestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        YoutubeReportingApi::reset();
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

### Reporting Job Tests

```php
<?php

namespace Tests\Feature;

use Tests\ReportingTestCase;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\ReportingMedia;
use Viewtrender\Youtube\YoutubeReportingApi;

class ReportingPipelineTest extends ReportingTestCase
{
    public function test_creates_reporting_job(): void
    {
        YoutubeReportingApi::fake([
            ReportingJob::create([
                'id' => 'job-123',
                'reportTypeId' => 'channel_basic_a2',
                'name' => 'Daily Channel Stats',
            ]),
        ]);

        $response = $this->postJson('/api/reporting/jobs', [
            'reportTypeId' => 'channel_basic_a2',
            'name' => 'Daily Channel Stats',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('id', 'job-123');

        YoutubeReportingApi::assertSentCount(1);
    }

    public function test_lists_reporting_jobs(): void
    {
        YoutubeReportingApi::fake([
            ReportingJob::list([
                ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2', 'name' => 'Daily Stats'],
                ['id' => 'job-2', 'reportTypeId' => 'channel_demographics_a1', 'name' => 'Demographics'],
                ['id' => 'job-3', 'reportTypeId' => 'channel_traffic_source_a2', 'name' => 'Traffic'],
            ]),
        ]);

        $response = $this->getJson('/api/reporting/jobs');

        $response->assertOk();
        $response->assertJsonCount(3, 'jobs');
        $response->assertJsonPath('jobs.0.reportTypeId', 'channel_basic_a2');

        YoutubeReportingApi::assertSentCount(1);
    }

    public function test_lists_available_reports(): void
    {
        YoutubeReportingApi::fake([
            ReportingReport::list([
                [
                    'id' => 'report-1',
                    'jobId' => 'job-123',
                    'startTime' => '2024-01-01T00:00:00Z',
                    'endTime' => '2024-01-02T00:00:00Z',
                    'createTime' => '2024-01-02T06:00:00Z',
                    'downloadUrl' => 'https://youtubereporting.googleapis.com/v1/media/report-1',
                ],
                [
                    'id' => 'report-2',
                    'jobId' => 'job-123',
                    'startTime' => '2024-01-02T00:00:00Z',
                    'endTime' => '2024-01-03T00:00:00Z',
                    'createTime' => '2024-01-03T06:00:00Z',
                    'downloadUrl' => 'https://youtubereporting.googleapis.com/v1/media/report-2',
                ],
            ]),
        ]);

        $response = $this->getJson('/api/reporting/jobs/job-123/reports');

        $response->assertOk();
        $response->assertJsonCount(2, 'reports');
    }

    public function test_downloads_report_csv(): void
    {
        $csvContent = "date,channel_id,views,watch_time_minutes,average_view_duration_seconds\n" .
                      "2024-01-01,UC123,10000,50000,300\n" .
                      "2024-01-02,UC123,12000,60000,300\n" .
                      "2024-01-03,UC123,11000,55000,300\n";

        YoutubeReportingApi::fake([
            ReportingMedia::download($csvContent),
        ]);

        $response = $this->get('/api/reporting/download/report-1');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv');

        YoutubeReportingApi::assertSentCount(1);
    }

    public function test_lists_report_types(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list([
                ['id' => 'channel_basic_a2', 'name' => 'Channel Basic'],
                ['id' => 'channel_demographics_a1', 'name' => 'Channel Demographics'],
                ['id' => 'channel_device_os_a2', 'name' => 'Channel Device/OS'],
                ['id' => 'channel_traffic_source_a2', 'name' => 'Channel Traffic Source'],
            ]),
        ]);

        $response = $this->getJson('/api/reporting/report-types');

        $response->assertOk();
        $response->assertJsonCount(4, 'reportTypes');
    }

    public function test_deletes_reporting_job(): void
    {
        YoutubeReportingApi::fake([
            ReportingJob::delete(),
        ]);

        $response = $this->deleteJson('/api/reporting/jobs/job-123');

        $response->assertNoContent();

        YoutubeReportingApi::assertSentCount(1);
    }
}
```

### Complete Pipeline Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingMedia;
use Viewtrender\Youtube\YoutubeReportingApi;

class ReportingSyncJobTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeReportingApi::reset();
        parent::tearDown();
    }

    public function test_full_reporting_pipeline(): void
    {
        // Queue responses for the entire pipeline
        YoutubeReportingApi::fake([
            // 1. List jobs to find our job
            ReportingJob::list([
                ['id' => 'job-123', 'reportTypeId' => 'channel_basic_a2'],
            ]),
            // 2. List available reports for the job
            ReportingReport::list([
                [
                    'id' => 'report-today',
                    'jobId' => 'job-123',
                    'startTime' => '2024-01-01T00:00:00Z',
                    'endTime' => '2024-01-02T00:00:00Z',
                    'downloadUrl' => 'https://youtubereporting.googleapis.com/v1/media/report-today',
                ],
            ]),
            // 3. Download the report CSV
            ReportingMedia::download(
                "date,channel_id,views,watch_time_minutes\n" .
                "2024-01-01,UC123,10000,50000\n"
            ),
        ]);

        // Dispatch the sync job
        $this->artisan('youtube:sync-reports')
            ->assertSuccessful();

        // Verify all three API calls were made
        YoutubeReportingApi::assertSentCount(3);

        // Verify data was stored
        $this->assertDatabaseHas('channel_daily_stats', [
            'date' => '2024-01-01',
            'views' => 10000,
        ]);
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
