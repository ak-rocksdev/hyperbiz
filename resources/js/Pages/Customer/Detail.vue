<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    customer: Object,
    stats: Object,
    recentOrders: Array,
    recentPayments: Array,
    salesReturns: Array,
});

// Computed customer data
const customer = computed(() => props.customer);

// Format helpers
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Get customer initials for avatar
const customerInitials = computed(() => {
    const name = customer.value?.name || '';
    if (name.split(' ').length > 1) {
        return name.split(' ').map(word => word[0]?.toUpperCase()).slice(0, 2).join('');
    }
    return name[0]?.toUpperCase() || 'C';
});

// Get status badge class for orders
const getStatusBadgeClass = (status) => {
    const classes = {
        'draft': 'badge-secondary',
        'confirmed': 'badge-primary',
        'processing': 'badge-info',
        'partial': 'badge-warning',
        'shipped': 'badge-info',
        'delivered': 'badge-success',
        'cancelled': 'badge-danger',
    };
    return classes[status] || 'badge-secondary';
};

// Get payment status badge class
const getPaymentStatusBadgeClass = (status) => {
    const classes = {
        'unpaid': 'badge-danger',
        'partial': 'badge-warning',
        'paid': 'badge-success',
    };
    return classes[status] || 'badge-secondary';
};

// Toggle customer status
const toggleStatus = () => {
    const action = customer.value.is_active ? 'deactivate' : 'activate';
    const title = customer.value.is_active ? 'Deactivate Customer?' : 'Activate Customer?';

    Swal.fire({
        title: title,
        text: customer.value.is_active
            ? `Are you sure you want to deactivate "${customer.value.name}"?`
            : `Are you sure you want to activate "${customer.value.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: customer.value.is_active ? '#F59E0B' : '#22C55E',
        cancelButtonColor: '#6B7280',
        confirmButtonText: customer.value.is_active ? 'Yes, deactivate' : 'Yes, activate'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`/customer/api/toggle-status/${customer.value.id}`)
                .then(response => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message
                    });
                    // Reload page to reflect changes
                    window.location.reload();
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data?.message || 'Failed to update customer status.'
                    });
                });
        }
    });
};
</script>

<template>
    <AppLayout title="Customer Detail">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/customer/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ customer?.name }}
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
                            <Link href="/customer/list" class="btn btn-icon btn-light btn-sm">
                                <i class="ki-filled ki-arrow-left"></i>
                            </Link>
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-primary-light text-primary font-bold text-xl shrink-0">
                                {{ customerInitials }}
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ customer?.name }}</h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="badge badge-sm badge-outline badge-secondary">
                                        {{ customer?.customer_type }}
                                    </span>
                                    <span class="badge badge-sm" :class="customer?.is_active ? 'badge-success' : 'badge-secondary'">
                                        {{ customer?.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Link :href="`/customer/edit/${customer?.id}`" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-pencil me-1"></i> Edit Customer
                            </Link>
                            <Link :href="`/sales-orders/create?customer_id=${customer?.id}`" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-plus-squared me-1"></i> Create Order
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Details (2/3 width) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Contact Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-profile-circle text-gray-500 me-2"></i>
                                Contact Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Email</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ customer?.email || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Phone Number</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ customer?.phone_number || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Customer Type</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ customer?.customer_type || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Contact Person</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ customer?.contact_person || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Contact Person Phone</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ customer?.contact_person_phone_number || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Address</span>
                                    <p v-if="customer?.address" class="font-medium text-gray-900 dark:text-white">
                                        {{ customer.address.address }}<br>
                                        {{ customer.address.city_name }}, {{ customer.address.state_name }}<br>
                                        {{ customer.address.country_name }}
                                    </p>
                                    <p v-else class="text-gray-400">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-document text-gray-500 me-2"></i>
                                Recent Orders
                            </h3>
                            <Link
                                v-if="stats?.orders_count > 5"
                                :href="`/sales-orders/list?customer_id=${customer?.id}`"
                                class="btn btn-sm btn-light">
                                View All Orders
                            </Link>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="recentOrders && recentOrders.length > 0" class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">SO Number</th>
                                            <th class="w-[100px]">Date</th>
                                            <th class="w-[130px] text-end">Amount</th>
                                            <th class="w-[100px] text-center">Status</th>
                                            <th class="w-[100px] text-center">Payment</th>
                                            <th class="w-[60px] text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                            <td>
                                                <Link :href="`/sales-orders/${order.id}`" class="text-primary hover:underline font-medium">
                                                    {{ order.so_number }}
                                                </Link>
                                            </td>
                                            <td class="text-gray-600">{{ order.order_date }}</td>
                                            <td class="text-end font-medium text-gray-900">{{ formatCurrency(order.grand_total) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-sm" :class="getStatusBadgeClass(order.status)">
                                                    {{ order.status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-sm" :class="getPaymentStatusBadgeClass(order.payment_status)">
                                                    {{ order.payment_status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <Link :href="`/sales-orders/${order.id}`" class="btn btn-sm btn-icon btn-light">
                                                    <i class="ki-filled ki-eye"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-10">
                                <i class="ki-filled ki-document text-5xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No orders found</p>
                                <Link :href="`/sales-orders/create?customer_id=${customer?.id}`" class="btn btn-sm btn-light mt-4">
                                    <i class="ki-filled ki-plus-squared me-1"></i>
                                    Create First Order
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments Card -->
                    <div v-if="recentPayments && recentPayments.length > 0" class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-dollar text-gray-500 me-2"></i>
                                Recent Payments
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">Payment #</th>
                                            <th class="w-[100px]">Date</th>
                                            <th class="w-[120px] text-end">Amount</th>
                                            <th class="w-[120px]">Method</th>
                                            <th class="w-[100px] text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="payment in recentPayments" :key="payment.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                            <td>
                                                <Link :href="`/payments/${payment.id}`" class="text-primary hover:underline font-medium">
                                                    {{ payment.payment_number }}
                                                </Link>
                                            </td>
                                            <td class="text-gray-600">{{ payment.payment_date }}</td>
                                            <td class="text-end font-medium text-gray-900">{{ formatCurrency(payment.amount) }}</td>
                                            <td>
                                                <span class="badge badge-sm badge-outline badge-secondary">
                                                    {{ payment.payment_method }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-sm" :class="payment.status === 'confirmed' ? 'badge-success' : 'badge-warning'">
                                                    {{ payment.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Returns Card -->
                    <div v-if="salesReturns && salesReturns.length > 0" class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-arrow-left text-gray-500 me-2"></i>
                                Returns
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">Return #</th>
                                            <th class="w-[100px]">Date</th>
                                            <th class="w-[120px] text-end">Amount</th>
                                            <th class="w-[100px] text-center">Status</th>
                                            <th class="min-w-[150px]">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="ret in salesReturns" :key="ret.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                            <td class="font-medium text-gray-900">{{ ret.return_number }}</td>
                                            <td class="text-gray-600">{{ ret.return_date }}</td>
                                            <td class="text-end font-medium text-danger">{{ formatCurrency(ret.subtotal) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-sm" :class="ret.status === 'completed' ? 'badge-success' : 'badge-warning'">
                                                    {{ ret.status }}
                                                </span>
                                            </td>
                                            <td class="text-gray-600 text-sm">{{ ret.reason || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
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
                        <div class="card-body space-y-4">
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-light">
                                    <i class="ki-filled ki-document text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ formatNumber(stats?.orders_count) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Total Orders</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-success-light">
                                    <i class="ki-filled ki-dollar text-success text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(stats?.total_sales) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Total Revenue</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-warning-light">
                                    <i class="ki-filled ki-time text-warning text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-lg font-bold" :class="stats?.outstanding > 0 ? 'text-warning' : 'text-gray-900 dark:text-white'">
                                        {{ formatCurrency(stats?.outstanding) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Outstanding</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-info-light">
                                    <i class="ki-filled ki-calculator text-info text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(stats?.avg_order_value) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Avg Order Value</div>
                                </div>
                            </div>
                            <div v-if="stats?.last_order_date" class="p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="ki-filled ki-calendar text-blue-600"></i>
                                    <span class="text-sm text-blue-800">
                                        Last Order: <strong>{{ stats.last_order_date }}</strong>
                                        <span v-if="stats.days_since_last_order !== null" class="text-blue-600">
                                            ({{ stats.days_since_last_order }} days ago)
                                        </span>
                                    </span>
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
                            <Link :href="`/customer/edit/${customer?.id}`" class="btn btn-primary w-full">
                                <i class="ki-filled ki-pencil me-2"></i>
                                Edit Customer
                            </Link>
                            <Link :href="`/sales-orders/create?customer_id=${customer?.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-plus-squared me-2"></i>
                                Create Sales Order
                            </Link>
                            <Link :href="`/sales-orders/list?customer_id=${customer?.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-document me-2"></i>
                                View All Orders
                            </Link>
                            <div class="border-t border-gray-200 pt-3">
                                <button
                                    @click="toggleStatus"
                                    class="btn w-full"
                                    :class="customer?.is_active ? 'btn-warning' : 'btn-success'"
                                >
                                    <i :class="customer?.is_active ? 'ki-filled ki-lock' : 'ki-filled ki-check-circle'" class="me-2"></i>
                                    {{ customer?.is_active ? 'Deactivate Customer' : 'Activate Customer' }}
                                </button>
                            </div>
                            <Link href="/customer/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to Customer List
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
                                    <span class="text-gray-900 dark:text-white">{{ customer?.created_at || '-' }}</span>
                                </div>
                                <div v-if="customer?.created_by" class="flex justify-between">
                                    <span class="text-gray-500">Created By</span>
                                    <span class="text-gray-900 dark:text-white">{{ customer.created_by }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Updated At</span>
                                    <span class="text-gray-900 dark:text-white">{{ customer?.updated_at || '-' }}</span>
                                </div>
                                <div v-if="customer?.updated_by" class="flex justify-between">
                                    <span class="text-gray-500">Updated By</span>
                                    <span class="text-gray-900 dark:text-white">{{ customer.updated_by }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
