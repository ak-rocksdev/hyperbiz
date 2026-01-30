<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    salesOrder: Object,
    profitData: Object, // Permission-gated profit data
});

const so = computed(() => props.salesOrder);
const showPaymentModal = ref(false);
const isLoading = ref(false);
const showItemProfit = ref(false);

// Payment form
const paymentForm = ref({
    payment_date: new Date().toISOString().split('T')[0],
    amount: 0,
    payment_method: 'bank_transfer',
    bank_name: '',
    account_number: '',
    reference_number: '',
    notes: '',
});

const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency }).format(value || 0);
};

// Format quantity - show whole numbers if no decimals, otherwise show up to 2 decimals
const formatQty = (value) => {
    if (value == null) return '0';
    const num = Number(value);
    return num % 1 === 0 ? num.toLocaleString('id-ID') : num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

const statusColors = {
    draft: 'badge-warning',
    confirmed: 'badge-info',
    processing: 'badge-primary',
    shipped: 'badge-purple',
    delivered: 'badge-success',
    cancelled: 'badge-danger',
};

// Status helpers (case-insensitive)
const currentStatus = computed(() => (so.value?.status || '').toLowerCase().trim());
const isDraft = computed(() => currentStatus.value === 'draft');
const isConfirmed = computed(() => currentStatus.value === 'confirmed');
const isProcessing = computed(() => currentStatus.value === 'processing');
const isShipped = computed(() => currentStatus.value === 'shipped');
const isDelivered = computed(() => currentStatus.value === 'delivered');
const isCancelled = computed(() => currentStatus.value === 'cancelled');
const canShip = computed(() => ['confirmed', 'processing'].includes(currentStatus.value));
const canCancel = computed(() => !['cancelled', 'delivered'].includes(currentStatus.value));

const paymentStatusColors = {
    unpaid: 'badge-danger',
    partial: 'badge-warning',
    paid: 'badge-success',
};

const paymentMethods = [
    { value: 'cash', label: 'Cash', icon: 'ki-filled ki-wallet' },
    { value: 'bank_transfer', label: 'Bank Transfer', icon: 'ki-filled ki-bank' },
    { value: 'credit_card', label: 'Credit Card', icon: 'ki-filled ki-credit-cart' },
    { value: 'cheque', label: 'Cheque', icon: 'ki-filled ki-document' },
    { value: 'giro', label: 'Giro', icon: 'ki-filled ki-cheque' },
    { value: 'e_wallet', label: 'E-Wallet', icon: 'ki-filled ki-phone' },
];

// Payment method needs bank details
const needsBankDetails = computed(() => {
    return ['bank_transfer', 'cheque', 'giro'].includes(paymentForm.value.payment_method);
});

// Calculate amount due
const amountDue = computed(() => {
    return (so.value?.grand_total || 0) - (so.value?.amount_paid || 0);
});

// Actions
const confirmSO = () => {
    Swal.fire({
        title: 'Confirm Sales Order?',
        text: 'This will confirm the order and reserve stock.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, confirm it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/sales-orders/api/confirm/${so.value.id}`)
                .then(() => {
                    Swal.fire('Confirmed!', 'Sales order has been confirmed.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to confirm SO.', 'error');
                });
        }
    });
};

const cancelSO = () => {
    Swal.fire({
        title: 'Cancel Sales Order?',
        text: 'This action will release reserved stock.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, cancel it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/sales-orders/api/cancel/${so.value.id}`)
                .then(() => {
                    Swal.fire('Cancelled!', 'Sales order has been cancelled.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to cancel SO.', 'error');
                });
        }
    });
};

const markAsShipped = () => {
    Swal.fire({
        title: 'Mark as Shipped?',
        text: 'This will mark the order as shipped and deduct from inventory.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark shipped!',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.post(`/sales-orders/api/mark-shipped/${so.value.id}`)
                .then(() => {
                    Swal.fire('Shipped!', 'Order has been marked as shipped.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to mark as shipped.', 'error');
                })
                .finally(() => {
                    isLoading.value = false;
                });
        }
    });
};

const markAsDelivered = () => {
    Swal.fire({
        title: 'Mark as Delivered?',
        text: 'This will mark the order as delivered.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark delivered!',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.post(`/sales-orders/api/mark-delivered/${so.value.id}`)
                .then(() => {
                    Swal.fire('Delivered!', 'Order has been marked as delivered.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to mark as delivered.', 'error');
                })
                .finally(() => {
                    isLoading.value = false;
                });
        }
    });
};

// Payment Modal
const openPaymentModal = () => {
    // Reset form to defaults
    paymentForm.value = {
        payment_date: new Date().toISOString().split('T')[0],
        amount: amountDue.value,
        payment_method: 'bank_transfer',
        bank_name: '',
        account_number: '',
        reference_number: '',
        notes: '',
    };
    showPaymentModal.value = true;
};

// Quick payment amount setters
const setFullPayment = () => {
    paymentForm.value.amount = amountDue.value;
};

const setHalfPayment = () => {
    paymentForm.value.amount = Math.round(amountDue.value / 2 * 100) / 100;
};

// Format number with thousand separator for display in input
const formatInputAmount = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const submitPayment = () => {
    if (paymentForm.value.amount <= 0) {
        Swal.fire('Error', 'Payment amount must be greater than 0', 'error');
        return;
    }
    if (paymentForm.value.amount > amountDue.value) {
        Swal.fire('Error', `Payment amount cannot exceed ${formatCurrency(amountDue.value)}`, 'error');
        return;
    }

    isLoading.value = true;
    axios.post(`/payments/api/sales-order/${so.value.id}`, paymentForm.value)
        .then(() => {
            Swal.fire('Success!', 'Payment recorded successfully.', 'success');
            showPaymentModal.value = false;
            router.reload();
        })
        .catch((error) => {
            Swal.fire('Error!', error.response?.data?.message || 'Failed to record payment.', 'error');
        })
        .finally(() => {
            isLoading.value = false;
        });
};
</script>

<template>
    <AppLayout title="Sales Order Detail">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('sales-orders.list')" class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                        {{ so?.so_number }}
                    </h2>
                    <span :class="['badge', statusColors[currentStatus]]">{{ so?.status_label || currentStatus }}</span>
                </div>
                <div class="flex gap-2">
                    <Link v-if="isDraft" :href="`/sales-orders/edit/${so?.id}`"
                        class="btn btn-sm btn-light">
                        <i class="ki-filled ki-pencil me-1"></i> Edit
                    </Link>
                    <button v-if="isDraft" @click="confirmSO" class="btn btn-sm btn-primary">
                        <i class="ki-filled ki-check-circle me-1"></i> Confirm
                    </button>
                    <button v-if="canShip" @click="markAsShipped"
                        class="btn btn-sm btn-info">
                        <i class="ki-filled ki-delivery me-1"></i> Mark Shipped
                    </button>
                    <button v-if="isShipped" @click="markAsDelivered"
                        class="btn btn-sm btn-success">
                        <i class="ki-filled ki-double-check me-1"></i> Mark Delivered
                    </button>
                    <button v-if="canCancel" @click="cancelSO"
                        class="btn btn-sm btn-danger">
                        <i class="ki-filled ki-cross-circle me-1"></i> Cancel
                    </button>
                </div>
            </div>
        </template>

        <div class="container-fixed py-5">
            <!-- Action Bar - Always Visible -->
            <div class="card mb-5">
                <div class="card-body py-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <Link :href="route('sales-orders.list')" class="btn btn-icon btn-light btn-sm">
                                <i class="ki-filled ki-arrow-left"></i>
                            </Link>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ so?.so_number }}</h2>
                                <span :class="['badge badge-sm', statusColors[currentStatus]]">{{ so?.status_label || currentStatus }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Link v-if="isDraft" :href="`/sales-orders/edit/${so?.id}`"
                                class="btn btn-sm btn-light">
                                <i class="ki-filled ki-pencil me-1"></i> Edit
                            </Link>
                            <button v-if="isDraft" @click="confirmSO" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-check-circle me-1"></i> Confirm
                            </button>
                            <button v-if="canShip" @click="markAsShipped"
                                class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-delivery me-1"></i> Mark Shipped
                            </button>
                            <button v-if="isShipped" @click="markAsDelivered"
                                class="btn btn-sm btn-success" style="color: white !important;">
                                <i class="ki-filled ki-double-check me-1"></i> Mark Delivered
                            </button>
                            <button v-if="canCancel" @click="cancelSO"
                                class="btn btn-sm btn-danger">
                                <i class="ki-filled ki-cross-circle me-1"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Basic Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Customer</span>
                                    <p class="font-medium">{{ so?.customer_name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Order Date</span>
                                    <p class="font-medium">{{ so?.order_date }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Delivery Date</span>
                                    <p class="font-medium">{{ so?.delivery_date || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Currency</span>
                                    <p class="font-medium">{{ so?.currency_code }}</p>
                                </div>
                            </div>
                            <div v-if="so?.shipping_address" class="mt-4 pt-4 border-t">
                                <span class="text-sm text-gray-500">Shipping Address</span>
                                <p class="mt-1">{{ so?.shipping_address }}</p>
                            </div>
                            <div v-if="so?.notes" class="mt-4 pt-4 border-t">
                                <span class="text-sm text-gray-500">Notes</span>
                                <p class="mt-1">{{ so?.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Items</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[200px]">Product</th>
                                            <th class="w-[80px] text-center">Qty</th>
                                            <th class="w-[120px] text-end">Unit Price</th>
                                            <th class="w-[100px] text-end">Discount</th>
                                            <th class="w-[120px] text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in so?.items" :key="item.id">
                                            <td>
                                                <div class="font-medium">{{ item.product_name }}</div>
                                                <div class="text-xs text-gray-500">SKU: {{ item.sku || 'N/A' }}</div>
                                            </td>
                                            <td class="text-center">{{ formatQty(item.quantity) }}</td>
                                            <td class="text-end">{{ formatCurrency(item.unit_price, so?.currency_code) }}</td>
                                            <td class="text-end">{{ formatCurrency(item.discount, so?.currency_code) }}</td>
                                            <td class="text-end font-medium">{{ formatCurrency(item.subtotal, so?.currency_code) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payment History</h3>
                            <button v-if="amountDue > 0 && so?.status !== 'cancelled'" @click="openPaymentModal"
                                class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-dollar me-1"></i> Add Payment
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-border">
                                <thead>
                                    <tr>
                                        <th>Payment #</th>
                                        <th class="text-center">Date</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in so?.payments" :key="payment.id">
                                        <td>{{ payment.payment_number }}</td>
                                        <td class="text-center">{{ payment.payment_date }}</td>
                                        <td>{{ payment.payment_method }}</td>
                                        <td class="text-end font-medium">{{ formatCurrency(payment.amount, so?.currency_code) }}</td>
                                        <td class="text-center">
                                            <span :class="['badge badge-sm', payment.status === 'confirmed' ? 'badge-success' : 'badge-danger']">
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!so?.payments || so?.payments.length === 0">
                                        <td colspan="5" class="text-center text-gray-500 py-6">No payments recorded yet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary -->
                <div class="space-y-5">
                    <!-- Payment Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payment Summary</h3>
                            <span :class="['badge', paymentStatusColors[so?.payment_status]]">
                                {{ so?.payment_status_label }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span>{{ formatCurrency(so?.subtotal, so?.currency_code) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Discount</span>
                                    <span class="text-red-500">-{{ formatCurrency(so?.discount_amount, so?.currency_code) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax</span>
                                    <span>{{ formatCurrency(so?.tax_amount, so?.currency_code) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping</span>
                                    <span>{{ formatCurrency(so?.shipping_cost, so?.currency_code) }}</span>
                                </div>
                                <div class="border-t pt-3 flex justify-between">
                                    <span class="font-semibold">Grand Total</span>
                                    <span class="font-bold text-lg">{{ formatCurrency(so?.grand_total, so?.currency_code) }}</span>
                                </div>
                                <div class="border-t pt-3 flex justify-between">
                                    <span class="text-gray-600">Amount Paid</span>
                                    <span class="text-success">{{ formatCurrency(so?.amount_paid, so?.currency_code) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Amount Due</span>
                                    <span class="font-bold text-danger">{{ formatCurrency(amountDue, so?.currency_code) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profit Summary (Permission-gated) -->
                    <div v-if="profitData" class="card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <h3 class="card-title">Profit Analysis</h3>
                                <span class="badge badge-sm badge-success">
                                    <i class="ki-filled ki-shield-tick text-[10px] mr-1"></i>
                                    Authorized
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Revenue</span>
                                    <span class="font-medium text-blue-600">{{ formatCurrency(profitData.revenue, so?.currency_code) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Cost of Goods</span>
                                    <span class="font-medium text-red-600">{{ formatCurrency(profitData.cogs, so?.currency_code) }}</span>
                                </div>
                                <div class="border-t pt-3 flex justify-between">
                                    <span class="font-semibold">Gross Profit</span>
                                    <span
                                        class="font-bold"
                                        :class="(profitData.gross_profit || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'"
                                    >
                                        {{ formatCurrency(profitData.gross_profit, so?.currency_code) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Margin</span>
                                    <span
                                        class="badge badge-sm"
                                        :class="{
                                            'badge-success': (profitData.margin_percent || 0) >= 30,
                                            'badge-warning': (profitData.margin_percent || 0) >= 15 && (profitData.margin_percent || 0) < 30,
                                            'badge-danger': (profitData.margin_percent || 0) < 15
                                        }"
                                    >
                                        {{ profitData.margin_percent || 0 }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Per-item profit breakdown toggle -->
                            <div v-if="profitData.items?.length" class="mt-4 pt-4 border-t">
                                <button
                                    type="button"
                                    class="text-sm text-primary hover:underline flex items-center gap-1"
                                    @click="showItemProfit = !showItemProfit"
                                >
                                    <i :class="['ki-filled', showItemProfit ? 'ki-minus' : 'ki-plus']" class="text-xs"></i>
                                    {{ showItemProfit ? 'Hide' : 'Show' }} Item Breakdown
                                </button>
                                <div v-if="showItemProfit" class="mt-3 space-y-2">
                                    <div
                                        v-for="item in profitData.items"
                                        :key="item.product_id"
                                        class="bg-gray-50 rounded-lg p-3"
                                    >
                                        <div class="text-sm font-medium text-gray-900 truncate" :title="item.product_name">
                                            {{ item.product_name }}
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 mt-2 text-xs">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Revenue:</span>
                                                <span>{{ formatCurrency(item.revenue, so?.currency_code) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Cost:</span>
                                                <span>{{ formatCurrency(item.cogs, so?.currency_code) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Profit:</span>
                                                <span
                                                    :class="item.profit >= 0 ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium'"
                                                >
                                                    {{ formatCurrency(item.profit, so?.currency_code) }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Margin:</span>
                                                <span
                                                    :class="{
                                                        'text-green-600': item.margin_percent >= 30,
                                                        'text-yellow-600': item.margin_percent >= 15 && item.margin_percent < 30,
                                                        'text-red-600': item.margin_percent < 15
                                                    }"
                                                >
                                                    {{ item.margin_percent }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Created Info -->
                    <div class="card">
                        <div class="card-body">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created By</span>
                                    <span>{{ so?.created_by }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created At</span>
                                    <span>{{ so?.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal - Redesigned -->
        <div v-if="showPaymentModal" class="modal open" data-modal="true">
            <div class="modal-content max-w-[600px] max-h-[90vh] flex flex-col bg-white dark:bg-coal-600">
                <!-- Modal Header -->
                <div class="modal-header border-b-0 pb-0 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                            <i class="ki-filled ki-dollar text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="modal-title mb-0 text-gray-900 dark:text-white">Record Payment</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ so?.so_number }}</span>
                        </div>
                    </div>
                    <button class="btn btn-xs btn-icon btn-light" @click="showPaymentModal = false" :disabled="isLoading">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>

                <div class="modal-body pt-4 overflow-y-auto flex-1">
                    <!-- Payment Summary Card -->
                    <div class="rounded-lg bg-gray-100 dark:bg-coal-500 p-4 mb-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400 block">Amount Due</span>
                                <span class="text-2xl font-bold text-danger">{{ formatCurrency(amountDue, so?.currency_code) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-gray-600 dark:text-gray-400 block">Order Total</span>
                                <span class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ formatCurrency(so?.grand_total, so?.currency_code) }}</span>
                            </div>
                        </div>
                        <!-- Quick Amount Buttons -->
                        <div class="flex gap-2 mt-3">
                            <button type="button" @click="setFullPayment"
                                class="btn btn-sm flex-1"
                                :class="paymentForm.amount === amountDue ? 'btn-primary' : 'btn-light'"
                                :disabled="isLoading">
                                <i class="ki-filled ki-check-circle me-1"></i> Pay Full
                            </button>
                            <button type="button" @click="setHalfPayment"
                                class="btn btn-sm flex-1"
                                :class="paymentForm.amount === Math.round(amountDue / 2 * 100) / 100 ? 'btn-primary' : 'btn-light'"
                                :disabled="isLoading">
                                <i class="ki-filled ki-percentage me-1"></i> Pay 50%
                            </button>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="space-y-5">
                        <!-- Amount & Date Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label text-gray-700 dark:text-gray-300">
                                    <i class="ki-filled ki-dollar text-gray-400 me-1"></i>
                                    Payment Amount <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ so?.currency_code }}</span>
                                    <input type="number" v-model="paymentForm.amount"
                                        class="input w-full"
                                        :max="amountDue"
                                        step="0.01"
                                        min="0.01"
                                        :disabled="isLoading"
                                        placeholder="Enter amount" />
                                </div>
                                <span v-if="paymentForm.amount > amountDue" class="text-xs text-danger mt-1 block">
                                    Amount exceeds balance due
                                </span>
                            </div>
                            <div>
                                <label class="form-label text-gray-700 dark:text-gray-300">
                                    <i class="ki-filled ki-calendar text-gray-400 me-1"></i>
                                    Payment Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" v-model="paymentForm.payment_date"
                                    class="input w-full"
                                    :disabled="isLoading" />
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <div>
                            <label class="form-label mb-3 text-gray-700 dark:text-gray-300">
                                <i class="ki-filled ki-wallet text-gray-400 me-1"></i>
                                Payment Method <span class="text-danger">*</span>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <button v-for="method in paymentMethods" :key="method.value"
                                    type="button"
                                    @click="paymentForm.payment_method = method.value"
                                    class="flex items-center justify-center gap-2 p-3 rounded-lg border-2 transition-all bg-white dark:bg-coal-500"
                                    :class="paymentForm.payment_method === method.value
                                        ? 'border-primary bg-primary/5 dark:bg-primary/10 text-primary'
                                        : 'border-gray-200 dark:border-coal-400 hover:border-gray-300 dark:hover:border-coal-300 text-gray-700 dark:text-gray-300'"
                                    :disabled="isLoading">
                                    <i :class="[method.icon, paymentForm.payment_method === method.value ? 'text-primary' : 'text-gray-400']"></i>
                                    <span class="text-sm font-medium">{{ method.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Bank Details (Conditional) -->
                        <div v-if="needsBankDetails" class="rounded-lg border border-gray-200 dark:border-coal-400 p-4 space-y-4 bg-gray-50 dark:bg-coal-500">
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <i class="ki-filled ki-bank text-gray-400"></i>
                                Bank / Transfer Details
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Bank Name <span class="text-gray-400 text-xs">(Optional)</span>
                                    </label>
                                    <input type="text" v-model="paymentForm.bank_name"
                                        class="input w-full"
                                        placeholder="e.g. BCA, Mandiri, BNI"
                                        :disabled="isLoading" />
                                </div>
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Account Number <span class="text-gray-400 text-xs">(Optional)</span>
                                    </label>
                                    <input type="text" v-model="paymentForm.account_number"
                                        class="input w-full"
                                        placeholder="Account number"
                                        :disabled="isLoading" />
                                </div>
                            </div>
                        </div>

                        <!-- Reference Number -->
                        <div>
                            <label class="form-label text-gray-700 dark:text-gray-300">
                                <i class="ki-filled ki-document text-gray-400 me-1"></i>
                                Reference / Transaction Number <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" v-model="paymentForm.reference_number"
                                class="input w-full"
                                placeholder="e.g. TRX-123456, Receipt No."
                                :disabled="isLoading" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="form-label text-gray-700 dark:text-gray-300">
                                <i class="ki-filled ki-message-text text-gray-400 me-1"></i>
                                Notes <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <textarea v-model="paymentForm.notes"
                                class="textarea w-full"
                                rows="2"
                                placeholder="Additional notes"
                                :disabled="isLoading"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-t border-gray-200 dark:border-coal-400 mt-2 flex-shrink-0">
                    <button class="btn btn-light" @click="showPaymentModal = false" :disabled="isLoading">
                        Cancel
                    </button>
                    <button class="btn btn-primary" @click="submitPayment"
                        :disabled="isLoading || paymentForm.amount <= 0 || paymentForm.amount > amountDue">
                        <i v-if="!isLoading" class="ki-filled ki-check me-1"></i>
                        <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isLoading ? 'Processing...' : 'Record Payment' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background-color: rgba(0, 0, 0, 0.5);
}

/* Input group styling */
.input-group {
    display: flex;
    align-items: stretch;
}

.input-group .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-right: 0;
    border-radius: 0.375rem 0 0 0.375rem;
}

.input-group .input {
    border-radius: 0 0.375rem 0.375rem 0;
}

/* Dark mode input group */
:deep(.dark) .input-group .input-group-text {
    background-color: #374151;
    border-color: #4b5563;
    color: #9ca3af;
}

/* Spinner for loading state */
.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    vertical-align: -0.125em;
    border: 0.15em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

.spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
    border-width: 0.125em;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}
</style>
