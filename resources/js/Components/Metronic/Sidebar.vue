<script setup>
    import { ref, computed, watch, watchEffect } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';

    // set reactive variable to check the dark mode
    const darkMode = ref(false);

    // watch for changes in the data-theme-mode attribute
    watchEffect(() => {
        const themeMode = document.documentElement.getAttribute('data-theme-mode');
        darkMode.value = themeMode === 'dark';
    });

    // Access the Inertia page object
    const page = usePage();

    // Access the current URL from the `page` object
    const currentPath = computed(() => page.url);

    // Function to check if a menu item is active
    const isActive = (path) => {
        if (path.endsWith('*')) {
            // Handle wildcard matching
            const basePath = path.slice(0, -1); // Remove the '*' character
            return currentPath.value.startsWith(basePath);
        }
        return currentPath.value === path;
    };

    // Get the company logo from the props
    const companyLogo = computed(() => {
        const logo = page.props.company?.logo;
        return logo ? `/storage/${logo}` : null;
    });

    // Check for user required permissions (using dot notation)
    const userPermissions = computed(() => page.props.user?.permissions || []);

    const hasPermission = (permission) => {
        return userPermissions.value.includes(permission);
    };

    const hasRolePermissions = computed(() => hasPermission('roles.view'));
    const hasUserPermissions = computed(() => hasPermission('users.view'));
    const hasAccessManagementPermissions = computed(() => hasPermission('users.view') || hasPermission('roles.view'));
    const hasTransactionPermissions = computed(() => hasPermission('transactions.view'));
    const hasCustomerPermissions = computed(() => hasPermission('customers.view'));
    const hasProductPermissions = computed(() => hasPermission('products.view'));
    const hasBrandPermissions = computed(() => hasPermission('brands.view'));
    const hasLogPermissions = computed(() => hasPermission('logs.view'));
    const hasProductCategoryPermissions = computed(() => hasPermission('product-categories.view'));
    const hasInventoryAdjustmentPermissions = computed(() => hasPermission('inventory.adjustments.view'));
    const hasUomPermissions = computed(() => hasPermission('uom.view'));
    const hasFinancePermissions = computed(() =>
        hasPermission('finance.settings.view') ||
        hasPermission('finance.chart_of_accounts.view') ||
        hasPermission('finance.fiscal_periods.view') ||
        hasPermission('finance.journal_entries.view') ||
        hasPermission('finance.expenses.view') ||
        hasPermission('finance.reports.ar_aging') ||
        hasPermission('finance.reports.ap_aging') ||
        hasPermission('finance.reports.trial_balance') ||
        hasPermission('finance.reports.profit_loss') ||
        hasPermission('finance.reports.balance_sheet') ||
        hasPermission('finance.bank.view')
    );

</script>
<template>
    <!-- Sidebar -->
    <div class="sidebar dark:bg-coal-600 bg-light border-e border-e-gray-200 dark:border-e-coal-100 fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0"
        data-drawer="true" data-drawer-class="drawer drawer-start top-0 bottom-0" data-drawer-enable="true|lg:false"
        id="sidebar">
        <div class="sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0"
            id="sidebar_header">
            <a v-if="!darkMode" href="/dashboard">
                <img v-if="companyLogo" :src="companyLogo" class="default-logo min-h-[22px] h-auto max-h-[32px] max-w-[150px]" />
                <img v-else class="default-logo min-h-[22px] max-w-[150px]" src="/assets/media/app/logo.png" />
                <img class="small-logo min-h-[22px] max-w-[35px]" src="/assets/media/app/bkpi_square_logo.png" />
            </a>
            <a v-else href="/dashboard">
                <img v-if="companyLogo" :src="companyLogo" class="default-logo min-h-[22px] max-w-[150px]" />
                <img v-else class="default-logo min-h-[22px] max-w-[150px]" src="/assets/media/app/logo.png" />
                <img class="small-logo min-h-[22px] max-w-[35px]" src="/assets/media/app/bkpi_square_logo.png" />
            </a>
            <button
                class="btn btn-icon btn-icon-md size-[30px] rounded-lg border border-gray-200 dark:border-gray-300 bg-light text-gray-500 hover:text-gray-700 toggle absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
                data-toggle="body" data-toggle-class="sidebar-collapse" id="sidebar_toggle">
                <i
                    class="ki-filled ki-black-left-line toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:toggle-active:rotate-0">
                </i>
            </button>
        </div>
        <div class="sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
            <div class="scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3" data-scrollable="true"
                data-scrollable-dependencies="#sidebar_header" data-scrollable-height="auto"
                data-scrollable-offset="0px" data-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
                <!-- Sidebar Menu -->
                <div class="menu flex flex-col grow gap-0.5" data-menu="true" data-menu-accordion-expand-all="false"
                    id="sidebar_menu">
                    <Link :class="['menu-item', isActive('/dashboard') ? 'active' : '']" :href="isActive('/dashboard') ? '#' : route('dashboard')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-element-11 text-lg">
                                </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Dashboards
                            </span>
                        </div>
                    </Link>
                    <div class="menu-item pt-2.25 pb-px">
                        <span class="menu-heading uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]">
                            Menu
                        </span>
                    </div>
                    <!--User & Access Management (consolidated)-->
                    <Link v-if="hasAccessManagementPermissions" :class="['menu-item', isActive('/access-management*') ? 'active' : '']" :href="isActive('/access-management') ? '#' : route('access-management.index')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-setting-2 text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Access Management
                            </span>
                        </div>
                    </Link>
                    <!-- Customer -->
                    <Link v-if="hasCustomerPermissions" :class="['menu-item', isActive('/customer*') ? 'active' : '']" :href="isActive('/customer/list') ? '#' : route('customer.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-user text-lg">
                                </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Customers
                            </span>
                        </div>
                    </Link>
                    <!-- Transactions (Expandable) -->
                    <div v-if="hasTransactionPermissions" class="menu-item" :class="{ 'here show': isActive('/purchase-orders*') || isActive('/sales-orders*') || isActive('/payments*') || isActive('/inventory*') }"
                        data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-dollar text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Transactions
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"></i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"></i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <Link :class="['menu-item', isActive('/purchase-orders*') ? 'active' : '']" :href="route('purchase-orders.list')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-entrance-left text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Purchase Orders
                                    </span>
                                </div>
                            </Link>
                            <Link :class="['menu-item', isActive('/sales-orders*') ? 'active' : '']" :href="route('sales-orders.list')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-exit-left text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Sales Orders
                                    </span>
                                </div>
                            </Link>
                            <Link :class="['menu-item', isActive('/payments*') ? 'active' : '']" :href="route('payments.list')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-wallet text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Payments
                                    </span>
                                </div>
                            </Link>
                            <Link :class="['menu-item', isActive('/inventory/list') || isActive('/inventory/product*') || isActive('/inventory/movements') ? 'active' : '']" :href="route('inventory.list')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-parcel text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Inventory
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasInventoryAdjustmentPermissions" :class="['menu-item', isActive('/inventory/adjustments*') ? 'active' : '']" :href="route('inventory.adjustments')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-notepad-edit text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Stock Adjustments
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <Link v-if="hasBrandPermissions" :class="['menu-item', isActive('/brand*') ? 'active' : '']" :href="isActive('/brand/list') ? '#' : route('brand.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-shop text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Brands
                            </span>
                        </div>
                    </Link>
                    
                    <Link v-if="hasProductPermissions" :class="['menu-item', isActive('/products*') ? 'active' : '']" :href="isActive('/products/list') ? '#' : route('product.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-handcart text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Products
                            </span>
                        </div>
                    </Link>
                    
                    <Link v-if="hasProductCategoryPermissions" :class="['menu-item', isActive('/product-category*') ? 'active' : '']" :href="isActive('/product-category/list') ? '#' : route('product-category.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-folder text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Product Categories
                            </span>
                        </div>
                    </Link>
                    <!-- Units of Measure (Expandable) -->
                    <div v-if="hasUomPermissions" class="menu-item" :class="{ 'here show': isActive('/uom*') || isActive('/uom-category*') }"
                        data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-abstract-26 text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Units of Measure
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"></i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"></i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <Link :class="['menu-item', isActive('/uom/list') || isActive('/uom/detail*') || isActive('/uom/edit*') ? 'active' : '']" :href="route('uom.list')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-questionnaire-tablet text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        UoM List
                                    </span>
                                </div>
                            </Link>
                            <Link :class="['menu-item', isActive('/uom-category*') ? 'active' : '']" :href="route('uom-category.list')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-category text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        UoM Categories
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <!-- Finance (Expandable) -->
                    <div v-if="hasFinancePermissions" class="menu-item" :class="{ 'here show': isActive('/finance*') }"
                        data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-chart-pie-4 text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Finance
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"></i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"></i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <Link v-if="hasPermission('finance.chart_of_accounts.view')" :class="['menu-item', isActive('/finance/chart-of-accounts*') ? 'active' : '']" :href="route('finance.chart-of-accounts')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-book text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Chart of Accounts
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.journal_entries.view')" :class="['menu-item', isActive('/finance/journal-entries*') ? 'active' : '']" href="/finance/journal-entries">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-notepad text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Journal Entries
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.expenses.view')" :class="['menu-item', isActive('/finance/expenses') && !isActive('/finance/expense-categories*') ? 'active' : '']" href="/finance/expenses">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-purchase text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Expenses
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.expenses.view')" :class="['menu-item', isActive('/finance/expense-categories*') ? 'active' : '']" href="/finance/expense-categories">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-category text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Expense Categories
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.bank.view')" :class="['menu-item', isActive('/finance/bank-accounts*') || isActive('/finance/bank-reconciliations*') ? 'active' : '']" href="/finance/bank-accounts">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-bank text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Bank Accounts
                                    </span>
                                </div>
                            </Link>
                            <!-- Reports Section -->
                            <Link v-if="hasPermission('finance.reports.ar_aging')" :class="['menu-item', isActive('/finance/reports/ar-aging*') ? 'active' : '']" href="/finance/reports/ar-aging">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-chart-pie-3 text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        AR Aging Report
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.reports.ap_aging')" :class="['menu-item', isActive('/finance/reports/ap-aging*') ? 'active' : '']" href="/finance/reports/ap-aging">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-chart text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        AP Aging Report
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.reports.trial_balance')" :class="['menu-item', isActive('/finance/reports/trial-balance*') ? 'active' : '']" href="/finance/reports/trial-balance">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-tablet-text-down text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Trial Balance
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.reports.profit_loss')" :class="['menu-item', isActive('/finance/reports/profit-loss*') ? 'active' : '']" href="/finance/reports/profit-loss">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-chart-line-up-2 text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Profit & Loss
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.reports.balance_sheet')" :class="['menu-item', isActive('/finance/reports/balance-sheet*') ? 'active' : '']" href="/finance/reports/balance-sheet">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-chart-pie-simple text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Balance Sheet
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.fiscal_periods.view')" :class="['menu-item', isActive('/finance/fiscal-periods*') ? 'active' : '']" :href="route('finance.fiscal-periods')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-calendar text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Fiscal Periods
                                    </span>
                                </div>
                            </Link>
                            <Link v-if="hasPermission('finance.settings.view')" :class="['menu-item', isActive('/finance/settings*') ? 'active' : '']" :href="route('finance.settings')">
                                <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
                                    <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                        <i class="ki-filled ki-setting-2 text-lg"></i>
                                    </span>
                                    <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                        Settings
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <Link v-if="hasLogPermissions" :class="['menu-item', isActive('/logs*') ? 'active' : '']" :href="isActive('/logs') ? '#' : '/logs'">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-message-text text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                System Logs
                            </span>
                        </div>
                    </Link>
                </div>
                <!-- End of Sidebar Menu -->
            </div>
        </div>
    </div>
    <!-- End of Sidebar -->
</template>