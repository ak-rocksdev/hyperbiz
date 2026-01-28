<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    salesOrders: Array,
    pagination: Object,
    stats: Object,
    filters: Object,
    customers: Array,
    statuses: Array,
});

// Format statuses for SearchableSelect
const statusOptions = computed(() => {
    return [
        { value: '', label: 'All Status' },
        ...(props.statuses || []).map(s => ({
            value: s.value,
            label: s.label,
        }))
    ];
});

const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || null);
const selectedCustomer = ref(props.filters?.customer_id || '');
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [10, 25, 50, 100];
const selectedPerPage = ref(props.pagination?.per_page || 10);

const fetchData = () => {
    router.get(route('sales-orders.list'), {
        search: searchQuery.value,
        status: selectedStatus.value,
        customer_id: selectedCustomer.value,
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

const statusColors = {
    draft: 'bg-gray-100 text-gray-600 border-gray-300',
    confirmed: 'bg-blue-100 text-blue-600 border-blue-300',
    processing: 'bg-yellow-100 text-yellow-600 border-yellow-300',
    shipped: 'bg-purple-100 text-purple-600 border-purple-300',
    delivered: 'bg-green-100 text-green-600 border-green-300',
    cancelled: 'bg-red-100 text-red-600 border-red-300',
};

const paymentStatusColors = {
    unpaid: 'bg-red-100 text-red-600 border-red-300',
    partial: 'bg-yellow-100 text-yellow-600 border-yellow-300',
    paid: 'bg-green-100 text-green-600 border-green-300',
};

// Helper to check status (case-insensitive)
const isStatus = (status, check) => {
    return (status || '').toLowerCase().trim() === check.toLowerCase();
};

const isDraft = (status) => isStatus(status, 'draft');
const isDelivered = (status) => isStatus(status, 'delivered');
const isCancelled = (status) => isStatus(status, 'cancelled');
const canCancel = (status) => !isDelivered(status) && !isCancelled(status);

const confirmSO = (id) => {
    Swal.fire({
        title: 'Confirm Sales Order?',
        text: 'This will confirm the order and reserve stock.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, confirm it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/sales-orders/api/confirm/${id}`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Confirmed!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    router.reload({ only: ['salesOrders', 'stats'], preserveScroll: true });
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

const cancelSO = (id) => {
    Swal.fire({
        title: 'Cancel Sales Order?',
        text: 'This action will release reserved stock.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/sales-orders/api/cancel/${id}`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    router.reload({ only: ['salesOrders', 'stats'], preserveScroll: true });
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

const deleteSO = (id) => {
    Swal.fire({
        title: 'Delete Sales Order?',
        text: 'This will permanently delete this draft order.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/sales-orders/api/delete/${id}`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    router.reload({ only: ['salesOrders', 'stats'], preserveScroll: true });
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
    <AppLayout title="Sales Orders">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sales Orders
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
                                    <i class="ki-filled ki-document text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.total_orders || 0 }}</div>
                                    <div class="text-xs text-gray-500">Total SO</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-orange-100">
                                    <i class="ki-filled ki-delivery text-orange-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ stats?.pending_orders || 0 }}</div>
                                    <div class="text-xs text-gray-500">Pending Delivery</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[180px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-green-100">
                                    <i class="ki-filled ki-dollar text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.total_value) }}</div>
                                    <div class="text-xs text-gray-500">Total SO Value</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 min-w-[180px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-red-100">
                                    <i class="ki-filled ki-wallet text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-red-600">{{ formatCurrency(stats?.unpaid_amount) }}</div>
                                    <div class="text-xs text-gray-500">Unpaid Amount</div>
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
                        <h3 class="card-title">Sales Orders</h3>
                        <div class="card-toolbar">
                            <div class="flex gap-3 items-center">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                    <input v-model="searchQuery" class="input input-sm ps-8 w-[200px]"
                                        placeholder="Search SO..." @keyup.enter="performSearch" />
                                </div>
                                <!-- Status Filter -->
                                <SearchableSelect
                                    v-model="selectedStatus"
                                    :options="statusOptions"
                                    placeholder="All Status"
                                    size="sm"
                                    class="w-[140px]"
                                    @update:model-value="performSearch"
                                />
                                <!-- Create Button -->
                                <Link :href="route('sales-orders.create')" class="btn btn-sm btn-primary whitespace-nowrap">
                                    <i class="ki-filled ki-plus-squared me-1"></i>
                                    New SO
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="min-w-[180px]">SO Number</th>
                                        <th class="min-w-[200px]">Customer</th>
                                        <th class="w-[120px] text-center">Date</th>
                                        <th class="w-[100px] text-center">Status</th>
                                        <th class="w-[100px] text-center">Payment</th>
                                        <th class="min-w-[150px] text-end">Grand Total</th>
                                        <th class="w-[100px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="so in salesOrders" :key="so.id" class="hover:bg-slate-50">
                                        <td>
                                            <Link :href="`/sales-orders/${so.id}`" class="text-primary hover:text-primary-dark font-medium">
                                                {{ so.so_number }}
                                            </Link>
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-800">{{ so.customer_name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ so.order_date }}</td>
                                        <td class="text-center">
                                            <span :class="`text-xs rounded-lg px-2 py-1 border ${statusColors[so.status]}`">
                                                {{ so.status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span :class="`text-xs rounded-lg px-2 py-1 border ${paymentStatusColors[so.payment_status]}`">
                                                {{ so.payment_status_label }}
                                            </span>
                                        </td>
                                        <td class="text-end font-medium">
                                            {{ formatCurrency(so.grand_total, so.currency_code) }}
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
                                                            <Link :href="`/sales-orders/${so.id}`" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div v-if="isDraft(so.status)" class="menu-item">
                                                            <Link :href="`/sales-orders/edit/${so.id}`" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Edit</span>
                                                            </Link>
                                                        </div>
                                                        <div v-if="isDraft(so.status)" class="menu-item">
                                                            <button @click="confirmSO(so.id)" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-check-circle"></i></span>
                                                                <span class="menu-title">Confirm</span>
                                                            </button>
                                                        </div>
                                                        <div v-if="isDraft(so.status) || canCancel(so.status)" class="menu-separator"></div>
                                                        <div v-if="isDraft(so.status)" class="menu-item">
                                                            <button @click="deleteSO(so.id)" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                <span class="menu-title text-danger">Delete</span>
                                                            </button>
                                                        </div>
                                                        <div v-if="canCancel(so.status)" class="menu-item">
                                                            <button @click="cancelSO(so.id)" class="menu-link">
                                                                <span class="menu-icon"><i class="ki-filled ki-cross-circle"></i></span>
                                                                <span class="menu-title text-danger">Cancel</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!salesOrders || salesOrders.length === 0">
                                        <td colspan="7">
                                            <div class="flex items-center justify-center h-40">
                                                <div class="flex flex-col items-center">
                                                    <i class="ki-filled ki-handcart text-6xl text-gray-300 mb-3"></i>
                                                    <span class="text-gray-500">No sales orders found</span>
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
                                Showing {{ salesOrders?.length || 0 }} of {{ pagination.total }} results
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
