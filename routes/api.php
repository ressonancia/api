<?php

use App\Http\Controllers\AppsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/apps', [AppsController::class, 'index'])->name('api.apps.index');
Route::post('/apps', [AppsController::class, 'store'])->name('api.apps.store');
