<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Artisan;

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

// create URL to run Artisan command to create storage link
Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage link created';
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        // Get greeting based on server time
        $hour = now()->hour;
        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 17) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }

        $stats = [
            'greeting' => $greeting,
            'total_users' => \App\Models\User::count(),
            'active_users' => \App\Models\User::where('is_active', true)->count(),
            'total_customers' => \App\Models\Customer::count(),
            'total_transactions' => \App\Models\Transaction::count(),
            'total_products' => \App\Models\Product::count(),
            'total_brands' => \App\Models\Brand::count(),
            'total_categories' => \App\Models\ProductCategory::count(),
            'recent_transactions' => \App\Models\Transaction::with('customer')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'transaction_code' => $t->transaction_code,
                    'transaction_type' => $t->transaction_type,
                    'customer_name' => $t->customer?->client_name ?? 'N/A',
                    'grand_total' => $t->grand_total ?? 0,
                    'status' => $t->status,
                    'transaction_date' => $t->transaction_date ? \Carbon\Carbon::parse($t->transaction_date)->format('d M Y') : '-',
                ]),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    })->name('dashboard');

    // Access Management (Users, Roles, Permissions)
    Route::prefix('access-management')->group(function () {
        // Main page - accessible if user has either users.view or roles.view
        Route::get('/', [RoleController::class, 'index'])->name('access-management.index');

        // Roles API with permission middleware
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles/{id}', [RoleController::class, 'show']);
        });
        Route::middleware('permission:roles.create')->group(function () {
            Route::post('/roles', [RoleController::class, 'store']);
            Route::post('/roles/{id}/duplicate', [RoleController::class, 'duplicate']);
        });
        Route::middleware('permission:roles.edit')->group(function () {
            Route::put('/roles/{id}', [RoleController::class, 'update']);
        });
        Route::middleware('permission:roles.delete')->group(function () {
            Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
            Route::post('/roles/bulk-delete', [RoleController::class, 'bulkDestroy']);
        });

        // Permissions API with permission middleware
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index']);
        });
        Route::middleware('permission:roles.create')->group(function () {
            Route::post('/permissions', [PermissionController::class, 'store']);
        });
        Route::middleware('permission:roles.edit')->group(function () {
            Route::put('/permissions/{id}', [PermissionController::class, 'update']);
        });
        Route::middleware('permission:roles.delete')->group(function () {
            Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
        });
    });

    // User API routes (for UsersTab in Access Management)
    Route::prefix('user')->group(function () {
        Route::get('/list',                     [UserController::class, 'index'])->name('user.list');
        Route::get('/detail/{id}',              [UserController::class, 'show'])->name('user.detail');
        Route::get('/api/detail/{id}',          [UserController::class, 'detailApi']);
        Route::get('/edit/{id}',                [UserController::class, 'edit'])->name('user.edit');
        Route::post('/api/store',               [UserController::class, 'store']);
        Route::put('/api/update/{id}',          [UserController::class, 'update']);
        Route::patch('/api/toggle-status/{id}', [UserController::class, 'toggleStatus']);
        Route::delete('/api/delete/{id}',       [UserController::class, 'destroy']);
    });

    Route::prefix('company')->group(function () {
        Route::get('/edit/{id}',                [CompanyController::class, 'edit'])->name('company.edit');
        Route::post('/api/update/{id}',         [CompanyController::class, 'update']);
        Route::get('/detail/{id}',              [CompanyController::class, 'detail'])->name('company.detail');
    });

    Route::prefix('customer')->group(function () {
        Route::get('/list',                     [CustomerController::class, 'list'])->name('customer.list');
        Route::get('/detail/{id}',              [CustomerController::class, 'detail'])->name('customer.detail');
        Route::get('/edit/{id}',                [CustomerController::class, 'edit'])->name('customer.edit');
        Route::get('/api/detail/{id}',          [CustomerController::class, 'detailApi']);
        Route::post('/api/store',               [CustomerController::class, 'store']);
        Route::put('/api/update/{id}',          [CustomerController::class, 'update']);
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
        Route::put('/api/update/{id}',  [ProductCategoryController::class, 'update']);
    });

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
});