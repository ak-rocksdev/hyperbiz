<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Props from controller
const props = defineProps({
    suppliers: {
        type: Array,
        default: () => []
    },
    totals: {
        type: Object,
        default: () => ({
            current_0_30: 0,
            current_31_60: 0,
            current_61_90: 0,
            current_over_90: 0,
            total_balance: 0
        })
    },
    summary: {
        type: Object,
        default: () => ({
            total_suppliers: 0,
            total_orders: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            as_of_date: null,
            currency: 'IDR',
            supplier_id: null
        })
    }
});

// Reactive filter state
const asOfDate = ref(props.filters?.as_of_date || '');

// Sorting state
const sortColumn = ref('supplier_name');
const sortDirection = ref('asc');

// Format currency helper (IDR)
const formatCurrency = (amount, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Get status badge configuration
const getStatusBadge = (status) => {
    const statusConfig = {
        current: { label: 'Current', class: 'badge-success' },
        attention: { label: 'Attention', class: 'badge-info' },
        warning: { label: 'Warning', class: 'badge-warning' },
        critical: { label: 'Critical', class: 'badge-danger' },
        none: { label: 'No Balance', class: 'badge-outline badge-gray' }
    };
    return statusConfig[status] || statusConfig.none;
};

// Apply filter
const applyFilter = () => {
    const params = {
        as_of_date: asOfDate.value || undefined,
    };

    router.get('/finance/reports/ap-aging', params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Sorted suppliers list
const sortedSuppliers = computed(() => {
    if (!props.suppliers || props.suppliers.length === 0) return [];

    const sorted = [...props.suppliers];
    sorted.sort((a, b) => {
        let aVal, bVal;

        switch (sortColumn.value) {
            case 'supplier_name':
                aVal = (a.supplier_name || '').toLowerCase();
                bVal = (b.supplier_name || '').toLowerCase();
                break;
            case 'total_balance':
                aVal = a.total_balance || 0;
                bVal = b.total_balance || 0;
                break;
            case 'current_0_30':
                aVal = a.current_0_30 || 0;
                bVal = b.current_0_30 || 0;
                break;
            case 'current_31_60':
                aVal = a.current_31_60 || 0;
                bVal = b.current_31_60 || 0;
                break;
            case 'current_61_90':
                aVal = a.current_61_90 || 0;
                bVal = b.current_61_90 || 0;
                break;
            case 'current_over_90':
                aVal = a.current_over_90 || 0;
                bVal = b.current_over_90 || 0;
                break;
            default:
                aVal = a.supplier_name || '';
                bVal = b.supplier_name || '';
        }

        if (sortDirection.value === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });

    return sorted;
});

// Toggle sort
const toggleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

// Get sort icon class
const getSortIcon = (column) => {
    if (sortColumn.value !== column) {
        return 'ki-outline ki-arrow-up-down text-gray-400';
    }
    return sortDirection.value === 'asc'
        ? 'ki-filled ki-arrow-up text-primary'
        : 'ki-filled ki-arrow-down text-primary';
};
</script>

<template>
    <AppLayout title="AP Aging Report">
        <!-- Container -->
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <Link href="/dashboard" class="hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Reports</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">AP Aging</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">AP Aging Report</h1>
                    <p class="text-sm text-gray-500">Accounts Payable aging analysis by supplier</p>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-5">
                <div class="card-body">
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- As of Date Filter -->
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">As of Date</label>
                            <DatePicker
                                v-model="asOfDate"
                                placeholder="Select date"
                                class="w-[180px]"
                            />
                        </div>

                        <!-- Apply Button -->
                        <button
                            @click="applyFilter"
                            class="btn btn-primary"
                        >
                            <i class="ki-filled ki-filter me-2"></i>
                            Apply Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <!-- Total Payables - Primary Large -->
                <div class="card lg:col-span-2">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-bill text-primary text-2xl"></i>
                            </div>
                            <div>
                                <span class="text-2xl font-bold text-primary">{{ formatCurrency(totals?.total_balance) }}</span>
                                <p class="text-sm text-gray-500">Total Payables</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current (0-30 Days) - Success -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-check-circle text-success text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-success">{{ formatCurrency(totals?.current_0_30) }}</span>
                                <p class="text-xs text-gray-500">Current (0-30)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 31-60 Days - Info -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                                <i class="ki-filled ki-timer text-info text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-info">{{ formatCurrency(totals?.current_31_60) }}</span>
                                <p class="text-xs text-gray-500">31-60 Days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 61-90 Days - Warning -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                                <i class="ki-filled ki-information-2 text-warning text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-warning">{{ formatCurrency(totals?.current_61_90) }}</span>
                                <p class="text-xs text-gray-500">61-90 Days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Over 90 Days - Danger -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-shield-cross text-danger text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-danger">{{ formatCurrency(totals?.current_over_90) }}</span>
                                <p class="text-xs text-gray-500">Over 90 Days</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supplier Count Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-people text-gray-600 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(summary?.total_suppliers) }}</span>
                                <p class="text-xs text-gray-500">Total Suppliers</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-cheque text-gray-600 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(summary?.total_orders) }}</span>
                                <p class="text-xs text-gray-500">Outstanding Orders</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Data Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Supplier Aging Details</h3>
                    <span class="text-sm text-gray-500">Click column headers to sort</span>
                </div>

                <div class="card-body">
                    <!-- Empty State -->
                    <div v-if="!sortedSuppliers || sortedSuppliers.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-bill text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Aging Data Found</h4>
                        <p class="text-sm text-gray-500">There are no outstanding payables to display for the selected criteria.</p>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <!-- Supplier Name - Sortable -->
                                    <th
                                        class="min-w-[200px] cursor-pointer hover:bg-gray-50"
                                        @click="toggleSort('supplier_name')"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span>Supplier</span>
                                            <i :class="getSortIcon('supplier_name')"></i>
                                        </div>
                                    </th>

                                    <!-- Current (0-30) - Sortable -->
                                    <th
                                        class="w-[130px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="toggleSort('current_0_30')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>Current (0-30)</span>
                                            <i :class="getSortIcon('current_0_30')"></i>
                                        </div>
                                    </th>

                                    <!-- 31-60 Days - Sortable -->
                                    <th
                                        class="w-[130px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="toggleSort('current_31_60')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>31-60 Days</span>
                                            <i :class="getSortIcon('current_31_60')"></i>
                                        </div>
                                    </th>

                                    <!-- 61-90 Days - Sortable -->
                                    <th
                                        class="w-[130px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="toggleSort('current_61_90')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>61-90 Days</span>
                                            <i :class="getSortIcon('current_61_90')"></i>
                                        </div>
                                    </th>

                                    <!-- Over 90 Days - Sortable -->
                                    <th
                                        class="w-[130px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="toggleSort('current_over_90')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>Over 90 Days</span>
                                            <i :class="getSortIcon('current_over_90')"></i>
                                        </div>
                                    </th>

                                    <!-- Total Payable - Sortable -->
                                    <th
                                        class="w-[140px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="toggleSort('total_balance')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>Total Payable</span>
                                            <i :class="getSortIcon('total_balance')"></i>
                                        </div>
                                    </th>

                                    <!-- Status -->
                                    <th class="w-[100px] text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="supplier in sortedSuppliers" :key="supplier.supplier_id" class="hover:bg-slate-50">
                                    <!-- Supplier Name with Email -->
                                    <td>
                                        <div class="flex flex-col">
                                            <Link
                                                :href="`/customers/${supplier.supplier_id}`"
                                                class="text-primary hover:underline font-medium"
                                            >
                                                {{ supplier.supplier_name }}
                                            </Link>
                                            <span v-if="supplier.email" class="text-xs text-gray-400">
                                                {{ supplier.email }}
                                            </span>
                                            <span v-if="supplier.order_count" class="text-xs text-gray-400">
                                                {{ supplier.order_count }} order(s)
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Current (0-30) - Green -->
                                    <td class="text-end">
                                        <span class="text-success font-medium">
                                            {{ formatCurrency(supplier.current_0_30) }}
                                        </span>
                                    </td>

                                    <!-- 31-60 Days - Blue -->
                                    <td class="text-end">
                                        <span class="text-info font-medium">
                                            {{ formatCurrency(supplier.current_31_60) }}
                                        </span>
                                    </td>

                                    <!-- 61-90 Days - Orange -->
                                    <td class="text-end">
                                        <span class="text-warning font-medium">
                                            {{ formatCurrency(supplier.current_61_90) }}
                                        </span>
                                    </td>

                                    <!-- Over 90 Days - Red -->
                                    <td class="text-end">
                                        <span class="text-danger font-medium">
                                            {{ formatCurrency(supplier.current_over_90) }}
                                        </span>
                                    </td>

                                    <!-- Total Payable - Bold -->
                                    <td class="text-end">
                                        <span class="font-bold text-gray-900">
                                            {{ formatCurrency(supplier.total_balance) }}
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="getStatusBadge(supplier.aging_status).class"
                                        >
                                            {{ getStatusBadge(supplier.aging_status).label }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Table Footer with Totals -->
                            <tfoot>
                                <tr class="bg-gray-50 font-semibold">
                                    <td class="text-gray-700">Total</td>
                                    <td class="text-end text-success">{{ formatCurrency(totals?.current_0_30) }}</td>
                                    <td class="text-end text-info">{{ formatCurrency(totals?.current_31_60) }}</td>
                                    <td class="text-end text-warning">{{ formatCurrency(totals?.current_61_90) }}</td>
                                    <td class="text-end text-danger">{{ formatCurrency(totals?.current_over_90) }}</td>
                                    <td class="text-end text-gray-900">{{ formatCurrency(totals?.total_balance) }}</td>
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
