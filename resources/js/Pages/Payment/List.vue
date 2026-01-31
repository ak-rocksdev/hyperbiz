<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch, computed } from 'vue';
import Swal from 'sweetalert2';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';

const props = defineProps({
    payments: Array,
    pagination: Object,
    stats: Object,
    filters: Object,
    paymentMethods: Array,
});

const searchQuery = ref(props.filters?.search || '');
const selectedPaymentType = ref(props.filters?.payment_type || '');
const selectedPaymentMethod = ref(props.filters?.payment_method || '');
const dateRange = ref(props.filters?.date_range || '');
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [10, 25, 50, 100];
const selectedPerPage = ref(props.pagination?.per_page || 10);

// Payment type options
const paymentTypeOptions = [
    { value: '', label: 'All Types' },
    { value: 'purchase', label: 'Purchase' },
    { value: 'sales', label: 'Sales' },
];

// Payment method options with "All" option
const paymentMethodOptions = computed(() => [
    { value: '', label: 'All Methods' },
    ...props.paymentMethods.map(m => ({ value: m.value, label: m.label }))
]);

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return searchQuery.value || dateRange.value || selectedPaymentType.value || selectedPaymentMethod.value;
});

// Reset all filters
const resetFilters = () => {
    searchQuery.value = '';
    dateRange.value = '';
    selectedPaymentType.value = '';
    selectedPaymentMethod.value = '';
    currentPage.value = 1;
    fetchData();
};

const fetchData = () => {
    router.get(route('payments.list'), {
        search: searchQuery.value,
        payment_type: selectedPaymentType.value,
        payment_method: selectedPaymentMethod.value,
        date_range: dateRange.value,
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

const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency }).format(value || 0);
};

const paymentTypeColors = {
    purchase: 'bg-orange-100 text-orange-600 border-orange-300',
    sales: 'bg-green-100 text-green-600 border-green-300',
};

const statusColors = {
    confirmed: 'bg-green-100 text-green-600 border-green-300',
    cancelled: 'bg-red-100 text-red-600 border-red-300',
};

const cancelPayment = (id) => {
    Swal.fire({
        title: 'Cancel Payment?',
        text: 'This will cancel the payment and update the order payment status.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/payments/api/cancel/${id}`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    router.reload({ only: ['payments', 'stats'], preserveScroll: true });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.response?.data?.message || 'Something went wrong!',
                    });
                });
        }
    });
};
</script>

<template>
    <AppLayout title="Payments">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Payments
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
                                    <i class="ki-filled ki-wallet text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_payments || 0 }}</div>
                                    <div class="text-xs text-gray-500">Total Payments</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[180px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-orange-100">
                                    <i class="ki-filled ki-entrance-left text-orange-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-orange-600">{{ formatCurrency(stats?.purchase_payments) }}</div>
                                    <div class="text-xs text-gray-500">Purchase Payments</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[180px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-green-100">
                                    <i class="ki-filled ki-exit-left text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.sales_payments) }}</div>
                                    <div class="text-xs text-gray-500">Sales Payments</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header">
                        <h3 class="card-title">Payment Records</h3>
                        <div class="card-toolbar">
                            <div class="flex gap-2.5 items-center flex-wrap">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                    <input v-model="searchQuery" class="input input-sm ps-8 w-[160px]"
                                        placeholder="Search..." @keyup.enter="performSearch" />
                                </div>
                                <!-- Date Range Filter -->
                                <div class="w-[265px]">
                                    <DatePicker
                                        v-model="dateRange"
                                        mode="range"
                                        placeholder="Filter by date"
                                        size="sm"
                                        @change="performSearch"
                                    />
                                </div>
                                <!-- Type Filter -->
                                <div class="w-[120px]">
                                    <SearchableSelect
                                        v-model="selectedPaymentType"
                                        :options="paymentTypeOptions"
                                        placeholder="All Types"
                                        :searchable="false"
                                        size="sm"
                                        @update:modelValue="performSearch"
                                    />
                                </div>
                                <!-- Method Filter -->
                                <div class="w-[140px]">
                                    <SearchableSelect
                                        v-model="selectedPaymentMethod"
                                        :options="paymentMethodOptions"
                                        placeholder="All Methods"
                                        :searchable="false"
                                        size="sm"
                                        @update:modelValue="performSearch"
                                    />
                                </div>
                                <!-- Reset Filters Button -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <button
                                        v-if="hasActiveFilters"
                                        @click="resetFilters"
                                        class="btn btn-sm btn-light btn-clear gap-1.5 text-gray-600 hover:text-primary hover:bg-primary/5 transition-all"
                                        title="Reset all filters"
                                    >
                                        <i class="ki-filled ki-arrows-circle text-sm"></i>
                                        <span class="hidden sm:inline">Reset</span>
                                    </button>
                                </Transition>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="min-w-[150px]">Payment #</th>
                                        <th class="w-[100px] text-center">Type</th>
                                        <th class="min-w-[150px]">Reference</th>
                                        <th class="w-[120px] text-center">Date</th>
                                        <th class="w-[120px]">Method</th>
                                        <th class="min-w-[140px] text-end">Amount</th>
                                        <th class="w-[100px] text-center">Status</th>
                                        <th class="w-[100px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in payments" :key="payment.id" class="hover:bg-slate-50">
                                        <td>
                                            <Link :href="`/payments/${payment.id}`" class="text-primary hover:text-primary-dark font-medium">
                                                {{ payment.payment_number }}
                                            </Link>
                                        </td>
                                        <td class="text-center">
                                            <span :class="`text-xs rounded-lg px-2 py-1 border capitalize ${paymentTypeColors[payment.payment_type]}`">
                                                {{ payment.payment_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-gray-600">{{ payment.reference_type === 'purchase_order' ? 'PO' : 'SO' }}:</span>
                                            {{ payment.reference_number }}
                                        </td>
                                        <td class="text-center">{{ payment.payment_date }}</td>
                                        <td>{{ payment.payment_method_label }}</td>
                                        <td class="text-end font-medium">
                                            {{ formatCurrency(payment.amount, payment.currency_code) }}
                                        </td>
                                        <td class="text-center">
                                            <span :class="`text-xs rounded-lg px-2 py-1 border capitalize ${statusColors[payment.status]}`">
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="menu flex-inline justify-center" data-menu="true">
                                                <div class="menu-item" data-menu-item-offset="0, 10px"
                                                    data-menu-item-placement="bottom-end"
                                                    data-menu-item-toggle="dropdown"
                                                    data-menu-item-trigger="click|lg:click">
                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                        <i class="ki-filled ki-dots-vertical"></i>
                                                    </button>
                                                    <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                        <div class="menu-item">
                                                            <Link :href="`/payments/${payment.id}`" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div v-if="payment.status === 'confirmed'" class="menu-separator"></div>
                                                        <div v-if="payment.status === 'confirmed'" class="menu-item">
                                                            <button @click="cancelPayment(payment.id)" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-cross-circle"></i></span>
                                                                <span class="menu-title text-danger">Cancel</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!payments || payments.length === 0">
                                        <td colspan="8">
                                            <div class="flex items-center justify-center h-40">
                                                <div class="flex flex-col items-center">
                                                    <i class="ki-filled ki-dollar text-6xl text-gray-300 mb-3"></i>
                                                    <span class="text-gray-500">No payments found</span>
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
                                Showing {{ payments?.length || 0 }} of {{ pagination.total }} results
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
