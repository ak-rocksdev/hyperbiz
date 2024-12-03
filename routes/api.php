<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// create route example, return json
Route::get('/example', function () {
    return response()->json([
        'message' => 'Hello World!',
    ]);
});

// create group middleware
Route::get('/users', [UserController::class, 'indexData']);
