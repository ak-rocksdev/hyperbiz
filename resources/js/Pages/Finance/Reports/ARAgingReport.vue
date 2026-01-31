<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Props from controller
const props = defineProps({
    customers: {
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
            total_customers: 0,
            total_orders: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            as_of_date: null,
            currency: 'IDR',
            customer_id: null
        })
    }
});

// Reactive filter state
const asOfDate = ref(props.filters?.as_of_date || '');

// Sorting state
const sortColumn = ref('customer_name');
const sortDirection = ref('asc');

// Format currency helper
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Apply filters
const applyFilters = () => {
    const params = {
        as_of_date: asOfDate.value || undefined,
    };

    router.get(window.location.pathname, params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Get status badge class based on aging_status
const getStatusBadgeClass = (status) => {
    const statusMap = {
        'current': 'badge-success',
        'attention': 'badge-info',
        'warning': 'badge-warning',
        'critical': 'badge-danger',
        'none': 'badge-light'
    };
    return statusMap[status] || 'badge-light';
};

// Get status label
const getStatusLabel = (status) => {
    const labelMap = {
        'current': 'Current',
        'attention': 'Attention',
        'warning': 'Warning',
        'critical': 'Critical',
        'none': 'No Balance'
    };
    return labelMap[status] || 'Unknown';
};

// Sorting logic
const sortedCustomers = computed(() => {
    if (!props.customers || props.customers.length === 0) return [];

    const sorted = [...props.customers];

    sorted.sort((a, b) => {
        let valueA, valueB;

        switch (sortColumn.value) {
            case 'customer_name':
                valueA = (a.customer_name || '').toLowerCase();
                valueB = (b.customer_name || '').toLowerCase();
                break;
            case 'current_0_30':
                valueA = a.current_0_30 || 0;
                valueB = b.current_0_30 || 0;
                break;
            case 'current_31_60':
                valueA = a.current_31_60 || 0;
                valueB = b.current_31_60 || 0;
                break;
            case 'current_61_90':
                valueA = a.current_61_90 || 0;
                valueB = b.current_61_90 || 0;
                break;
            case 'current_over_90':
                valueA = a.current_over_90 || 0;
                valueB = b.current_over_90 || 0;
                break;
            case 'total_balance':
                valueA = a.total_balance || 0;
                valueB = b.total_balance || 0;
                break;
            default:
                valueA = a.customer_name || '';
                valueB = b.customer_name || '';
        }

        if (typeof valueA === 'string') {
            return sortDirection.value === 'asc'
                ? valueA.localeCompare(valueB)
                : valueB.localeCompare(valueA);
        }

        return sortDirection.value === 'asc'
            ? valueA - valueB
            : valueB - valueA;
    });

    return sorted;
});

// Handle column sort click
const handleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

// Get sort icon class
const getSortIconClass = (column) => {
    if (sortColumn.value !== column) {
        return 'ki-filled ki-arrow-up-down text-gray-300';
    }
    return sortDirection.value === 'asc'
        ? 'ki-filled ki-arrow-up text-primary'
        : 'ki-filled ki-arrow-down text-primary';
};
</script>

<template>
    <AppLayout title="AR Aging Report">
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
                <span class="text-gray-900 font-medium">AR Aging</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">AR Aging Report</h1>
                    <p class="text-sm text-gray-500">Accounts Receivable aging analysis by customer</p>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-5">
                <div class="card-body">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">As of Date</label>
                            <DatePicker
                                v-model="asOfDate"
                                placeholder="Select date"
                                size="sm"
                                class="w-[180px]"
                            />
                        </div>
                        <button
                            @click="applyFilters"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="ki-filled ki-filter me-2"></i>
                            Apply Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <!-- Total Balance -->
                <div class="card lg:col-span-1">
                    <div class="card-body p-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <i class="ki-filled ki-dollar text-primary text-lg"></i>
                                </div>
                            </div>
                            <span class="text-2xl font-bold text-primary">{{ formatCurrency(totals?.total_balance) }}</span>
                            <p class="text-xs text-gray-500 mt-1">Total Balance</p>
                        </div>
                    </div>
                </div>

                <!-- Current (0-30 Days) -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                    <i class="ki-filled ki-check-circle text-success text-lg"></i>
                                </div>
                            </div>
                            <span class="text-xl font-bold text-success">{{ formatCurrency(totals?.current_0_30) }}</span>
                            <p class="text-xs text-gray-500 mt-1">Current (0-30)</p>
                        </div>
                    </div>
                </div>

                <!-- 31-60 Days -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                                    <i class="ki-filled ki-time text-info text-lg"></i>
                                </div>
                            </div>
                            <span class="text-xl font-bold text-info">{{ formatCurrency(totals?.current_31_60) }}</span>
                            <p class="text-xs text-gray-500 mt-1">31-60 Days</p>
                        </div>
                    </div>
                </div>

                <!-- 61-90 Days -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                                    <i class="ki-filled ki-notification-bing text-warning text-lg"></i>
                                </div>
                            </div>
                            <span class="text-xl font-bold text-warning">{{ formatCurrency(totals?.current_61_90) }}</span>
                            <p class="text-xs text-gray-500 mt-1">61-90 Days</p>
                        </div>
                    </div>
                </div>

                <!-- Over 90 Days -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                                    <i class="ki-filled ki-shield-cross text-danger text-lg"></i>
                                </div>
                            </div>
                            <span class="text-xl font-bold text-danger">{{ formatCurrency(totals?.current_over_90) }}</span>
                            <p class="text-xs text-gray-500 mt-1">Over 90 Days</p>
                        </div>
                    </div>
                </div>

                <!-- Total Customers -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="ki-filled ki-people text-gray-600 text-lg"></i>
                                </div>
                            </div>
                            <span class="text-xl font-bold text-gray-900">{{ formatNumber(summary?.total_customers) }}</span>
                            <p class="text-xs text-gray-500 mt-1">Total Customers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer Aging Details</h3>
                    <span class="text-sm text-gray-500">{{ formatNumber(customers?.length || 0) }} customers with outstanding balances</span>
                </div>

                <div class="card-body p-0">
                    <!-- Empty State -->
                    <div v-if="!customers || customers.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-questionnaire-tablet text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Aging Data Found</h4>
                        <p class="text-sm text-gray-500">There are no outstanding receivables for the selected period</p>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th
                                        class="min-w-[200px] cursor-pointer hover:bg-gray-50"
                                        @click="handleSort('customer_name')"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span>Customer</span>
                                            <i :class="getSortIconClass('customer_name')" class="text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="w-[130px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="handleSort('current_0_30')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>Current (0-30)</span>
                                            <i :class="getSortIconClass('current_0_30')" class="text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="w-[120px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="handleSort('current_31_60')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>31-60 Days</span>
                                            <i :class="getSortIconClass('current_31_60')" class="text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="w-[120px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="handleSort('current_61_90')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>61-90 Days</span>
                                            <i :class="getSortIconClass('current_61_90')" class="text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="w-[120px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="handleSort('current_over_90')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>Over 90 Days</span>
                                            <i :class="getSortIconClass('current_over_90')" class="text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="w-[140px] text-end cursor-pointer hover:bg-gray-50"
                                        @click="handleSort('total_balance')"
                                    >
                                        <div class="flex items-center justify-end gap-2">
                                            <span>Total Balance</span>
                                            <i :class="getSortIconClass('total_balance')" class="text-xs"></i>
                                        </div>
                                    </th>
                                    <th class="w-[100px] text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="customer in sortedCustomers" :key="customer.customer_id" class="hover:bg-slate-50">
                                    <!-- Customer Name -->
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-light text-primary font-bold">
                                                {{ customer.customer_name?.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <Link
                                                    :href="`/customers/${customer.customer_id}`"
                                                    class="font-medium text-gray-900 hover:text-primary"
                                                >
                                                    {{ customer.customer_name }}
                                                </Link>
                                                <div class="text-xs text-gray-500">{{ customer.email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Current (0-30) -->
                                    <td class="text-end">
                                        <span class="text-success font-medium">
                                            {{ formatCurrency(customer.current_0_30) }}
                                        </span>
                                    </td>

                                    <!-- 31-60 Days -->
                                    <td class="text-end">
                                        <span class="text-info font-medium">
                                            {{ formatCurrency(customer.current_31_60) }}
                                        </span>
                                    </td>

                                    <!-- 61-90 Days -->
                                    <td class="text-end">
                                        <span class="text-warning font-medium">
                                            {{ formatCurrency(customer.current_61_90) }}
                                        </span>
                                    </td>

                                    <!-- Over 90 Days -->
                                    <td class="text-end">
                                        <span class="text-danger font-medium">
                                            {{ formatCurrency(customer.current_over_90) }}
                                        </span>
                                    </td>

                                    <!-- Total Balance -->
                                    <td class="text-end">
                                        <span class="font-bold text-gray-900">
                                            {{ formatCurrency(customer.total_balance) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="getStatusBadgeClass(customer.aging_status)"
                                        >
                                            {{ getStatusLabel(customer.aging_status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 font-bold">
                                    <td class="text-end">Total:</td>
                                    <td class="text-end text-success">{{ formatCurrency(totals?.current_0_30) }}</td>
                                    <td class="text-end text-info">{{ formatCurrency(totals?.current_31_60) }}</td>
                                    <td class="text-end text-warning">{{ formatCurrency(totals?.current_61_90) }}</td>
                                    <td class="text-end text-danger">{{ formatCurrency(totals?.current_over_90) }}</td>
                                    <td class="text-end text-primary text-lg">{{ formatCurrency(totals?.total_balance) }}</td>
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
