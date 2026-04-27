<?php

namespace Badawy\Pushify;

use Badawy\Pushify\Commands\SendScheduledPushifyNotifications;
use Badawy\Pushify\Contracts\PushifyServiceInterface;
use Badawy\Pushify\Services\PushifyService;
use Illuminate\Support\ServiceProvider;

class PushifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pushify.php', 'pushify');

        $this->app->bind(PushifyServiceInterface::class, PushifyService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/pushify.php' => config_path('pushify.php'),
        ], 'pushify-config');

        $this->publishes([
            __DIR__.'/../stubs/routes/pushify.stub' => base_path('routes/pushify.php'),
        ], 'pushify-routes');

        $this->publishes([
            __DIR__.'/../stubs/Http/Controllers/PushifyController.stub' => app_path('Http/Controllers/Pushify/PushifyController.php'),
            __DIR__.'/../stubs/Http/Requests/StorePushifyRequest.stub' => app_path('Http/Requests/Pushify/StorePushifyRequest.php'),
            __DIR__.'/../stubs/Http/Resources/PushifyResource.stub' => app_path('Http/Resources/Pushify/PushifyResource.php'),
        ], 'pushify-http');

        $this->publishes([
            __DIR__.'/../config/pushify.php' => config_path('pushify.php'),
            __DIR__.'/../stubs/routes/pushify.stub' => base_path('routes/pushify.php'),
            __DIR__.'/../stubs/Http/Controllers/PushifyController.stub' => app_path('Http/Controllers/Pushify/PushifyController.php'),
            __DIR__.'/../stubs/Http/Requests/StorePushifyRequest.stub' => app_path('Http/Requests/Pushify/StorePushifyRequest.php'),
            __DIR__.'/../stubs/Http/Resources/PushifyResource.stub' => app_path('Http/Resources/Pushify/PushifyResource.php'),
        ], 'pushify');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ((bool) config('pushify.routes.enabled', true)) {
            $publishedRoutes = base_path('routes/pushify.php');
            $this->loadRoutesFrom(file_exists($publishedRoutes) ? $publishedRoutes : __DIR__.'/../routes/pushify.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendScheduledPushifyNotifications::class,
            ]);
        }
    }
}
