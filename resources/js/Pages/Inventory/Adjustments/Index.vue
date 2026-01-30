<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    adjustments: Array,
    pagination: Object,
    filters: Object,
    reasons: Array,
    stats: Object,
});

// Filters
const search = ref(props.filters?.search || '');
const adjustmentType = ref(props.filters?.adjustment_type || '');
const reasonCode = ref(props.filters?.reason_code || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const perPage = ref(props.pagination?.per_page || 20);

// Format helpers
const formatNumber = (value) => {
    if (value == null) return '0';
    const num = Number(value);
    return num % 1 === 0 ? num.toLocaleString('id-ID') : num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

// Reason lookup
const getReasonInfo = (code) => {
    const reason = props.reasons?.find(r => r.code === code);
    return reason || { name: code || '-', icon: 'ki-filled ki-information' };
};

// Apply filters
const applyFilters = () => {
    router.get(route('inventory.adjustments'), {
        search: search.value || undefined,
        adjustment_type: adjustmentType.value || undefined,
        reason_code: reasonCode.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Debounced search
const debouncedSearch = debounce(() => {
    applyFilters();
}, 400);

watch(search, debouncedSearch);
watch([adjustmentType, reasonCode, dateFrom, dateTo, perPage], applyFilters);

// Clear filters
const clearFilters = () => {
    search.value = '';
    adjustmentType.value = '';
    reasonCode.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

// Pagination
const goToPage = (page) => {
    router.get(route('inventory.adjustments'), {
        ...props.filters,
        page,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Computed: filter active
const hasActiveFilters = computed(() => {
    return search.value || adjustmentType.value || reasonCode.value || dateFrom.value || dateTo.value;
});

// Computed: filtered reasons by type
const filteredReasons = computed(() => {
    if (!adjustmentType.value) return props.reasons;
    return props.reasons?.filter(r => r.type === 'both' || r.type === adjustmentType.value);
});

// Options for SearchableSelect components
const typeOptions = computed(() => [
    { value: 'add', label: 'Stock In (+)' },
    { value: 'deduct', label: 'Stock Out (-)' }
]);

const reasonOptions = computed(() => {
    return filteredReasons.value?.map(r => ({
        value: r.code,
        label: r.name
    })) || [];
});
</script>

<template>
    <AppLayout title="Stock Adjustments">
        <div class="container-fixed py-5">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Stock Adjustments</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage stock adjustments and corrections</p>
                </div>
                <Link :href="route('inventory.adjustments.create')" class="btn btn-primary">
                    <i class="ki-filled ki-plus me-2"></i>
                    New Adjustment
                </Link>
            </div>

            <!-- Stats Cards - 4 Column Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-4 mb-5">
                <!-- Total Adjustments -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats?.total_adjustments) }}</p>
                                <span class="text-xs text-gray-500">Total Adjustments</span>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-chart text-primary text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stock In -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-success">{{ formatNumber(stats?.total_additions) }}</p>
                                <span class="text-xs text-gray-500">Stock In</span>
                                <p class="text-xs text-success mt-1">+{{ formatNumber(stats?.total_quantity_added) }} units</p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-arrow-up text-success text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stock Out -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-danger">{{ formatNumber(stats?.total_deductions) }}</p>
                                <span class="text-xs text-gray-500">Stock Out</span>
                                <p class="text-xs text-danger mt-1">-{{ formatNumber(stats?.total_quantity_deducted) }} units</p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-arrow-down text-danger text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Net Change -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold" :class="(stats?.total_quantity_added || 0) - (stats?.total_quantity_deducted || 0) >= 0 ? 'text-info' : 'text-warning'">
                                    {{ (stats?.total_quantity_added || 0) - (stats?.total_quantity_deducted || 0) >= 0 ? '+' : '' }}{{ formatNumber((stats?.total_quantity_added || 0) - (stats?.total_quantity_deducted || 0)) }}
                                </p>
                                <span class="text-xs text-gray-500">Net Qty Change</span>
                                <p class="text-xs text-gray-400 mt-1">Overall impact</p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-info/10 flex items-center justify-center">
                                <i class="ki-filled ki-graph-up text-info text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-5">
                <div class="card-header py-3">
                    <h3 class="card-title text-sm font-medium">
                        <i class="ki-filled ki-filter text-gray-500 me-2"></i>
                        Filters
                    </h3>
                    <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-xs btn-light">
                        <i class="ki-filled ki-cross me-1"></i>
                        Clear All
                    </button>
                </div>
                <div class="card-body py-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2 xl:col-span-1">
                            <div class="relative">
                                <i class="ki-filled ki-magnifier absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input
                                    v-model="search"
                                    type="text"
                                    class="input input-sm ps-9 w-full"
                                    placeholder="Search product or SKU..."
                                />
                            </div>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <SearchableSelect
                                v-model="adjustmentType"
                                :options="typeOptions"
                                placeholder="All Types"
                                search-placeholder="Search type..."
                                size="sm"
                                clearable
                            />
                        </div>

                        <!-- Reason Filter -->
                        <div>
                            <SearchableSelect
                                v-model="reasonCode"
                                :options="reasonOptions"
                                placeholder="All Reasons"
                                search-placeholder="Search reason..."
                                size="sm"
                                clearable
                            />
                        </div>

                        <!-- Date From -->
                        <div>
                            <DatePicker
                                v-model="dateFrom"
                                placeholder="From Date"
                                size="sm"
                                clearable
                            />
                        </div>

                        <!-- Date To -->
                        <div>
                            <DatePicker
                                v-model="dateTo"
                                placeholder="To Date"
                                size="sm"
                                clearable
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Adjustment History</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">Show</span>
                        <select v-model="perPage" class="select select-sm w-20">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="scrollable-x-auto">
                        <table class="table table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[140px]">Date</th>
                                    <th class="min-w-[200px]">Product</th>
                                    <th class="w-[100px] text-center">Type</th>
                                    <th class="w-[100px] text-center">Qty</th>
                                    <th class="w-[120px] text-end">Unit Cost</th>
                                    <th class="min-w-[150px]">Reason</th>
                                    <th class="w-[100px] text-center">Before</th>
                                    <th class="w-[100px] text-center">After</th>
                                    <th class="min-w-[120px]">By</th>
                                    <th class="min-w-[200px]">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="adjustment in adjustments" :key="adjustment.id">
                                    <td class="text-sm">{{ adjustment.movement_date }}</td>
                                    <td>
                                        <Link :href="`/inventory/product/${adjustment.product_id}`" class="text-primary hover:underline font-medium">
                                            {{ adjustment.product_name }}
                                        </Link>
                                        <p class="text-xs text-gray-500">{{ adjustment.product_sku }}</p>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="adjustment.adjustment_type === 'add'" class="badge badge-sm badge-success">
                                            <i class="ki-filled ki-arrow-up me-1"></i> IN
                                        </span>
                                        <span v-else class="badge badge-sm badge-danger">
                                            <i class="ki-filled ki-arrow-down me-1"></i> OUT
                                        </span>
                                    </td>
                                    <td class="text-center font-semibold">
                                        <span :class="adjustment.adjustment_type === 'add' ? 'text-success' : 'text-danger'">
                                            {{ adjustment.adjustment_type === 'add' ? '+' : '-' }}{{ formatNumber(adjustment.quantity) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-sm">{{ formatCurrency(adjustment.unit_cost) }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i :class="[getReasonInfo(adjustment.reason_code).icon, 'text-gray-500']"></i>
                                            <span class="text-sm">{{ adjustment.reason_name || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center text-sm text-gray-500">{{ formatNumber(adjustment.quantity_before) }}</td>
                                    <td class="text-center text-sm font-medium">{{ formatNumber(adjustment.quantity_after) }}</td>
                                    <td class="text-sm">{{ adjustment.created_by }}</td>
                                    <td class="text-sm text-gray-500 max-w-[200px] truncate" :title="adjustment.notes">
                                        {{ adjustment.notes || '-' }}
                                    </td>
                                </tr>
                                <tr v-if="!adjustments || adjustments.length === 0">
                                    <td colspan="10" class="text-center py-10">
                                        <div class="flex flex-col items-center">
                                            <i class="ki-filled ki-notepad-edit text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-gray-500">No adjustments found</p>
                                            <Link :href="route('inventory.adjustments.create')" class="btn btn-sm btn-light mt-3">
                                                <i class="ki-filled ki-plus me-1"></i>
                                                Create First Adjustment
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination && pagination.total > 0" class="card-footer flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} entries
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="btn btn-sm btn-icon btn-light"
                        >
                            <i class="ki-filled ki-arrow-left"></i>
                        </button>
                        <template v-for="page in pagination.last_page" :key="page">
                            <button
                                v-if="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
                                @click="goToPage(page)"
                                :class="['btn btn-sm', page === pagination.current_page ? 'btn-primary' : 'btn-light']"
                            >
                                {{ page }}
                            </button>
                            <span v-else-if="page === 2 || page === pagination.last_page - 1" class="px-2 text-gray-400">...</span>
                        </template>
                        <button
                            @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="btn btn-sm btn-icon btn-light"
                        >
                            <i class="ki-filled ki-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
