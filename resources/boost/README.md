# Laravel Boost AI Resources

AI guidelines for Laravel Boost, Laravel's official AI development assistant.

## Guidelines

| File | Description |
|------|-------------|
| `youtube-data-api.md` | Testing YouTube Data API (videos, channels, playlists, search) |
| `youtube-analytics-api.md` | Testing Analytics API (on-demand metrics queries) |
| `youtube-reporting-api.md` | Testing Reporting API (bulk data exports, CSV processing) |

## Usage

When you run `php artisan boost:install` or `php artisan boost:update` in a project that uses this package, Laravel Boost will append these guidelines to your `CLAUDE.md` or `AGENTS.md` file.

This gives AI assistants the context they need to write clean YouTube API test implementations using the testkit factories.
