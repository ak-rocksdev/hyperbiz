<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stocks: Array,
    pagination: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};
</script>

<template>
    <AppLayout title="Low Stock Report">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('inventory.list')" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Low Stock Report
                </h2>
            </div>
        </template>

        <div class="container-fixed py-5">
            <!-- Alert Banner -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-5">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="ki-filled ki-notification-bing text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>{{ stocks?.length || 0 }} products</strong> are below their reorder level and may need restocking.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Products Below Reorder Level</h3>
                </div>
                <div class="card-body p-0">
                    <div class="scrollable-x-auto">
                        <table class="table table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">Product</th>
                                    <th class="w-[100px] text-center">Available</th>
                                    <th class="w-[120px] text-center">Reorder Level</th>
                                    <th class="w-[100px] text-center">Shortage</th>
                                    <th class="w-[140px] text-end">Avg Cost</th>
                                    <th class="w-[140px] text-end">Est. Restock Cost</th>
                                    <th class="w-[80px] text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="stock in stocks" :key="stock.id" class="hover:bg-slate-50">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 font-bold">
                                                <i class="ki-filled ki-notification-bing"></i>
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
                                    <td class="text-center">
                                        <span :class="stock.quantity_available <= 0 ? 'text-danger font-bold' : 'text-warning font-medium'">
                                            {{ formatNumber(stock.quantity_available) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ formatNumber(stock.reorder_level) }}</td>
                                    <td class="text-center">
                                        <span class="text-danger font-bold">{{ formatNumber(stock.shortage) }}</span>
                                    </td>
                                    <td class="text-end">{{ formatCurrency(stock.average_cost) }}</td>
                                    <td class="text-end font-medium text-primary">
                                        {{ formatCurrency(stock.shortage * stock.average_cost) }}
                                    </td>
                                    <td class="text-center">
                                        <Link :href="`/inventory/product/${stock.product_id}`"
                                            class="btn btn-sm btn-icon btn-light">
                                            <i class="ki-filled ki-eye"></i>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!stocks || stocks.length === 0">
                                    <td colspan="7">
                                        <div class="flex items-center justify-center h-40">
                                            <div class="flex flex-col items-center text-green-600">
                                                <i class="ki-filled ki-check-circle text-6xl mb-3"></i>
                                                <span class="font-medium">All products are well stocked!</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
