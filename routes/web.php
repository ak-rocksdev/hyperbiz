<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    // redirect to '/login'
    if(auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('user')->group(function () {
        Route::get('/list',         [UserController::class, 'index'])->name('user.list');
    });

    // prefix "client"
    Route::prefix('client')->group(function () {
        Route::get('/list',             [ClientController::class, 'list'])->name('client.list');
        Route::get('/detail/{id}',      [ClientController::class, 'detail'])->name('client.detail');
        Route::get('/edit/{id}',        [ClientController::class, 'edit'])->name('client.edit');
        Route::get('/api/detail/{id}',  [ClientController::class, 'detailApi']);
        Route::post('/api/store',       [ClientController::class, 'store']);
        Route::put('/api/update/{id}', [ClientController::class, 'update']);
    });
});