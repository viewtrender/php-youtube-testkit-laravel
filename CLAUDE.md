# CLAUDE.md — php-youtube-testkit-laravel

## Project Overview

Laravel integration package for `viewtrender/php-youtube-testkit-core`. Provides a service provider and facade that auto-swap the `Google\Service\YouTube` container binding with a fake implementation when `YoutubeDataApi::fake()` is called in tests. This lets controllers that type-hint `YouTube` in their signatures receive the fake without any manual binding.

Package: `viewtrender/php-youtube-testkit-laravel` (v0.1.0)
Supports: Laravel 10, 11, 12 | PHP 8.3+

## Development Commands

```bash
composer install                          # Install dependencies
vendor/bin/phpunit                        # Run all tests
vendor/bin/phpunit tests/ContainerSwapTest.php  # Run a single test file
vendor/bin/phpunit --filter test_fake_auto_swaps  # Run a single test method
```

## Architecture

### Source files (`src/`, namespace `Viewtrender\Youtube\Laravel`)

- **`YoutubeDataApiServiceProvider`** — Registers the `youtube-testkit` config and a container swap hook via `YoutubeDataApi::registerContainerSwap()`. When `fake()` is called, the callback binds the fake `YouTube` instance into the Laravel container and optionally enables `preventStrayRequests`.
- **`Facades/YoutubeDataApi`** — Laravel facade for `Viewtrender\Youtube\YoutubeDataApi`. Proxies `fake()`, `reset()`, assertion methods, etc.
- **`config/youtube-testkit.php`** — Publishable config with `fixtures_path` (custom fixture location) and `prevent_stray_requests` (throw on unmatched API calls).

### How the container swap works

1. `YoutubeDataApiServiceProvider::register()` calls `YoutubeDataApi::registerContainerSwap()` with a closure.
2. When test code calls `YoutubeDataApi::fake([...])`, the core package invokes the registered closure.
3. The closure calls `$this->app->instance(YouTube::class, YoutubeDataApi::youtube())`, replacing the container binding with the fake.
4. Controllers that inject `YouTube` via the container now receive the fake instance.

## Testing

Tests use **Orchestra Testbench** (`Orchestra\Testbench\TestCase`) to boot a minimal Laravel app.

### Test files (`tests/`, namespace `Viewtrender\Youtube\Laravel\Tests`)

- `ServiceProviderTest` — Provider registration, config merging, container binding swap
- `FacadeTest` — Facade resolution, fake data assertions
- `ContainerSwapTest` — End-to-end route tests, prevent stray requests, reset behavior

### Important pattern: always reset in tearDown

Every test class must call `YoutubeDataApi::reset()` in `tearDown()` before `parent::tearDown()` to clear fake state between tests:

```php
protected function tearDown(): void
{
    YoutubeDataApi::reset();
    parent::tearDown();
}
```

## Code Conventions

- All PHP files use `declare(strict_types=1)`
- PSR-4 autoloading: `Viewtrender\Youtube\Laravel\` maps to `src/`
- Auto-discovery via `composer.json` `extra.laravel` — no manual provider/alias registration needed in consuming apps
