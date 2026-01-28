<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// Props from controller
const props = defineProps({
    brand: Object,
    products: Array,
});

// Computed brand data
const brand = computed(() => props.brand);

// Format helpers
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Get brand initials for avatar
const brandInitials = computed(() => {
    const name = brand.value?.name || '';
    if (name.split(' ').length > 1) {
        return name.split(' ').map(word => word[0]?.toUpperCase()).slice(0, 2).join('');
    }
    return name[0]?.toUpperCase() || 'B';
});
</script>

<template>
    <AppLayout title="Brand Detail">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/brand/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ brand?.name }}
                </h2>
            </div>
        </template>

        <!-- Main Content Container -->
        <div class="container-fixed py-5">
            <!-- Action Bar - Always Visible -->
            <div class="card mb-5">
                <div class="card-body py-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <Link href="/brand/list" class="btn btn-icon btn-light btn-sm">
                                <i class="ki-filled ki-arrow-left"></i>
                            </Link>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ brand?.name }}</h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="badge badge-sm badge-outline badge-secondary">
                                        {{ brand?.products_count || 0 }} products
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Link :href="`/brand/edit/${brand?.id}`" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-pencil me-1"></i> Edit Brand
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Details (2/3 width) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Brand Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-tag text-gray-500 me-2"></i>
                                Brand Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Brand Header with Avatar -->
                            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl mb-5">
                                <div class="flex items-center justify-center w-20 h-20 rounded-xl bg-primary-light text-primary font-bold text-2xl shrink-0">
                                    {{ brandInitials }}
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ brand?.name }}</h4>
                                    <p class="text-sm text-gray-500">Brand ID: {{ brand?.id }}</p>
                                </div>
                            </div>

                            <!-- Brand Details Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Name</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ brand?.name || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Created At</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDateTime(brand?.created_at) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Updated At</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDateTime(brand?.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-package text-gray-500 me-2"></i>
                                Products
                            </h3>
                            <Link
                                v-if="brand?.products_count > 10"
                                :href="`/products/list?brand_id=${brand?.id}`"
                                class="btn btn-sm btn-light">
                                View All Products
                            </Link>
                        </div>
                        <div class="card-body p-0">
                            <!-- Products Table -->
                            <div v-if="products && products.length > 0" class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[200px]">Name</th>
                                            <th class="w-[120px]">SKU</th>
                                            <th class="w-[150px]">Category</th>
                                            <th class="w-[130px] text-end">Selling Price</th>
                                            <th class="w-[80px] text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <!-- Product Avatar -->
                                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-teal-100 text-teal-700 font-bold text-sm shrink-0">
                                                        {{ product.name?.charAt(0)?.toUpperCase() || 'P' }}
                                                    </div>
                                                    <!-- Product Name -->
                                                    <Link
                                                        :href="`/products/detail/${product.id}`"
                                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary">
                                                        {{ product.name }}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td class="text-gray-600">
                                                {{ product.sku || '-' }}
                                            </td>
                                            <td>
                                                <span v-if="product.category_name" class="badge badge-sm badge-outline badge-secondary">
                                                    {{ product.category_name }}
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="text-end font-medium text-gray-900 dark:text-white">
                                                {{ formatCurrency(product.price) }}
                                            </td>
                                            <td class="text-center">
                                                <Link
                                                    :href="`/products/detail/${product.id}`"
                                                    class="btn btn-sm btn-icon btn-light">
                                                    <i class="ki-filled ki-eye"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="flex flex-col items-center justify-center py-10">
                                <i class="ki-filled ki-package text-6xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No products found for this brand</p>
                                <Link href="/products/create" class="btn btn-sm btn-light mt-4">
                                    <i class="ki-filled ki-plus-squared me-1"></i>
                                    Add Product
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary (1/3 width) -->
                <div class="space-y-5">
                    <!-- Quick Stats Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-chart-simple text-gray-500 me-2"></i>
                                Quick Stats
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-primary-light">
                                    <i class="ki-filled ki-package text-primary text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ brand?.products_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Total Products</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-setting-2 text-gray-500 me-2"></i>
                                Actions
                            </h3>
                        </div>
                        <div class="card-body space-y-3">
                            <!-- Edit Brand -->
                            <Link :href="`/brand/edit/${brand?.id}`" class="btn btn-primary w-full">
                                <i class="ki-filled ki-pencil me-2"></i>
                                Edit Brand
                            </Link>

                            <!-- Back to List -->
                            <Link href="/brand/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to Brand List
                            </Link>
                        </div>
                    </div>

                    <!-- Record Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-information-2 text-gray-500 me-2"></i>
                                Record Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created At</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatDateTime(brand?.created_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Updated At</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatDateTime(brand?.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
