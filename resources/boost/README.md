# Laravel Boost AI Resources

This directory contains AI guidelines for Laravel Boost, Laravel's official AI development assistant.

## What's Included

### Guidelines (`guidelines/core.blade.php`)
- Overview of the YouTube testkit package
- Basic setup and configuration
- Common usage patterns and examples
- Available assertions and error handling

## Usage

When you run `php artisan boost:install` or `php artisan boost:update` in a project that uses this package, Laravel Boost will automatically append these guidelines to your `CLAUDE.md` or `AGENTS.md` file, giving AI assistants the context they need to write clean YouTube API test implementations.

## For Package Maintainers

These resources are automatically registered by the `YoutubeDataApiServiceProvider`. The guidelines use Laravel Blade syntax with `@verbatim` blocks to prevent template processing of code examples.
