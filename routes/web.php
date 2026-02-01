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
use App\Http\Controllers\FinancialSettingController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\ARAgingController;
use App\Http\Controllers\APAgingController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\UomCategoryController;
use App\Http\Controllers\UomController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\StripeWebhookController;

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
    'check.subscription', // Check if subscription is active/trial
])->group(function () {
    // =====================================================
    // Onboarding Routes (Users without company)
    // =====================================================
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'welcome'])->name('welcome');
        Route::get('/company-setup', [OnboardingController::class, 'companySetup'])->name('company-setup');
        Route::post('/company-setup', [OnboardingController::class, 'storeCompany'])->name('store-company');
        Route::get('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

    // Main dashboard - redirects based on user type
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Platform admin goes to admin dashboard
        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // User without company goes to onboarding
        if (!$user->hasCompany()) {
            return redirect()->route('onboarding.welcome');
        }

        // Regular tenant user goes to dashboard
        return app(DashboardController::class)->index();
    })->name('dashboard');

    // =====================================================
    // Subscription Routes (Tenant Users)
    // =====================================================
    Route::prefix('subscription')->name('subscription.')->middleware('ensure.company')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/plans', [SubscriptionController::class, 'showPlans'])->name('plans');
        Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::get('/payment-proof/{invoice}', [SubscriptionController::class, 'showPaymentProof'])->name('payment-proof');
        Route::post('/payment-proof/{invoice}', [SubscriptionController::class, 'uploadPaymentProof'])->name('upload-payment-proof');
        Route::get('/billing-history', [SubscriptionController::class, 'billingHistory'])->name('billing-history');
        Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('cancel');
        Route::get('/invoice/{invoice}/download', [SubscriptionController::class, 'downloadInvoice'])->name('invoice.download');

        // Stripe routes
        Route::get('/stripe/checkout/{invoice}', [SubscriptionController::class, 'stripeCheckout'])->name('stripe.checkout');
        Route::get('/success', [SubscriptionController::class, 'paymentSuccess'])->name('success');
        Route::get('/cancelled', [SubscriptionController::class, 'paymentCancelled'])->name('cancelled');
    });

    // Stripe webhook (outside auth middleware)
    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
        ->name('stripe.webhook')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // =====================================================
    // Platform Admin Routes (Platform Admins Only)
    // =====================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [PlatformAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/companies', [PlatformAdminController::class, 'companies'])->name('companies');
        Route::get('/companies/{company}', [PlatformAdminController::class, 'companyDetail'])->name('companies.detail');
        Route::put('/companies/{company}/subscription', [PlatformAdminController::class, 'updateCompanySubscription'])->name('companies.subscription');

        // Subscription Plans Management
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [SubscriptionPlanController::class, 'index'])->name('index');
            Route::post('/', [SubscriptionPlanController::class, 'store'])->name('store');
            Route::put('/{plan}', [SubscriptionPlanController::class, 'update'])->name('update');
            Route::patch('/{plan}/toggle-status', [SubscriptionPlanController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{plan}', [SubscriptionPlanController::class, 'destroy'])->name('destroy');
            Route::post('/update-order', [SubscriptionPlanController::class, 'updateOrder'])->name('update-order');
        });

        // Payment Verifications Management
        Route::prefix('payment-verifications')->name('payment-verifications.')->group(function () {
            Route::get('/', [PaymentVerificationController::class, 'index'])->name('index');
            Route::get('/{proof}', [PaymentVerificationController::class, 'show'])->name('show');
            Route::post('/{proof}/approve', [PaymentVerificationController::class, 'approve'])->name('approve');
            Route::post('/{proof}/reject', [PaymentVerificationController::class, 'reject'])->name('reject');
        });
    });

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
        // View routes
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/list',                     [UserController::class, 'index'])->name('user.list');
            Route::get('/detail/{id}',              [UserController::class, 'show'])->name('user.detail');
            Route::get('/api/detail/{id}',          [UserController::class, 'detailApi']);
        });

        // Create routes
        Route::middleware('permission:users.create')->group(function () {
            Route::post('/api/store',               [UserController::class, 'store']);
        });

        // Edit routes
        Route::middleware('permission:users.edit')->group(function () {
            Route::get('/edit/{id}',                [UserController::class, 'edit'])->name('user.edit');
            Route::put('/api/update/{id}',          [UserController::class, 'update']);
            Route::patch('/api/toggle-status/{id}', [UserController::class, 'toggleStatus']);
        });

        // Delete routes
        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('/api/delete/{id}',       [UserController::class, 'destroy']);
        });
    });

    // Company Settings
    Route::prefix('company')->group(function () {
        Route::middleware('permission:company.view')->group(function () {
            Route::get('/detail/{id}',              [CompanyController::class, 'detail'])->name('company.detail');
        });
        Route::middleware('permission:company.edit')->group(function () {
            Route::get('/edit/{id}',                [CompanyController::class, 'edit'])->name('company.edit');
            Route::post('/api/update/{id}',         [CompanyController::class, 'update']);
        });
    });

    // Customers
    Route::prefix('customer')->group(function () {
        Route::middleware('permission:customers.view')->group(function () {
            Route::get('/list',                     [CustomerController::class, 'list'])->name('customer.list');
            Route::get('/detail/{id}',              [CustomerController::class, 'detail'])->name('customer.detail');
            Route::get('/api/detail/{id}',          [CustomerController::class, 'detailApi']);
        });
        Route::middleware('permission:customers.create')->group(function () {
            Route::post('/api/store',               [CustomerController::class, 'store']);
        });
        Route::middleware('permission:customers.edit')->group(function () {
            Route::get('/edit/{id}',                [CustomerController::class, 'edit'])->name('customer.edit');
            Route::put('/api/update/{id}',          [CustomerController::class, 'update']);
            Route::patch('/api/toggle-status/{id}', [CustomerController::class, 'toggleStatus']);
        });
    });

    // Transactions
    Route::prefix('transaction')->group(function () {
        Route::middleware('permission:transactions.view')->group(function () {
            Route::get('/list',                     [TransactionController::class, 'list'])->name('transaction.list');
            Route::get('/api/detail/{id}',          [TransactionController::class, 'detailApi']);
            Route::get('/{id}/export-pdf',          [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
            Route::get('/{id}/preview',             [TransactionController::class, 'previewPdf']);
        });
        Route::middleware('permission:transactions.create')->group(function () {
            Route::get('/create',                   [TransactionController::class, 'create'])->name('transaction.create');
            Route::post('/api/store',               [TransactionController::class, 'store']);
        });
        Route::middleware('permission:transactions.edit')->group(function () {
            Route::get('/edit/{id}',                [TransactionController::class, 'edit'])->name('transaction.edit');
            Route::put('/api/update/{id}',          [TransactionController::class, 'update']);
        });
        Route::middleware('permission:transactions.delete')->group(function () {
            Route::delete('/api/delete/{id}',       [TransactionController::class, 'delete'])->name('transaction.delete');
        });
    });

    // Brands
    Route::prefix('brand')->group(function () {
        Route::middleware('permission:brands.view')->group(function () {
            Route::get('/list',             [BrandController::class, 'list'])->name('brand.list');
            Route::get('/detail/{id}',      [BrandController::class, 'show'])->name('brand.detail');
            Route::get('/api/detail/{id}',  [BrandController::class, 'detailApi']);
        });
        Route::middleware('permission:brands.create')->group(function () {
            Route::post('/api/store',       [BrandController::class, 'store']);
        });
        Route::middleware('permission:brands.edit')->group(function () {
            Route::get('/edit/{id}',        [BrandController::class, 'edit'])->name('brand.edit');
            Route::put('/api/update/{id}',  [BrandController::class, 'update']);
        });
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::middleware('permission:products.view')->group(function () {
            Route::get('/list',             [ProductController::class, 'list'])->name('product.list');
            Route::get('/detail/{id}',      [ProductController::class, 'show'])->name('product.detail');
            Route::get('/api/detail/{id}',  [ProductController::class, 'detailApi']);
            // Product UoMs for PO/SO (view permission only)
            Route::get('/api/{productId}/available-uoms', [ProductController::class, 'getAvailableUoms']);
        });
        Route::middleware('permission:products.create')->group(function () {
            Route::post('/api/store',       [ProductController::class, 'store']);
        });
        Route::middleware('permission:products.edit')->group(function () {
            Route::get('/edit/{id}',        [ProductController::class, 'edit'])->name('product.edit');
            Route::put('/api/update/{id}',  [ProductController::class, 'update']);
            // Product UoM configuration
            Route::get('/api/{productId}/uoms',             [ProductController::class, 'getProductUoms']);
            Route::post('/api/{productId}/uoms',            [ProductController::class, 'storeProductUom']);
            Route::put('/api/{productId}/uoms/{id}',        [ProductController::class, 'updateProductUom']);
            Route::delete('/api/{productId}/uoms/{id}',     [ProductController::class, 'destroyProductUom']);
        });
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

    // UoM Categories
    Route::prefix('uom-category')->group(function () {
        // View routes
        Route::middleware('permission:uom.view')->group(function () {
            Route::get('/list',             [UomCategoryController::class, 'list'])->name('uom-category.list');
            Route::get('/detail/{id}',      [UomCategoryController::class, 'show'])->name('uom-category.detail');
            Route::get('/api/detail/{id}',  [UomCategoryController::class, 'detailApi']);
        });

        // Create routes
        Route::middleware('permission:uom.create')->group(function () {
            Route::post('/api/store',       [UomCategoryController::class, 'store']);
        });

        // Edit routes
        Route::middleware('permission:uom.edit')->group(function () {
            Route::get('/edit/{id}',            [UomCategoryController::class, 'edit'])->name('uom-category.edit');
            Route::put('/api/update/{id}',      [UomCategoryController::class, 'update']);
            Route::patch('/api/toggle-status/{id}', [UomCategoryController::class, 'toggleStatus']);
        });

        // Delete routes
        Route::middleware('permission:uom.delete')->group(function () {
            Route::delete('/api/delete/{id}', [UomCategoryController::class, 'destroy']);
        });
    });

    // Units of Measure (UoM)
    Route::prefix('uom')->group(function () {
        // View routes
        Route::middleware('permission:uom.view')->group(function () {
            Route::get('/list',                     [UomController::class, 'list'])->name('uom.list');
            Route::get('/detail/{id}',              [UomController::class, 'show'])->name('uom.detail');
            Route::get('/api/detail/{id}',          [UomController::class, 'detailApi']);
            Route::get('/api/base-uoms/{categoryId}', [UomController::class, 'getBaseUomsByCategory']);
        });

        // Create routes
        Route::middleware('permission:uom.create')->group(function () {
            Route::post('/api/store',       [UomController::class, 'store']);
        });

        // Edit routes
        Route::middleware('permission:uom.edit')->group(function () {
            Route::get('/edit/{id}',            [UomController::class, 'edit'])->name('uom.edit');
            Route::put('/api/update/{id}',      [UomController::class, 'update']);
            Route::patch('/api/toggle-status/{id}', [UomController::class, 'toggleStatus']);
        });

        // Delete routes
        Route::middleware('permission:uom.delete')->group(function () {
            Route::delete('/api/delete/{id}', [UomController::class, 'destroy']);
        });
    });

    // System Logs
    Route::middleware('permission:logs.view')->group(function () {
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    });

    // Purchase Orders
    Route::prefix('purchase-orders')->group(function () {
        Route::middleware('permission:purchase-orders.create')->group(function () {
            Route::get('/create',                       [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
            Route::post('/api/store',                   [PurchaseOrderController::class, 'store']);
        });
        Route::middleware('permission:purchase-orders.edit')->group(function () {
            Route::get('/edit/{id}',                    [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
            Route::put('/api/update/{id}',              [PurchaseOrderController::class, 'update']);
            Route::post('/api/confirm/{id}',            [PurchaseOrderController::class, 'confirm']);
            Route::post('/api/cancel/{id}',             [PurchaseOrderController::class, 'cancel']);
            Route::post('/api/receive/{id}',            [PurchaseOrderController::class, 'receive']);
            Route::post('/api/receive-all/{id}',        [PurchaseOrderController::class, 'receiveAll']);
        });
        Route::middleware('permission:purchase-orders.delete')->group(function () {
            Route::delete('/api/delete/{id}',           [PurchaseOrderController::class, 'delete']);
        });
        Route::middleware('permission:purchase-orders.view')->group(function () {
            Route::get('/list',                         [PurchaseOrderController::class, 'list'])->name('purchase-orders.list');
            Route::get('/api/items/{id}',               [PurchaseOrderController::class, 'getItems']);
            Route::get('/pdf/preview/{purchaseOrder}',  [PurchaseOrderPdfController::class, 'preview'])->name('purchase-orders.pdf.preview');
            Route::get('/pdf/download/{purchaseOrder}', [PurchaseOrderPdfController::class, 'download'])->name('purchase-orders.pdf.download');
            Route::get('/{id}',                         [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        });
    });

    // Sales Orders
    Route::prefix('sales-orders')->group(function () {
        Route::middleware('permission:sales-orders.create')->group(function () {
            Route::get('/create',                       [SalesOrderController::class, 'create'])->name('sales-orders.create');
            Route::post('/api/store',                   [SalesOrderController::class, 'store']);
        });
        Route::middleware('permission:sales-orders.edit')->group(function () {
            Route::get('/edit/{id}',                    [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
            Route::put('/api/update/{id}',              [SalesOrderController::class, 'update']);
            Route::post('/api/confirm/{id}',            [SalesOrderController::class, 'confirm']);
            Route::post('/api/cancel/{id}',             [SalesOrderController::class, 'cancel']);
            Route::post('/api/mark-shipped/{id}',       [SalesOrderController::class, 'markAsShipped']);
            Route::post('/api/mark-delivered/{id}',     [SalesOrderController::class, 'markAsDelivered']);
        });
        Route::middleware('permission:sales-orders.delete')->group(function () {
            Route::delete('/api/delete/{id}',           [SalesOrderController::class, 'delete']);
        });
        Route::middleware('permission:sales-orders.view')->group(function () {
            Route::get('/list',                         [SalesOrderController::class, 'list'])->name('sales-orders.list');
            Route::get('/api/items/{id}',               [SalesOrderController::class, 'getItems']);
            Route::get('/pdf/preview/{salesOrder}',     [SalesOrderPdfController::class, 'preview'])->name('sales-orders.pdf.preview');
            Route::get('/pdf/download/{salesOrder}',    [SalesOrderPdfController::class, 'download'])->name('sales-orders.pdf.download');
            Route::get('/{id}',                         [SalesOrderController::class, 'show'])->name('sales-orders.show');
        });
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::middleware('permission:payments.create')->group(function () {
            Route::post('/api/purchase-order/{poId}',           [PaymentController::class, 'storeForPurchaseOrder']);
            Route::post('/api/sales-order/{soId}',              [PaymentController::class, 'storeForSalesOrder']);
        });
        Route::middleware('permission:payments.edit')->group(function () {
            Route::post('/api/cancel/{id}',                     [PaymentController::class, 'cancel']);
        });
        Route::middleware('permission:payments.view')->group(function () {
            Route::get('/list',                                 [PaymentController::class, 'list'])->name('payments.list');
            Route::get('/api/purchase-order/{poId}',            [PaymentController::class, 'getForPurchaseOrder']);
            Route::get('/api/sales-order/{soId}',               [PaymentController::class, 'getForSalesOrder']);
            Route::get('/{id}',                                 [PaymentController::class, 'show'])->name('payments.show');
        });
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::middleware('permission:inventory.view')->group(function () {
            Route::get('/list',                         [InventoryController::class, 'list'])->name('inventory.list');
            Route::get('/movements',                    [InventoryController::class, 'movements'])->name('inventory.movements');
            Route::get('/product/{id}',                 [InventoryController::class, 'show'])->name('inventory.show');
            Route::get('/low-stock',                    [InventoryController::class, 'lowStockReport'])->name('inventory.low-stock');
            Route::get('/valuation',                    [InventoryController::class, 'valuationReport'])->name('inventory.valuation');
            Route::get('/api/movements/{id}',           [InventoryController::class, 'getMovements']);
        });
        Route::middleware('permission:inventory.edit')->group(function () {
            Route::post('/api/adjust/{id}',             [InventoryController::class, 'adjust']);
            Route::post('/api/reorder-level/{id}',      [InventoryController::class, 'updateReorderLevel']);
        });

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

    // ==================== FINANCE MODULE ====================
    Route::prefix('finance')->group(function () {
        // Financial Settings
        Route::middleware('permission:finance.settings.view')->group(function () {
            Route::get('/settings', [FinancialSettingController::class, 'index'])->name('finance.settings');
            Route::get('/api/settings/{group}', [FinancialSettingController::class, 'getByGroup']);
        });
        Route::middleware('permission:finance.settings.manage')->group(function () {
            Route::put('/settings', [FinancialSettingController::class, 'update']);
        });

        // Chart of Accounts
        Route::middleware('permission:finance.chart_of_accounts.view')->group(function () {
            Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('finance.chart-of-accounts');
            Route::get('/api/accounts', [ChartOfAccountController::class, 'getForSelect']);
        });
        Route::middleware('permission:finance.chart_of_accounts.manage')->group(function () {
            Route::post('/api/accounts', [ChartOfAccountController::class, 'store']);
            Route::put('/api/accounts/{account}', [ChartOfAccountController::class, 'update']);
            Route::delete('/api/accounts/{account}', [ChartOfAccountController::class, 'destroy']);
            Route::patch('/api/accounts/{account}/toggle-status', [ChartOfAccountController::class, 'toggleStatus']);
        });

        // Fiscal Periods
        Route::middleware('permission:finance.fiscal_periods.view')->group(function () {
            Route::get('/fiscal-periods', [FiscalPeriodController::class, 'index'])->name('finance.fiscal-periods');
            Route::get('/api/fiscal-periods/by-date', [FiscalPeriodController::class, 'getByDate']);
        });
        Route::middleware('permission:finance.fiscal_periods.close')->group(function () {
            Route::post('/api/fiscal-years', [FiscalPeriodController::class, 'createYear']);
            Route::post('/api/fiscal-years/{year}/close', [FiscalPeriodController::class, 'closeYear']);
            Route::post('/api/fiscal-years/{year}/set-current', [FiscalPeriodController::class, 'setCurrentYear']);
            Route::post('/api/fiscal-periods/{period}/close', [FiscalPeriodController::class, 'closePeriod']);
            Route::post('/api/fiscal-periods/{period}/reopen', [FiscalPeriodController::class, 'reopenPeriod']);
        });

        // Expense Categories
        Route::middleware('permission:finance.expenses.view')->group(function () {
            Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('finance.expense-categories');
            Route::get('/api/expense-categories', [ExpenseCategoryController::class, 'getForSelect']);
        });
        Route::middleware('permission:finance.expenses.create')->group(function () {
            Route::post('/api/expense-categories', [ExpenseCategoryController::class, 'store']);
        });
        Route::middleware('permission:finance.expenses.edit')->group(function () {
            Route::put('/api/expense-categories/{category}', [ExpenseCategoryController::class, 'update']);
            Route::patch('/api/expense-categories/{category}/toggle-status', [ExpenseCategoryController::class, 'toggleStatus']);
        });
        Route::middleware('permission:finance.expenses.delete')->group(function () {
            Route::delete('/api/expense-categories/{category}', [ExpenseCategoryController::class, 'destroy']);
        });

        // Expenses
        Route::middleware('permission:finance.expenses.view')->group(function () {
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('finance.expenses');
        });
        Route::middleware('permission:finance.expenses.create')->group(function () {
            Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('finance.expenses.create');
            Route::post('/api/expenses', [ExpenseController::class, 'store']);
        });
        Route::middleware('permission:finance.expenses.view')->group(function () {
            Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('finance.expenses.show');
        });
        Route::middleware('permission:finance.expenses.edit')->group(function () {
            Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('finance.expenses.edit');
            Route::put('/api/expenses/{expense}', [ExpenseController::class, 'update']);
        });
        Route::middleware('permission:finance.expenses.delete')->group(function () {
            Route::delete('/api/expenses/{expense}', [ExpenseController::class, 'destroy']);
        });
        Route::middleware('permission:finance.expenses.approve')->group(function () {
            Route::post('/api/expenses/{expense}/approve', [ExpenseController::class, 'approve']);
            Route::post('/api/expenses/{expense}/post', [ExpenseController::class, 'post']);
            Route::post('/api/expenses/{expense}/cancel', [ExpenseController::class, 'cancel']);
        });

        // Journal Entries
        Route::middleware('permission:finance.journal_entries.view')->group(function () {
            Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('finance.journal-entries');
        });
        Route::middleware('permission:finance.journal_entries.create')->group(function () {
            Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('finance.journal-entries.create');
            Route::post('/api/journal-entries', [JournalEntryController::class, 'store']);
        });
        Route::middleware('permission:finance.journal_entries.view')->group(function () {
            Route::get('/journal-entries/{entry}', [JournalEntryController::class, 'show'])->name('finance.journal-entries.show');
        });
        Route::middleware('permission:finance.journal_entries.edit')->group(function () {
            Route::get('/journal-entries/{entry}/edit', [JournalEntryController::class, 'edit'])->name('finance.journal-entries.edit');
            Route::put('/api/journal-entries/{entry}', [JournalEntryController::class, 'update']);
        });
        Route::middleware('permission:finance.journal_entries.delete')->group(function () {
            Route::delete('/api/journal-entries/{entry}', [JournalEntryController::class, 'destroy']);
        });
        Route::middleware('permission:finance.journal_entries.post')->group(function () {
            Route::post('/api/journal-entries/{entry}/post', [JournalEntryController::class, 'post']);
        });
        Route::middleware('permission:finance.journal_entries.void')->group(function () {
            Route::post('/api/journal-entries/{entry}/void', [JournalEntryController::class, 'void']);
            Route::post('/api/journal-entries/{entry}/reverse', [JournalEntryController::class, 'reverse']);
        });

        // AR Aging Reports
        Route::middleware('permission:finance.reports.ar_aging')->group(function () {
            Route::get('/reports/ar-aging', [ARAgingController::class, 'index'])->name('finance.reports.ar-aging');
            Route::get('/api/reports/ar-aging/{customerId}', [ARAgingController::class, 'detail']);
            Route::post('/api/reports/ar-aging/recalculate', [ARAgingController::class, 'recalculate']);
        });

        // AP Aging Reports
        Route::middleware('permission:finance.reports.ap_aging')->group(function () {
            Route::get('/reports/ap-aging', [APAgingController::class, 'index'])->name('finance.reports.ap-aging');
            Route::get('/api/reports/ap-aging/{supplierId}', [APAgingController::class, 'detail']);
            Route::post('/api/reports/ap-aging/recalculate', [APAgingController::class, 'recalculate']);
        });

        // Bank Accounts
        Route::middleware('permission:finance.bank.view')->group(function () {
            Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('finance.bank-accounts');
            Route::get('/bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->name('finance.bank-accounts.show');
            Route::get('/api/bank-accounts/list', [BankAccountController::class, 'list']);
        });
        Route::middleware('permission:finance.bank.manage')->group(function () {
            Route::post('/api/bank-accounts', [BankAccountController::class, 'store']);
            Route::put('/api/bank-accounts/{bankAccount}', [BankAccountController::class, 'update']);
            Route::delete('/api/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy']);
            Route::patch('/api/bank-accounts/{bankAccount}/toggle-status', [BankAccountController::class, 'toggleStatus']);
            Route::post('/api/bank-accounts/{bankAccount}/transactions', [BankAccountController::class, 'storeTransaction']);
            Route::delete('/api/bank-accounts/{bankAccount}/transactions/{transaction}', [BankAccountController::class, 'deleteTransaction']);
            Route::post('/api/bank-accounts/transfer', [BankAccountController::class, 'transfer']);
        });

        // Bank Reconciliations
        Route::middleware('permission:finance.bank.view')->group(function () {
            Route::get('/bank-reconciliations', [BankReconciliationController::class, 'index'])->name('finance.bank-reconciliations');
        });
        Route::middleware('permission:finance.bank.reconcile')->group(function () {
            Route::get('/bank-accounts/{bankAccount}/reconcile', [BankReconciliationController::class, 'reconcile'])->name('finance.bank-accounts.reconcile');
            Route::post('/api/bank-accounts/{bankAccount}/reconcile/start', [BankReconciliationController::class, 'start']);
            Route::get('/bank-reconciliations/{reconciliation}', [BankReconciliationController::class, 'show'])->name('finance.bank-reconciliations.show');
            Route::post('/api/bank-reconciliations/{reconciliation}/match', [BankReconciliationController::class, 'match']);
            Route::post('/api/bank-reconciliations/{reconciliation}/unmatch/{transactionId}', [BankReconciliationController::class, 'unmatch']);
            Route::post('/api/bank-reconciliations/{reconciliation}/complete', [BankReconciliationController::class, 'complete']);
            Route::post('/api/bank-reconciliations/{reconciliation}/cancel', [BankReconciliationController::class, 'cancel']);
            Route::post('/api/bank-reconciliations/{reconciliation}/adjustment', [BankReconciliationController::class, 'adjustment']);
            Route::get('/api/bank-reconciliations/{reconciliation}/summary', [BankReconciliationController::class, 'summary']);
        });

        // Financial Reports
        Route::middleware('permission:finance.reports.trial_balance')->group(function () {
            Route::get('/reports/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('finance.reports.trial-balance');
            Route::get('/api/reports/trial-balance', [FinancialReportController::class, 'getTrialBalance']);
        });
        Route::middleware('permission:finance.reports.profit_loss')->group(function () {
            Route::get('/reports/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('finance.reports.profit-loss');
            Route::get('/api/reports/profit-loss', [FinancialReportController::class, 'getProfitLoss']);
        });
        Route::middleware('permission:finance.reports.balance_sheet')->group(function () {
            Route::get('/reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('finance.reports.balance-sheet');
            Route::get('/api/reports/balance-sheet', [FinancialReportController::class, 'getBalanceSheet']);
        });
    });
});