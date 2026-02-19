<?php

use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/search', [VideoController::class, 'search']);
Route::get('/videos/{id}', [VideoController::class, 'show']);
Route::get('/channels', [ChannelController::class, 'index']);
Route::get('/channels/{id}', [ChannelController::class, 'show']);
