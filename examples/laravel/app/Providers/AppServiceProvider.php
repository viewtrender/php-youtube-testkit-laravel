<?php

namespace App\Providers;

use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(YouTube::class, function () {
            $client = new GoogleClient();
            $client->setApplicationName(config('services.youtube.application_name', 'My App'));
            $client->setDeveloperKey(config('services.youtube.api_key'));

            return new YouTube($client);
        });
    }
}
