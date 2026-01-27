<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    payment: Object,
});

const payment = computed(() => props.payment);

const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency }).format(value || 0);
};

const paymentTypeColors = {
    purchase: 'badge-warning',
    sales: 'badge-success',
};

const statusColors = {
    confirmed: 'badge-success',
    cancelled: 'badge-danger',
};

const cancelPayment = () => {
    Swal.fire({
        title: 'Cancel Payment?',
        text: 'This will cancel the payment and update the order payment status.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, cancel it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/payments/api/cancel/${payment.value.id}`)
                .then(() => {
                    Swal.fire('Cancelled!', 'Payment has been cancelled.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to cancel payment.', 'error');
                });
        }
    });
};

const goToReference = () => {
    const ref = payment.value?.reference;
    if (!ref || !ref.id) {
        console.error('Reference data:', ref);
        Swal.fire('Error', 'Related order information not available.', 'warning');
        return;
    }

    if (ref.type === 'Purchase Order') {
        router.visit(`/purchase-orders/${ref.id}`);
    } else if (ref.type === 'Sales Order') {
        router.visit(`/sales-orders/${ref.id}`);
    }
};
</script>

<template>
    <AppLayout title="Payment Detail">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('payments.list')" class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                        {{ payment?.payment_number }}
                    </h2>
                    <span :class="['badge', paymentTypeColors[payment?.payment_type]]" class="capitalize">
                        {{ payment?.payment_type }}
                    </span>
                    <span :class="['badge', statusColors[payment?.status]]" class="capitalize">
                        {{ payment?.status }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button v-if="payment?.status === 'confirmed'" @click="cancelPayment"
                        class="btn btn-sm btn-danger">
                        <i class="ki-filled ki-cross-circle me-1"></i> Cancel Payment
                    </button>
                </div>
            </div>
        </template>

        <div class="container-fixed py-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Payment Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payment Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Payment Number</span>
                                    <p class="font-medium">{{ payment?.payment_number }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Payment Date</span>
                                    <p class="font-medium">{{ payment?.payment_date }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Payment Method</span>
                                    <p class="font-medium">{{ payment?.payment_method_label }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Currency</span>
                                    <p class="font-medium">{{ payment?.currency_code }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Amount</span>
                                    <p class="font-bold text-lg text-primary">
                                        {{ formatCurrency(payment?.amount, payment?.currency_code) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Status</span>
                                    <p>
                                        <span :class="['badge', statusColors[payment?.status]]" class="capitalize">
                                            {{ payment?.status }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Bank Details -->
                            <div v-if="payment?.bank_name || payment?.account_number || payment?.reference_number"
                                class="mt-5 pt-5 border-t">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Bank Details</h4>
                                <div class="grid grid-cols-3 gap-5">
                                    <div v-if="payment?.bank_name">
                                        <span class="text-sm text-gray-500">Bank Name</span>
                                        <p class="font-medium">{{ payment?.bank_name }}</p>
                                    </div>
                                    <div v-if="payment?.account_number">
                                        <span class="text-sm text-gray-500">Account Number</span>
                                        <p class="font-medium">{{ payment?.account_number }}</p>
                                    </div>
                                    <div v-if="payment?.reference_number">
                                        <span class="text-sm text-gray-500">Reference Number</span>
                                        <p class="font-medium">{{ payment?.reference_number }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div v-if="payment?.notes" class="mt-5 pt-5 border-t">
                                <span class="text-sm text-gray-500">Notes</span>
                                <p class="mt-1">{{ payment?.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reference Order Card -->
                    <div v-if="payment?.reference" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Related Order</h3>
                            <button @click="goToReference" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-eye me-1"></i> View Order
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Order Type</span>
                                    <p class="font-medium">{{ payment?.reference?.type }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Order Number</span>
                                    <p class="font-medium text-primary">{{ payment?.reference?.number }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Party Name</span>
                                    <p class="font-medium">{{ payment?.reference?.party_name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Order Total</span>
                                    <p class="font-medium">{{ formatCurrency(payment?.reference?.grand_total, payment?.currency_code) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Meta Info -->
                <div class="space-y-5">
                    <!-- Created Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Record Info</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created By</span>
                                    <span>{{ payment?.created_by }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created At</span>
                                    <span>{{ payment?.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body space-y-3">
                            <button @click="goToReference" class="btn btn-light w-full">
                                <i class="ki-filled ki-document me-2"></i>
                                View Related Order
                            </button>
                            <button v-if="payment?.status === 'confirmed'" @click="cancelPayment"
                                class="btn btn-danger w-full">
                                <i class="ki-filled ki-cross-circle me-2"></i>
                                Cancel Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
