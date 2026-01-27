<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    stocks: Array,
    pagination: Object,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const selectedStockStatus = ref(props.filters?.stock_status || '');
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [10, 25, 50, 100];
const selectedPerPage = ref(props.pagination?.per_page || 10);

const fetchData = () => {
    router.get(route('inventory.list'), {
        search: searchQuery.value,
        stock_status: selectedStockStatus.value,
        per_page: selectedPerPage.value,
        page: currentPage.value,
    }, { preserveScroll: true, preserveState: true });
};

watch([currentPage, selectedPerPage], () => fetchData());

const performSearch = () => {
    currentPage.value = 1;
    fetchData();
};

const visiblePages = computed(() => {
    const total = props.pagination?.last_page || 1;
    const current = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    if (current <= 4) return [1, 2, 3, 4, '...', total];
    if (current >= total - 3) return [1, '...', total - 3, total - 2, total - 1, total];
    return [1, '...', current - 1, current, current + 1, '...', total];
});

const goToPage = (page) => {
    if (page !== '...') currentPage.value = page;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const stockStatusColors = {
    in_stock: 'bg-green-100 text-green-600 border-green-300',
    low_stock: 'bg-yellow-100 text-yellow-600 border-yellow-300',
    out_of_stock: 'bg-red-100 text-red-600 border-red-300',
};

const stockStatusLabels = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
};
</script>

<template>
    <AppLayout title="Inventory">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inventory Management
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Stats Summary -->
            <div class="py-5">
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-center justify-between gap-5">
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100">
                                    <i class="ki-filled ki-parcel text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_products) }}</div>
                                    <div class="text-xs text-gray-500">Total Products</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[180px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100">
                                    <i class="ki-filled ki-dollar text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(stats?.total_stock_value) }}</div>
                                    <div class="text-xs text-gray-500">Total Stock Value</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-100">
                                    <i class="ki-filled ki-notification-bing text-yellow-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-yellow-600">{{ formatNumber(stats?.low_stock_count) }}</div>
                                    <div class="text-xs text-gray-500">Low Stock Items</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-red-100">
                                    <i class="ki-filled ki-cross-circle text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-red-600">{{ formatNumber(stats?.out_of_stock_count) }}</div>
                                    <div class="text-xs text-gray-500">Out of Stock</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="flex gap-3 mb-5">
                <Link :href="route('inventory.movements')" class="btn btn-sm btn-light">
                    <i class="ki-filled ki-arrows-loop me-1"></i> Stock Movements
                </Link>
                <Link :href="route('inventory.low-stock')" class="btn btn-sm btn-light">
                    <i class="ki-filled ki-notification-bing me-1"></i> Low Stock Report
                </Link>
                <Link :href="route('inventory.valuation')" class="btn btn-sm btn-light">
                    <i class="ki-filled ki-chart-pie-simple me-1"></i> Valuation Report
                </Link>
            </div>

            <!-- Main Table Card -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header">
                        <h3 class="card-title">Inventory Stock</h3>
                        <div class="card-toolbar">
                            <div class="flex gap-3 items-center">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                    <input v-model="searchQuery" class="input input-sm ps-8 w-[200px]"
                                        placeholder="Search product..." @keyup.enter="performSearch" />
                                </div>
                                <!-- Stock Status Filter -->
                                <select v-model="selectedStockStatus" @change="performSearch" class="select select-sm w-[140px]">
                                    <option value="">All Status</option>
                                    <option value="available">In Stock</option>
                                    <option value="low">Low Stock</option>
                                    <option value="out">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="min-w-[200px]">Product</th>
                                        <th class="w-[100px] text-center">On Hand</th>
                                        <th class="w-[100px] text-center">Reserved</th>
                                        <th class="w-[100px] text-center">Available</th>
                                        <th class="w-[120px] text-end">Avg Cost</th>
                                        <th class="w-[140px] text-end">Stock Value</th>
                                        <th class="w-[100px] text-center">Status</th>
                                        <th class="w-[80px] text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="stock in stocks" :key="stock.id" class="hover:bg-slate-50">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-light text-primary font-bold">
                                                    {{ stock.product_name?.charAt(0).toUpperCase() }}
                                                </div>
                                                <div>
                                                    <Link :href="`/inventory/product/${stock.product_id}`"
                                                        class="font-medium text-gray-800 hover:text-primary">
                                                        {{ stock.product_name }}
                                                    </Link>
                                                    <div class="text-xs text-gray-500">SKU: {{ stock.sku }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center font-medium">{{ formatNumber(stock.quantity_on_hand) }}</td>
                                        <td class="text-center text-orange-600">{{ formatNumber(stock.quantity_reserved) }}</td>
                                        <td class="text-center">
                                            <span :class="stock.quantity_available > 0 ? 'text-success font-medium' : 'text-danger font-medium'">
                                                {{ formatNumber(stock.quantity_available) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ formatCurrency(stock.average_cost) }}</td>
                                        <td class="text-end font-medium">{{ formatCurrency(stock.stock_value) }}</td>
                                        <td class="text-center">
                                            <span :class="`text-xs rounded-lg px-2 py-1 border ${stockStatusColors[stock.status]}`">
                                                {{ stockStatusLabels[stock.status] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/inventory/product/${stock.product_id}`"
                                                class="btn btn-sm btn-icon btn-light">
                                                <i class="ki-filled ki-eye"></i>
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!stocks || stocks.length === 0">
                                        <td colspan="8">
                                            <div class="flex items-center justify-center h-40">
                                                <div class="flex flex-col items-center">
                                                    <i class="ki-filled ki-parcel text-6xl text-gray-300 mb-3"></i>
                                                    <span class="text-gray-500">No inventory records found</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="card-footer justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                            <div class="flex items-center gap-2">
                                Show
                                <select v-model="selectedPerPage" class="select select-sm w-16">
                                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                per page
                            </div>
                            <span v-if="pagination">
                                Showing {{ stocks?.length || 0 }} of {{ pagination.total }} results
                            </span>
                            <div class="pagination flex items-center">
                                <button class="btn" :disabled="currentPage <= 1" @click="currentPage--">
                                    <i class="ki-outline ki-black-left"></i>
                                </button>
                                <span v-for="page in visiblePages" :key="page" class="btn"
                                    :class="{ active: page === currentPage }" @click="goToPage(page)">
                                    {{ page }}
                                </span>
                                <button class="btn" :disabled="currentPage >= pagination?.last_page" @click="currentPage++">
                                    <i class="ki-outline ki-black-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
