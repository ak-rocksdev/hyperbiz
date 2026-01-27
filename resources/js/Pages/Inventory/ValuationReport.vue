<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stocks: Array,
    totalValue: Number,
    pagination: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

// Calculate percentage of total
const getPercentage = (value) => {
    if (!props.totalValue || props.totalValue === 0) return 0;
    return ((value / props.totalValue) * 100).toFixed(1);
};
</script>

<template>
    <AppLayout title="Stock Valuation Report">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('inventory.list')" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Stock Valuation Report
                </h2>
            </div>
        </template>

        <div class="container-fixed py-5">
            <!-- Total Value Card -->
            <div class="card mb-5">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Inventory Value</p>
                            <p class="text-4xl font-bold text-primary">{{ formatCurrency(totalValue) }}</p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary-light">
                            <i class="ki-filled ki-chart-pie-simple text-primary text-3xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stock Valuation by Product</h3>
                    <span class="text-sm text-gray-500">Sorted by value (highest first)</span>
                </div>
                <div class="card-body p-0">
                    <div class="scrollable-x-auto">
                        <table class="table table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">Product</th>
                                    <th class="w-[100px] text-center">On Hand</th>
                                    <th class="w-[140px] text-end">Avg Cost</th>
                                    <th class="w-[160px] text-end">Stock Value</th>
                                    <th class="w-[120px] text-center">% of Total</th>
                                    <th class="w-[80px] text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="stock in stocks" :key="stock.id" class="hover:bg-slate-50">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 font-bold">
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
                                    <td class="text-end">{{ formatCurrency(stock.average_cost) }}</td>
                                    <td class="text-end font-bold text-primary">{{ formatCurrency(stock.stock_value) }}</td>
                                    <td class="text-center">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-primary rounded-full"
                                                    :style="{ width: getPercentage(stock.stock_value) + '%' }"></div>
                                            </div>
                                            <span class="text-sm text-gray-600 w-12">{{ getPercentage(stock.stock_value) }}%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <Link :href="`/inventory/product/${stock.product_id}`"
                                            class="btn btn-sm btn-icon btn-light">
                                            <i class="ki-filled ki-eye"></i>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!stocks || stocks.length === 0">
                                    <td colspan="6">
                                        <div class="flex items-center justify-center h-40">
                                            <div class="flex flex-col items-center">
                                                <i class="ki-filled ki-parcel text-6xl text-gray-300 mb-3"></i>
                                                <span class="text-gray-500">No inventory records found</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="stocks && stocks.length > 0">
                                <tr class="bg-gray-50 font-bold">
                                    <td colspan="3" class="text-end">Total:</td>
                                    <td class="text-end text-primary text-lg">{{ formatCurrency(totalValue) }}</td>
                                    <td class="text-center">100%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
