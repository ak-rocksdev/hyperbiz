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
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pdf\SalesOrderPdfController;
use App\Http\Controllers\Pdf\PurchaseOrderPdfController;
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
        Route::patch('/api/toggle-status/{id}', [CustomerController::class, 'toggleStatus']);
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
        Route::get('/detail/{id}',      [BrandController::class, 'show'])->name('brand.detail');
        Route::get('/edit/{id}',        [BrandController::class, 'edit'])->name('brand.edit');
        Route::get('/api/detail/{id}',  [BrandController::class, 'detailApi']);
        Route::post('/api/store',       [BrandController::class, 'store']);
        Route::put('/api/update/{id}',  [BrandController::class, 'update']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/list',             [ProductController::class, 'list'])->name('product.list');
        Route::get('/detail/{id}',      [ProductController::class, 'show'])->name('product.detail');
        Route::get('/api/detail/{id}',  [ProductController::class, 'detailApi']);
        Route::post('/api/store',       [ProductController::class, 'store']);
        Route::get('/edit/{id}',        [ProductController::class, 'edit'])->name('product.edit');
        Route::put('/api/update/{id}',  [ProductController::class, 'update']);
    });

    // Product Categories
    Route::prefix('product-category')->group(function () {
        // View routes
        Route::middleware('permission:product-categories.view')->group(function () {
            Route::get('/list',             [ProductCategoryController::class, 'list'])->name('product-category.list');
            Route::get('/detail/{id}',      [ProductCategoryController::class, 'show'])->name('product-category.detail');
            Route::get('/api/detail/{id}',  [ProductCategoryController::class, 'detailApi']);
        });

        // Create routes
        Route::middleware('permission:product-categories.create')->group(function () {
            Route::post('/api/store',       [ProductCategoryController::class, 'store']);
        });

        // Edit routes
        Route::middleware('permission:product-categories.edit')->group(function () {
            Route::get('/edit/{id}',        [ProductCategoryController::class, 'edit'])->name('product-category.edit');
            Route::put('/api/update/{id}',  [ProductCategoryController::class, 'update']);
        });

        // Delete routes
        Route::middleware('permission:product-categories.delete')->group(function () {
            Route::delete('/api/delete/{id}', [ProductCategoryController::class, 'destroy']);
        });
    });

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // Purchase Orders
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/list',                         [PurchaseOrderController::class, 'list'])->name('purchase-orders.list');
        Route::get('/create',                       [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::get('/{id}',                         [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::get('/edit/{id}',                    [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::post('/api/store',                   [PurchaseOrderController::class, 'store']);
        Route::put('/api/update/{id}',              [PurchaseOrderController::class, 'update']);
        Route::post('/api/confirm/{id}',            [PurchaseOrderController::class, 'confirm']);
        Route::post('/api/cancel/{id}',             [PurchaseOrderController::class, 'cancel']);
        Route::delete('/api/delete/{id}',           [PurchaseOrderController::class, 'delete']);
        Route::post('/api/receive/{id}',            [PurchaseOrderController::class, 'receive']);
        Route::post('/api/receive-all/{id}',        [PurchaseOrderController::class, 'receiveAll']);
        Route::get('/api/items/{id}',               [PurchaseOrderController::class, 'getItems']);
        // PDF Routes
        Route::get('/pdf/preview/{purchaseOrder}',  [PurchaseOrderPdfController::class, 'preview'])->name('purchase-orders.pdf.preview');
        Route::get('/pdf/download/{purchaseOrder}', [PurchaseOrderPdfController::class, 'download'])->name('purchase-orders.pdf.download');
    });

    // Sales Orders
    Route::prefix('sales-orders')->group(function () {
        Route::get('/list',                         [SalesOrderController::class, 'list'])->name('sales-orders.list');
        Route::get('/create',                       [SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::get('/{id}',                         [SalesOrderController::class, 'show'])->name('sales-orders.show');
        Route::get('/edit/{id}',                    [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
        Route::post('/api/store',                   [SalesOrderController::class, 'store']);
        Route::put('/api/update/{id}',              [SalesOrderController::class, 'update']);
        Route::post('/api/confirm/{id}',            [SalesOrderController::class, 'confirm']);
        Route::post('/api/cancel/{id}',             [SalesOrderController::class, 'cancel']);
        Route::delete('/api/delete/{id}',           [SalesOrderController::class, 'delete']);
        Route::post('/api/mark-shipped/{id}',       [SalesOrderController::class, 'markAsShipped']);
        Route::post('/api/mark-delivered/{id}',     [SalesOrderController::class, 'markAsDelivered']);
        Route::get('/api/items/{id}',               [SalesOrderController::class, 'getItems']);
        // PDF Routes
        Route::get('/pdf/preview/{salesOrder}',     [SalesOrderPdfController::class, 'preview'])->name('sales-orders.pdf.preview');
        Route::get('/pdf/download/{salesOrder}',    [SalesOrderPdfController::class, 'download'])->name('sales-orders.pdf.download');
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/list',                                 [PaymentController::class, 'list'])->name('payments.list');
        Route::get('/{id}',                                 [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/api/cancel/{id}',                     [PaymentController::class, 'cancel']);
        Route::post('/api/purchase-order/{poId}',           [PaymentController::class, 'storeForPurchaseOrder']);
        Route::post('/api/sales-order/{soId}',              [PaymentController::class, 'storeForSalesOrder']);
        Route::get('/api/purchase-order/{poId}',            [PaymentController::class, 'getForPurchaseOrder']);
        Route::get('/api/sales-order/{soId}',               [PaymentController::class, 'getForSalesOrder']);
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('/list',                         [InventoryController::class, 'list'])->name('inventory.list');
        Route::get('/movements',                    [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('/product/{id}',                 [InventoryController::class, 'show'])->name('inventory.show');
        Route::get('/low-stock',                    [InventoryController::class, 'lowStockReport'])->name('inventory.low-stock');
        Route::get('/valuation',                    [InventoryController::class, 'valuationReport'])->name('inventory.valuation');
        Route::post('/api/adjust/{id}',             [InventoryController::class, 'adjust']);
        Route::post('/api/reorder-level/{id}',      [InventoryController::class, 'updateReorderLevel']);
        Route::get('/api/movements/{id}',           [InventoryController::class, 'getMovements']);

        // Stock Adjustments
        Route::middleware('permission:inventory.adjustments.view')->group(function () {
            Route::get('/adjustments',              [InventoryController::class, 'adjustmentList'])->name('inventory.adjustments');
        });
        Route::middleware('permission:inventory.adjustments.create')->group(function () {
            Route::get('/adjustments/create',       [InventoryController::class, 'createAdjustment'])->name('inventory.adjustments.create');
            Route::post('/api/adjustments/store',   [InventoryController::class, 'storeAdjustment']);
            Route::post('/api/adjustments/bulk',    [InventoryController::class, 'storeBulkAdjustment']);
        });
    });
});