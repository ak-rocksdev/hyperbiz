<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\CompanyController;

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
        Route::get('/list',                     [UserController::class, 'index'])->name('user.list');
    });

    Route::prefix('company')->group(function () {
        Route::get('/edit/{id}',                [CompanyController::class, 'edit'])->name('company.edit');
        Route::post('/api/update/{id}',         [CompanyController::class, 'update']);
        Route::get('/api/latest', function () {
            return response()->json(['company' => \App\Models\Company::first()]);
        })->name('company.latest');
    });

    Route::prefix('client')->group(function () {
        Route::get('/list',                     [ClientController::class, 'list'])->name('client.list');
        Route::get('/detail/{id}',              [ClientController::class, 'detail'])->name('client.detail');
        Route::get('/edit/{id}',                [ClientController::class, 'edit'])->name('client.edit');
        Route::get('/api/detail/{id}',          [ClientController::class, 'detailApi']);
        Route::post('/api/store',               [ClientController::class, 'store']);
        Route::put('/api/update/{id}',          [ClientController::class, 'update']);
        Route::get('/api/get/products/{id}',    [ClientController::class, 'getProducts']);
    });

    Route::prefix('transaction')->group(function () {
        Route::get('/list',                     [TransactionController::class, 'list'])->name('transaction.list');
        Route::get('/create',                   [TransactionController::class, 'create'])->name('transaction.create');
        Route::post('/api/store',               [TransactionController::class, 'store']);
        Route::get('/api/detail/{id}',          [TransactionController::class, 'detailApi']);
        Route::get('/edit/{id}',                [TransactionController::class, 'edit'])->name('transaction.edit');
        Route::put('/api/update/{id}',          [TransactionController::class, 'update']);
        Route::delete('/api/delete/{id}',       [TransactionController::class, 'delete'])->name('transaction.delete');
        Route::get('/{id}/export-pdf',          [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
        Route::get('/{id}/preview',             [TransactionController::class, 'previewPdf']);

    });

    Route::prefix('brand')->group(function () {
        Route::get('/list',             [BrandController::class, 'list'])->name('brand.list');
        Route::get('/api/detail/{id}',  [BrandController::class, 'detailApi']);
        Route::post('/api/store',       [BrandController::class, 'store']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/list',             [ProductController::class, 'list'])->name('product.list');
        Route::get('/api/detail/{id}',  [ProductController::class, 'detailApi']);
        Route::post('/api/store',       [ProductController::class, 'store']);
        Route::get('/edit/{id}',        [ProductController::class, 'edit'])->name('product.edit');
        Route::put('/api/update/{id}',  [ProductController::class, 'update']);
    });

    // product category
    Route::prefix('product-category')->group(function () {
        Route::get('/list',             [ProductCategoryController::class, 'list'])->name('product-category.list');
        Route::get('/api/detail/{id}',  [ProductCategoryController::class, 'detailApi']);
        Route::post('/api/store',       [ProductCategoryController::class, 'store']);
    });
});