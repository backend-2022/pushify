<?php

use Badawy\Pushify\Http\Controllers\PushifyController;
use Illuminate\Support\Facades\Route;

$registerRoutes = static function (): void {
    Route::get('/', [PushifyController::class, 'index']);
    Route::post('/', [PushifyController::class, 'store']);
    Route::get('/{pushify}', [PushifyController::class, 'show']);
    Route::post('/{pushify}/send', [PushifyController::class, 'send']);
};

Route::prefix(config('pushify.routes.prefix', 'pushify'))
    ->middleware(config('pushify.routes.middleware', ['api']))
    ->group($registerRoutes);

Route::prefix(config('pushify.routes.web_prefix', config('pushify.routes.prefix', 'pushify-web')))
    ->middleware(config('pushify.routes.web_middleware', ['web']))
    ->group($registerRoutes);
