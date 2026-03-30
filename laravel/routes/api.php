<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/tours/search', [TourController::class, 'search']);
Route::get('/tours/{lead}/results', [TourController::class, 'getResults']);