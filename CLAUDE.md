# CLAUDE.md — php-youtube-testkit-laravel

## Project Overview

Laravel integration package for `viewtrender/php-youtube-testkit-core`. Provides service providers and facades that auto-swap Google API container bindings with fake implementations when `fake()` is called in tests. Controllers that type-hint the Google services receive the fake automatically.

Package: `viewtrender/php-youtube-testkit-laravel`
Supports: Laravel 10, 11, 12 | PHP 8.3+
Core dependency: `viewtrender/php-youtube-testkit-core` ^0.5

## Development Commands

```bash
composer install                          # Install dependencies
vendor/bin/phpunit                        # Run all tests
vendor/bin/phpunit tests/ContainerSwapTest.php  # Run a single test file
vendor/bin/phpunit --filter test_fake_auto_swaps  # Run a single test method
```

## Architecture

### Source files (`src/`, namespace `Viewtrender\Youtube\Laravel`)

- **`YoutubeDataApiServiceProvider`** — Registers the `youtube-testkit` config and container swap hooks for all three APIs. When `fake()` is called on any facade, the callback binds the fake instance into the Laravel container.

### Facades

| Facade | Swaps | Use Case |
|--------|-------|----------|
| `YoutubeDataApi` | `Google\Service\YouTube` | Videos, channels, playlists, search, comments |
| `YoutubeAnalyticsApi` | `Google\Service\YouTubeAnalytics` | On-demand metrics queries |
| `YoutubeReportingApi` | `Google\Service\YouTubeReporting` | Bulk data exports, CSV pipelines |

### Config (`config/youtube-testkit.php`)

- `fixtures_path` — Custom fixture location (null = package defaults)
- `prevent_stray_requests` — Throw on unmatched API calls

### How the container swap works

1. `YoutubeDataApiServiceProvider::register()` calls `registerContainerSwap()` on each API class with a closure.
2. When test code calls `YoutubeDataApi::fake([...])` (or Analytics/Reporting), the core package invokes the registered closure.
3. The closure calls `$this->app->instance(ServiceClass::class, FakeInstance)`, replacing the container binding.
4. Controllers that inject the service via the container now receive the fake instance.

## Boost Integration

This package includes AI guidelines for Laravel Boost:

- `resources/boost/guidelines/core.blade.php` — Entry point, directs to per-API skills
- `resources/boost/skills/youtube-data-api/SKILL.md` — Data API factories + pagination
- `resources/boost/skills/youtube-analytics-api/SKILL.md` — Analytics query factories
- `resources/boost/skills/youtube-reporting-api/SKILL.md` — Reporting pipeline factories

## Testing

Tests use **Orchestra Testbench** (`Orchestra\Testbench\TestCase`) to boot a minimal Laravel app.

### Test files (`tests/`, namespace `Viewtrender\Youtube\Laravel\Tests`)

- `ServiceProviderTest` — Provider registration, config merging, container binding swap
- `FacadeTest` — Facade resolution, fake data assertions
- `ContainerSwapTest` — End-to-end route tests, prevent stray requests, reset behavior

### Important pattern: always reset in tearDown

Every test class must call reset on all used facades in `tearDown()` before `parent::tearDown()`:

```php
protected function tearDown(): void
{
    YoutubeDataApi::reset();
    YoutubeAnalyticsApi::reset();
    YoutubeReportingApi::reset();
    parent::tearDown();
}
```

## Code Conventions

- All PHP files use `declare(strict_types=1)`
- PSR-4 autoloading: `Viewtrender\Youtube\Laravel\` maps to `src/`
- Auto-discovery via `composer.json` `extra.laravel` — no manual provider/alias registration needed in consuming apps
