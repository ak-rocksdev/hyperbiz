<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user || page.props.user);

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount || 0);
};

// Get status badge class
const getStatusBadgeClass = (status) => {
    const classes = {
        'completed': 'badge-success',
        'pending': 'badge-warning',
        'cancelled': 'badge-danger',
        'processing': 'badge-info',
    };
    return classes[status?.toLowerCase()] || 'badge-secondary';
};

// Check permission
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Get greeting from server (uses server timezone)
const greeting = computed(() => props.stats?.greeting || 'Hello');
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="container-fixed">
            <div class="py-5">
                <!-- Welcome Section -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">
                        {{ greeting }}, {{ user?.name?.split(' ')[0] }}!
                    </h1>
                    <p class="text-gray-500">Here's what's happening with your business today.</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                    <!-- Users -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-people text-primary text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_users || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Users</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customers -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-user text-info text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_customers || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Customers</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-dollar text-success text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_transactions || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Transactions</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-handcart text-warning text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_products || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Products</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brands -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-shop text-danger text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_brands || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Brands</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-folder text-secondary text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_categories || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Categories</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Recent Transactions -->
                    <div class="lg:col-span-2">
                        <div class="card h-full">
                            <div class="card-header border-b border-gray-200 px-5 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-semibold text-gray-900">Recent Transactions</h3>
                                    <Link
                                        v-if="hasPermission('transactions.view')"
                                        :href="route('transaction.list')"
                                        class="btn btn-sm btn-light"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="stats?.recent_transactions?.length" class="overflow-x-auto">
                                    <table class="table-auto w-full">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Transaction</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Customer</th>
                                                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                                <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="trx in stats.recent_transactions" :key="trx.id" class="hover:bg-gray-50">
                                                <td class="px-5 py-3">
                                                    <div class="font-medium text-gray-900 text-sm">{{ trx.transaction_code }}</div>
                                                    <div class="text-xs text-gray-500">{{ trx.transaction_date }}</div>
                                                </td>
                                                <td class="px-5 py-3 text-sm text-gray-700">{{ trx.customer_name }}</td>
                                                <td class="px-5 py-3 text-sm text-gray-900 text-right font-medium">{{ formatCurrency(trx.grand_total) }}</td>
                                                <td class="px-5 py-3 text-center">
                                                    <span class="badge badge-sm capitalize" :class="getStatusBadgeClass(trx.status)">
                                                        {{ trx.status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                                    <i class="ki-filled ki-dollar text-4xl mb-3"></i>
                                    <p class="text-sm">No recent transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Access -->
                    <div class="lg:col-span-1">
                        <div class="card h-full">
                            <div class="card-header border-b border-gray-200 px-5 py-4">
                                <h3 class="text-base font-semibold text-gray-900">Quick Access</h3>
                            </div>
                            <div class="card-body p-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Access Management -->
                                    <Link
                                        v-if="hasPermission('users.view') || hasPermission('roles.view')"
                                        :href="route('access-management.index')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-primary hover:bg-primary/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                                            <i class="ki-filled ki-setting-2 text-primary text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">Access Management</span>
                                    </Link>

                                    <!-- Transactions -->
                                    <Link
                                        v-if="hasPermission('transactions.view')"
                                        :href="route('transaction.list')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-success hover:bg-success/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center group-hover:bg-success/20 transition-colors">
                                            <i class="ki-filled ki-dollar text-success text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">Transactions</span>
                                    </Link>

                                    <!-- Customers -->
                                    <Link
                                        v-if="hasPermission('customers.view')"
                                        :href="route('customer.list')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-info hover:bg-info/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center group-hover:bg-info/20 transition-colors">
                                            <i class="ki-filled ki-user text-info text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">Customers</span>
                                    </Link>

                                    <!-- Products -->
                                    <Link
                                        v-if="hasPermission('products.view')"
                                        :href="route('product.list')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-warning hover:bg-warning/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center group-hover:bg-warning/20 transition-colors">
                                            <i class="ki-filled ki-handcart text-warning text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">Products</span>
                                    </Link>

                                    <!-- Brands -->
                                    <Link
                                        v-if="hasPermission('brands.view')"
                                        :href="route('brand.list')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-danger hover:bg-danger/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center group-hover:bg-danger/20 transition-colors">
                                            <i class="ki-filled ki-shop text-danger text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">Brands</span>
                                    </Link>

                                    <!-- Categories -->
                                    <Link
                                        v-if="hasPermission('product-categories.view')"
                                        :href="route('product-category.list')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-secondary hover:bg-secondary/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center group-hover:bg-secondary/20 transition-colors">
                                            <i class="ki-filled ki-folder text-secondary text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">Categories</span>
                                    </Link>

                                    <!-- System Logs -->
                                    <Link
                                        v-if="hasPermission('logs.view')"
                                        :href="route('logs.index')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border border-gray-200 hover:border-gray-400 hover:bg-gray-50 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition-colors">
                                            <i class="ki-filled ki-message-text text-gray-600 text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 text-center">System Logs</span>
                                    </Link>

                                    <!-- Create Transaction -->
                                    <Link
                                        v-if="hasPermission('transactions.create')"
                                        :href="route('transaction.create')"
                                        class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-primary hover:bg-primary/5 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                                            <i class="ki-filled ki-plus-squared text-gray-500 group-hover:text-primary text-xl"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 group-hover:text-primary text-center">New Transaction</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
