<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
    recentSales: Array,
    recentPurchases: Array,
    recentMovements: Array,
});

const product = computed(() => props.product);

// Stock status badge classes
const stockStatusColors = {
    in_stock: 'badge-success',
    low_stock: 'badge-warning',
    out_of_stock: 'badge-danger',
    no_stock: 'badge-secondary',
};

const stockStatusLabels = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
    no_stock: 'No Stock',
};

// SO/PO status badge classes
const orderStatusColors = {
    draft: 'badge-warning',
    confirmed: 'badge-info',
    processing: 'badge-primary',
    shipped: 'badge-purple',
    delivered: 'badge-success',
    partial: 'badge-primary',
    received: 'badge-success',
    cancelled: 'badge-danger',
};

// Movement type colors and labels
const movementTypeColors = {
    purchase_in: 'badge-success',
    sales_out: 'badge-danger',
    adjustment_in: 'badge-success',
    adjustment_out: 'badge-danger',
    transfer_in: 'badge-info',
    transfer_out: 'badge-warning',
    return_in: 'badge-info',
    return_out: 'badge-info',
    initial: 'badge-secondary',
};

// Format helpers
const formatNumber = (value) => {
    if (value == null) return '0';
    const num = Number(value);
    return num % 1 === 0 ? num.toLocaleString('id-ID') : num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency, minimumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Get product initials for avatar
const productInitials = computed(() => {
    const name = product.value?.name || '';
    if (name.split(' ').length > 1) {
        return name.split(' ').map(word => word[0]?.toUpperCase()).slice(0, 2).join('');
    }
    return name[0]?.toUpperCase() || 'P';
});

// Format profit margin
const formatProfitMargin = (value) => {
    if (value == null) return '-';
    return `${Number(value).toFixed(1)}%`;
};
</script>

<template>
    <AppLayout title="Product Detail">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/products/list" class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                        {{ product?.name }}
                    </h2>
                    <span v-if="product?.is_active" class="badge badge-outline badge-success">Active</span>
                    <span v-else class="badge badge-outline badge-danger">Inactive</span>
                    <span
                        class="badge"
                        :class="stockStatusColors[product?.stock_status] || 'badge-secondary'">
                        {{ stockStatusLabels[product?.stock_status] || product?.stock_status }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/products/edit/${product?.id}`" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-pencil me-1"></i> Edit Product
                    </Link>
                    <Link :href="route('inventory.adjustments.create', { product_id: product?.id })" class="btn btn-sm btn-primary">
                        <i class="ki-filled ki-plus-squared me-1"></i> Adjust Stock
                    </Link>
                </div>
            </div>
        </template>

        <div class="container-fixed py-5">
            <!-- Action Bar - Always Visible -->
            <div class="card mb-5">
                <div class="card-body py-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <Link href="/products/list" class="btn btn-icon btn-light btn-sm">
                                <i class="ki-filled ki-arrow-left"></i>
                            </Link>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ product?.name }}</h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <span v-if="product?.is_active" class="badge badge-sm badge-outline badge-success">Active</span>
                                    <span v-else class="badge badge-sm badge-outline badge-danger">Inactive</span>
                                    <span
                                        class="badge badge-sm"
                                        :class="stockStatusColors[product?.stock_status] || 'badge-secondary'">
                                        {{ stockStatusLabels[product?.stock_status] || product?.stock_status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Link :href="`/products/edit/${product?.id}`" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-pencil me-1"></i> Edit Product
                            </Link>
                            <Link :href="route('inventory.adjustments.create', { product_id: product?.id })" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-plus-squared me-1"></i> Adjust Stock
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Product Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Product Information</h3>
                        </div>
                        <div class="card-body">
                            <!-- Product Header with Avatar -->
                            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl mb-5">
                                <div class="flex items-center justify-center w-20 h-20 rounded-xl bg-teal-100 text-teal-700 font-bold text-2xl shrink-0">
                                    {{ productInitials }}
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ product?.name }}</h4>
                                    <p class="text-sm text-gray-500">{{ product?.description || 'No description available' }}</p>
                                </div>
                            </div>

                            <!-- Product Details Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">SKU</span>
                                    <p class="font-medium">{{ product?.sku || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Barcode</span>
                                    <p class="font-medium">{{ product?.barcode || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Category</span>
                                    <p>
                                        <span v-if="product?.category" class="badge badge-outline badge-secondary badge-sm">
                                            {{ product?.category }}
                                        </span>
                                        <span v-else>-</span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Brand</span>
                                    <p class="font-medium">{{ product?.brand || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Supplier</span>
                                    <p class="font-medium">{{ product?.supplier || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Unit of Measure</span>
                                    <p class="font-medium">{{ product?.uom || product?.uom_code || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Weight</span>
                                    <p class="font-medium">{{ product?.weight ? `${formatNumber(product.weight)} Kg` : '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Dimensions</span>
                                    <p class="font-medium">{{ product?.dimensions || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pricing Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Selling Price</span>
                                    <p class="text-lg font-semibold text-primary">{{ formatCurrency(product?.price, product?.currency || 'IDR') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Cost Price</span>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ formatCurrency(product?.cost_price, product?.currency || 'IDR') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Average Cost</span>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ formatCurrency(product?.average_cost, product?.currency || 'IDR') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Last Purchase Cost</span>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ formatCurrency(product?.last_cost, product?.currency || 'IDR') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Profit Margin</span>
                                    <p class="text-lg font-semibold" :class="product?.profit_margin > 0 ? 'text-success' : 'text-danger'">
                                        {{ formatProfitMargin(product?.profit_margin) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales History Card -->
                    <div v-if="recentSales && recentSales.length > 0" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sales History</h3>
                            <Link :href="`/sales-orders/list?product_id=${product?.id}`" class="btn btn-sm btn-light">
                                View All
                            </Link>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">SO Number</th>
                                            <th class="min-w-[150px]">Customer</th>
                                            <th class="w-[100px] text-center">Date</th>
                                            <th class="w-[80px] text-center">Qty</th>
                                            <th class="w-[120px] text-end">Unit Price</th>
                                            <th class="w-[120px] text-end">Subtotal</th>
                                            <th class="w-[100px] text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="sale in recentSales" :key="sale.so_number">
                                            <td>
                                                <Link :href="`/sales-orders/detail/${sale.id}`" class="text-primary hover:underline">
                                                    {{ sale.so_number }}
                                                </Link>
                                            </td>
                                            <td>{{ sale.customer }}</td>
                                            <td class="text-center">{{ formatDate(sale.date) }}</td>
                                            <td class="text-center">{{ formatNumber(sale.quantity) }}</td>
                                            <td class="text-end">{{ formatCurrency(sale.unit_price) }}</td>
                                            <td class="text-end font-medium">{{ formatCurrency(sale.subtotal) }}</td>
                                            <td class="text-center">
                                                <span :class="['badge badge-sm', orderStatusColors[sale.status] || 'badge-secondary']">
                                                    {{ sale.status_label || sale.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase History Card -->
                    <div v-if="recentPurchases && recentPurchases.length > 0" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Purchase History</h3>
                            <Link :href="`/purchase-orders/list?product_id=${product?.id}`" class="btn btn-sm btn-light">
                                View All
                            </Link>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">PO Number</th>
                                            <th class="min-w-[150px]">Supplier</th>
                                            <th class="w-[100px] text-center">Date</th>
                                            <th class="w-[80px] text-center">Qty</th>
                                            <th class="w-[120px] text-end">Unit Cost</th>
                                            <th class="w-[120px] text-end">Subtotal</th>
                                            <th class="w-[100px] text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="purchase in recentPurchases" :key="purchase.po_number">
                                            <td>
                                                <Link :href="`/purchase-orders/detail/${purchase.id}`" class="text-primary hover:underline">
                                                    {{ purchase.po_number }}
                                                </Link>
                                            </td>
                                            <td>{{ purchase.supplier }}</td>
                                            <td class="text-center">{{ formatDate(purchase.date) }}</td>
                                            <td class="text-center">{{ formatNumber(purchase.quantity) }}</td>
                                            <td class="text-end">{{ formatCurrency(purchase.unit_cost) }}</td>
                                            <td class="text-end font-medium">{{ formatCurrency(purchase.subtotal) }}</td>
                                            <td class="text-center">
                                                <span :class="['badge badge-sm', orderStatusColors[purchase.status] || 'badge-secondary']">
                                                    {{ purchase.status_label || purchase.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary -->
                <div class="space-y-5">
                    <!-- Inventory Status Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Inventory Status</h3>
                            <span
                                class="badge"
                                :class="stockStatusColors[product?.stock_status] || 'badge-secondary'">
                                {{ stockStatusLabels[product?.stock_status] || product?.stock_status }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Quantity On Hand</span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(product?.quantity_on_hand) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Available</span>
                                    <span class="text-lg font-semibold text-success">{{ formatNumber(product?.quantity_available) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Reserved</span>
                                    <span class="text-lg font-semibold text-warning">{{ formatNumber(product?.quantity_reserved) }}</span>
                                </div>
                                <div class="border-t pt-3 flex justify-between items-center">
                                    <span class="text-gray-600">Reorder Level</span>
                                    <span class="font-medium">{{ formatNumber(product?.reorder_level) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Last Movement</span>
                                    <span class="text-sm">{{ formatDate(product?.last_movement_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-2">
                                <Link :href="`/products/edit/${product?.id}`" class="btn btn-light w-full justify-start">
                                    <i class="ki-filled ki-pencil me-2"></i>
                                    Edit Product
                                </Link>
                                <Link :href="route('inventory.adjustments.create', { product_id: product?.id })" class="btn btn-light w-full justify-start">
                                    <i class="ki-filled ki-plus-squared me-2"></i>
                                    Adjust Stock
                                </Link>
                                <Link :href="`/inventory/movements?product_id=${product?.id}`" class="btn btn-light w-full justify-start">
                                    <i class="ki-filled ki-arrow-mix me-2"></i>
                                    View Movements
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Stock Movements Card -->
                    <div v-if="recentMovements && recentMovements.length > 0" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Movements</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <div v-for="(movement, index) in recentMovements.slice(0, 10)" :key="index" class="p-3 hover:bg-gray-50 dark:hover:bg-coal-600">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span
                                                    class="badge badge-sm"
                                                    :class="movementTypeColors[movement.type] || 'badge-secondary'">
                                                    {{ movement.type_label || movement.type }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ formatDate(movement.date) }}</span>
                                            </div>
                                            <div class="text-sm">
                                                <span v-if="movement.type?.includes('_in')" class="text-success font-medium">
                                                    +{{ formatNumber(movement.quantity) }}
                                                </span>
                                                <span v-else-if="movement.type?.includes('_out')" class="text-danger font-medium">
                                                    -{{ formatNumber(movement.quantity) }}
                                                </span>
                                                <span v-else class="font-medium">
                                                    {{ formatNumber(movement.quantity) }}
                                                </span>
                                                <span class="text-gray-500 text-xs ms-2">
                                                    ({{ formatNumber(movement.quantity_before) }} &rarr; {{ formatNumber(movement.quantity_after) }})
                                                </span>
                                            </div>
                                            <p v-if="movement.notes" class="text-xs text-gray-500 truncate mt-1">{{ movement.notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                                <Link :href="`/inventory/movements?product_id=${product?.id}`" class="text-primary text-sm hover:underline">
                                    View all movements
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- No Movements Message -->
                    <div v-else class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Movements</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="ki-filled ki-arrow-mix text-4xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500 text-sm">No recent movements</p>
                            </div>
                        </div>
                    </div>

                    <!-- Record Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Record Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created At</span>
                                    <span>{{ formatDateTime(product?.created_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Updated At</span>
                                    <span>{{ formatDateTime(product?.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
