<?php

use Badawy\Pushify\Http\Controllers\PushifyController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('pushify.routes.prefix', 'pushify'))
    ->middleware(config('pushify.routes.middleware', ['api']))
    ->group(function () {
        Route::get('/', [PushifyController::class, 'index']);
        Route::post('/', [PushifyController::class, 'store']);
        Route::get('/{pushify}', [PushifyController::class, 'show']);
        Route::post('/{pushify}/send', [PushifyController::class, 'send']);
    });
