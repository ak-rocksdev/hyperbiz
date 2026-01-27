<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    movements: Array,
    pagination: Object,
    filters: Object,
    filterProduct: Object, // { id, name, sku } | null - When filtered by product
    movementTypes: Array,  // [{ value, label }] - For filter dropdown
    stats: Object,         // { total_movements, total_in, total_out }
});

// Format movement types for SearchableSelect
const movementTypeOptions = computed(() => {
    return [
        { value: '', label: 'All Types' },
        ...(props.movementTypes || []).map(t => ({
            value: t.value,
            label: t.label,
        }))
    ];
});

// Filter state
const searchQuery = ref(props.filters?.search || '');
const selectedMovementType = ref(props.filters?.movement_type || null);
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

// Pagination state
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [
    { value: 10, label: '10' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
];
const selectedPerPage = ref(props.pagination?.per_page || 10);

// Fetch data with current filters
const fetchData = () => {
    const params = {
        search: searchQuery.value,
        movement_type: selectedMovementType.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        per_page: selectedPerPage.value,
        page: currentPage.value,
    };

    // Preserve product_id filter if filtering by product
    if (props.filters?.product_id) {
        params.product_id = props.filters.product_id;
    }

    router.get(route('inventory.movements'), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Watch pagination changes
watch([currentPage, selectedPerPage], () => fetchData());

// Perform search (resets to page 1)
const performSearch = () => {
    currentPage.value = 1;
    fetchData();
};

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedMovementType.value = null;
    dateFrom.value = '';
    dateTo.value = '';
    currentPage.value = 1;
    fetchData();
};

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return searchQuery.value || (selectedMovementType.value && selectedMovementType.value !== '') || dateFrom.value || dateTo.value;
});

// Pagination visible pages logic
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

// Formatting utilities
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

// Movement type badge colors - incoming types are green, outgoing are red
const getMovementTypeBadgeClass = (movement) => {
    if (movement.is_incoming) {
        return 'badge-success';
    } else if (movement.is_outgoing) {
        return 'badge-danger';
    }
    return 'badge-light';
};

// Quantity display with +/- prefix and color
const getQuantityClass = (movement) => {
    if (movement.is_incoming) {
        return 'text-green-600 font-medium';
    } else if (movement.is_outgoing) {
        return 'text-red-600 font-medium';
    }
    return 'font-medium';
};

const getQuantityDisplay = (movement) => {
    const qty = formatNumber(Math.abs(movement.quantity));
    if (movement.is_incoming) {
        return `+${qty}`;
    } else if (movement.is_outgoing) {
        return `-${qty}`;
    }
    return qty;
};

// Reference link generation
const getReferenceLink = (movement) => {
    if (movement.reference_type === 'purchase_order' && movement.reference_id) {
        return `/purchase-orders/detail/${movement.reference_id}`;
    }
    if (movement.reference_type === 'sales_order' && movement.reference_id) {
        return `/sales-orders/detail/${movement.reference_id}`;
    }
    return null;
};

const getReferenceLabel = (movement) => {
    if (movement.reference_type === 'purchase_order') {
        return 'PO';
    }
    if (movement.reference_type === 'sales_order') {
        return 'SO';
    }
    return movement.reference_type || '-';
};

// Page title based on filter context
const pageTitle = computed(() => {
    if (props.filterProduct) {
        return `Movements for ${props.filterProduct.name}`;
    }
    return 'Inventory Movements';
});
</script>

<template>
    <AppLayout title="Inventory Movements">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Back to Product button when filtering by product -->
                    <Link v-if="filterProduct" :href="`/products/detail/${filterProduct.id}`"
                        class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ pageTitle }}
                    </h2>
                </div>
                <!-- Back to Inventory List link -->
                <Link :href="route('inventory.list')" class="btn btn-sm btn-light">
                    <i class="ki-filled ki-parcel me-1"></i> Back to Inventory
                </Link>
            </div>
        </template>

        <div class="container-fixed">
            <!-- Stats Summary -->
            <div class="py-5">
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-center justify-between gap-5">
                            <!-- Total Movements -->
                            <div class="flex items-center gap-3 min-w-[160px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100">
                                    <i class="ki-filled ki-arrows-loop text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_movements) }}</div>
                                    <div class="text-xs text-gray-500">Total Movements</div>
                                </div>
                            </div>
                            <!-- Total In (Green) -->
                            <div class="flex items-center gap-3 min-w-[160px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-green-100">
                                    <i class="ki-filled ki-arrow-up text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ formatNumber(stats?.total_in) }}</div>
                                    <div class="text-xs text-gray-500">Total In</div>
                                </div>
                            </div>
                            <!-- Total Out (Red) -->
                            <div class="flex items-center gap-3 min-w-[160px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-red-100">
                                    <i class="ki-filled ki-arrow-down text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-red-600">{{ formatNumber(stats?.total_out) }}</div>
                                    <div class="text-xs text-gray-500">Total Out</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Context Info (when filtering by product) -->
            <div v-if="filterProduct" class="mb-5">
                <div class="card bg-primary-light">
                    <div class="card-body py-3">
                        <div class="flex items-center gap-3">
                            <i class="ki-filled ki-filter text-primary text-lg"></i>
                            <span class="text-gray-700">
                                Showing movements for:
                                <strong class="text-primary">{{ filterProduct.name }}</strong>
                                <span class="text-gray-500 ms-2">(SKU: {{ filterProduct.sku }})</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header">
                        <h3 class="card-title">Movement History</h3>
                        <div class="card-toolbar">
                            <div class="flex gap-3 items-center">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                    <input v-model="searchQuery" class="input input-sm ps-8 w-[180px]"
                                        placeholder="Search..." @keyup.enter="performSearch" />
                                </div>
                                <!-- Movement Type Filter -->
                                <SearchableSelect
                                    v-model="selectedMovementType"
                                    :options="movementTypeOptions"
                                    placeholder="All Types"
                                    size="sm"
                                    class="w-[180px]"
                                    @update:model-value="performSearch"
                                />
                                <!-- Date From -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm text-gray-500">From:</span>
                                    <input type="date" v-model="dateFrom" @change="performSearch"
                                        class="input input-sm w-[140px]" />
                                </div>
                                <!-- Date To -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm text-gray-500">To:</span>
                                    <input type="date" v-model="dateTo" @change="performSearch"
                                        class="input input-sm w-[140px]" />
                                </div>
                                <!-- Clear Filters -->
                                <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-sm btn-light">
                                    <i class="ki-filled ki-cross me-1"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="min-w-[120px]">Date</th>
                                        <!-- Product column only shown if not filtering by product -->
                                        <th v-if="!filterProduct" class="min-w-[200px]">Product</th>
                                        <th class="min-w-[140px]">Movement Type</th>
                                        <th class="w-[100px] text-center">Qty</th>
                                        <th class="w-[120px] text-end">Unit Cost</th>
                                        <th class="w-[140px] text-center">Before / After</th>
                                        <th class="w-[100px]">Reference</th>
                                        <th class="min-w-[150px]">Notes</th>
                                        <th class="w-[100px]">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="movement in movements" :key="movement.id" class="hover:bg-slate-50">
                                        <!-- Date -->
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <i class="ki-filled ki-calendar text-gray-400"></i>
                                                <span>{{ movement.movement_date }}</span>
                                            </div>
                                        </td>
                                        <!-- Product (conditional) -->
                                        <td v-if="!filterProduct">
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-light text-primary font-bold">
                                                    {{ movement.product_name?.charAt(0).toUpperCase() }}
                                                </div>
                                                <div>
                                                    <Link :href="`/inventory/product/${movement.product_id}`"
                                                        class="font-medium text-gray-800 hover:text-primary">
                                                        {{ movement.product_name }}
                                                    </Link>
                                                    <div class="text-xs text-gray-500">SKU: {{ movement.product_sku }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Movement Type -->
                                        <td>
                                            <span :class="['badge badge-sm', getMovementTypeBadgeClass(movement)]">
                                                <i :class="['me-1', movement.is_incoming ? 'ki-filled ki-arrow-up' : 'ki-filled ki-arrow-down']"></i>
                                                {{ movement.movement_type_label }}
                                            </span>
                                        </td>
                                        <!-- Quantity -->
                                        <td class="text-center">
                                            <span :class="getQuantityClass(movement)">
                                                {{ getQuantityDisplay(movement) }}
                                            </span>
                                        </td>
                                        <!-- Unit Cost -->
                                        <td class="text-end">{{ formatCurrency(movement.unit_cost) }}</td>
                                        <!-- Before / After -->
                                        <td class="text-center">
                                            <span class="text-gray-500">{{ formatNumber(movement.quantity_before) }}</span>
                                            <i class="ki-filled ki-arrow-right text-gray-400 mx-1"></i>
                                            <span class="font-medium">{{ formatNumber(movement.quantity_after) }}</span>
                                        </td>
                                        <!-- Reference -->
                                        <td>
                                            <template v-if="getReferenceLink(movement)">
                                                <Link :href="getReferenceLink(movement)"
                                                    class="text-primary hover:underline font-medium">
                                                    {{ getReferenceLabel(movement) }} #{{ movement.reference_id }}
                                                </Link>
                                            </template>
                                            <template v-else>
                                                <span class="text-gray-400">-</span>
                                            </template>
                                        </td>
                                        <!-- Notes -->
                                        <td>
                                            <span class="text-sm text-gray-500">{{ movement.notes || '-' }}</span>
                                        </td>
                                        <!-- Created By -->
                                        <td>
                                            <span class="text-sm text-gray-600">{{ movement.created_by || '-' }}</span>
                                        </td>
                                    </tr>
                                    <!-- Empty State -->
                                    <tr v-if="!movements || movements.length === 0">
                                        <td :colspan="filterProduct ? 8 : 9">
                                            <div class="flex items-center justify-center h-40">
                                                <div class="flex flex-col items-center">
                                                    <i class="ki-filled ki-arrows-loop text-6xl text-gray-300 mb-3"></i>
                                                    <span class="text-gray-500">No movement records found</span>
                                                    <span v-if="hasActiveFilters" class="text-sm text-gray-400 mt-1">
                                                        Try adjusting your filters
                                                    </span>
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
                                <SearchableSelect
                                    v-model="selectedPerPage"
                                    :options="perPageOptions"
                                    size="sm"
                                    class="w-[70px]"
                                />
                                per page
                            </div>
                            <span v-if="pagination">
                                Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} results
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
