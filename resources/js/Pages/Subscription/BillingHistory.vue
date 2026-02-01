<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

// Local filter state
const localFilters = ref({
    status: props.filters.status || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
});

// Format currency
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Get invoice status badge class
const getStatusClass = (status) => {
    const classes = {
        paid: 'badge-success',
        pending: 'badge-warning',
        awaiting_verification: 'badge-info',
        failed: 'badge-danger',
        expired: 'badge-secondary',
        cancelled: 'badge-secondary',
    };
    return classes[status?.toLowerCase()] || 'badge-secondary';
};

// Apply filters
const applyFilters = () => {
    const query = {};
    if (localFilters.value.status) query.status = localFilters.value.status;
    if (localFilters.value.from_date) query.from_date = localFilters.value.from_date;
    if (localFilters.value.to_date) query.to_date = localFilters.value.to_date;

    router.get('/subscription/billing-history', query, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset filters
const resetFilters = () => {
    localFilters.value = {
        status: '',
        from_date: '',
        to_date: '',
    };
    router.get('/subscription/billing-history', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Status filter options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'awaiting_verification', label: 'Awaiting Verification' },
    { value: 'paid', label: 'Paid' },
    { value: 'failed', label: 'Failed' },
    { value: 'expired', label: 'Expired' },
    { value: 'cancelled', label: 'Cancelled' },
];
</script>

<template>
    <AppLayout title="Billing History">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Billing History
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <Link href="/subscription" class="text-gray-500 hover:text-primary transition-colors">
                    Subscription
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Billing History</span>
            </div>

            <!-- Page Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Billing History</h1>
                    <p class="text-gray-500 mt-1">View all your invoices and payment history</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-6">
                <div class="card-body p-4">
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- Status Filter -->
                        <div class="flex-1 min-w-[150px]">
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Status</label>
                            <select
                                v-model="localFilters.status"
                                class="select select-sm w-full"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="flex-1 min-w-[150px]">
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">From Date</label>
                            <input
                                type="date"
                                v-model="localFilters.from_date"
                                class="input input-sm w-full"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="flex-1 min-w-[150px]">
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">To Date</label>
                            <input
                                type="date"
                                v-model="localFilters.to_date"
                                class="input input-sm w-full"
                            />
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex gap-2">
                            <button type="button" @click="applyFilters" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-filter me-1"></i>
                                Apply
                            </button>
                            <button type="button" @click="resetFilters" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-arrows-loop me-1"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices Table Card -->
            <div class="card">
                <div class="card-header border-b border-gray-200">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="ki-filled ki-document text-gray-500"></i>
                        Invoices
                    </h3>
                    <span class="badge badge-sm badge-light">
                        {{ invoices.total || invoices.data?.length || 0 }} total
                    </span>
                </div>
                <div class="card-body p-0">
                    <div v-if="invoices.data && invoices.data.length > 0" class="overflow-x-auto">
                        <table class="table table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[150px]">Invoice #</th>
                                    <th class="min-w-[150px]">Plan</th>
                                    <th class="w-[100px] text-center">Billing</th>
                                    <th class="min-w-[140px] text-end">Amount</th>
                                    <th class="w-[120px] text-center">Due Date</th>
                                    <th class="w-[120px] text-center">Paid At</th>
                                    <th class="w-[100px] text-center">Status</th>
                                    <th class="w-[100px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-slate-50">
                                    <td>
                                        <span class="font-medium text-gray-800">{{ invoice.invoice_number }}</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-600">{{ invoice.plan_name }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-sm badge-light">
                                            {{ invoice.billing_cycle }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ invoice.formatted_amount }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-sm text-gray-600">{{ invoice.due_date }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="invoice.paid_at" class="text-sm text-gray-600">
                                            {{ invoice.paid_at }}
                                        </span>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="getStatusClass(invoice.status)"
                                        >
                                            {{ invoice.status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-1">
                                            <Link
                                                v-if="invoice.status === 'pending' || invoice.status === 'awaiting_verification'"
                                                :href="`/subscription/payment-proof/${invoice.id}`"
                                                class="btn btn-xs btn-primary"
                                                title="Upload Payment Proof"
                                            >
                                                <i class="ki-filled ki-cloud-add"></i>
                                            </Link>
                                            <a
                                                :href="`/subscription/invoice/${invoice.id}/download`"
                                                class="btn btn-xs btn-light"
                                                title="Download Invoice"
                                            >
                                                <i class="ki-filled ki-file-down"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center py-16">
                        <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <i class="ki-filled ki-document text-gray-400 text-4xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-600 mb-2">No Invoices Found</h4>
                        <p class="text-sm text-gray-500 mb-4 text-center max-w-sm">
                            You don't have any invoices yet. Subscribe to a plan to get started.
                        </p>
                        <Link href="/subscription/plans" class="btn btn-primary">
                            <i class="ki-filled ki-price-tag me-2"></i>
                            View Plans
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.data && invoices.data.length > 0 && (invoices.prev_page_url || invoices.next_page_url)" class="card-footer border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">
                            Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }} entries
                        </span>
                        <div class="flex gap-2">
                            <Link
                                v-if="invoices.prev_page_url"
                                :href="invoices.prev_page_url"
                                class="btn btn-sm btn-light"
                                preserve-state
                            >
                                <i class="ki-filled ki-arrow-left me-1"></i>
                                Previous
                            </Link>
                            <Link
                                v-if="invoices.next_page_url"
                                :href="invoices.next_page_url"
                                class="btn btn-sm btn-light"
                                preserve-state
                            >
                                Next
                                <i class="ki-filled ki-arrow-right ms-1"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
