<?php

use App\Http\Controllers\Api\GoalController;
use App\Http\Middleware\AuthenticateBoardApiKey;
use Illuminate\Support\Facades\Route;

Route::get('/goals', [GoalController::class, 'index'])
    ->middleware(AuthenticateBoardApiKey::class)
    ->name('api.goals.index');

Route::post('/goals', [GoalController::class, 'store'])
    ->middleware(AuthenticateBoardApiKey::class)
    ->name('api.goals.store');

Route::patch('/goals/{goal}', [GoalController::class, 'update'])
    ->whereNumber('goal')
    ->middleware(AuthenticateBoardApiKey::class)
    ->name('api.goals.update');
