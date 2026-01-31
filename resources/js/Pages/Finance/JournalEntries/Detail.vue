<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    entry: Object,
});

const entry = computed(() => props.entry);
const isLoading = ref(false);

// Currency formatting
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency }).format(value || 0);
};

// Number formatting for exchange rate
const formatNumber = (value, decimals = 4) => {
    if (value == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals,
    }).format(value);
};

// Format date/time for timeline
const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Status badge colors mapping
const statusColors = {
    draft: 'badge-light',
    posted: 'badge-success',
    voided: 'badge-danger',
};

// Get reference type label
const getReferenceTypeLabel = (type) => {
    const labels = {
        sales_order: 'Sales Order',
        purchase_order: 'Purchase Order',
        expense: 'Expense',
        payment: 'Payment',
        invoice: 'Invoice',
        manual: 'Manual Entry',
    };
    return labels[type] || type || '-';
};

// Get reference link URL
const getReferenceUrl = (type, id) => {
    if (!type || !id) return null;
    const urls = {
        sales_order: `/sales-orders/detail/${id}`,
        purchase_order: `/purchase-orders/detail/${id}`,
        expense: `/finance/expenses/${id}`,
        payment: `/payments/${id}`,
        invoice: `/invoices/${id}`,
    };
    return urls[type] || null;
};

// Check if entry is balanced
const isBalanced = computed(() => {
    return entry.value?.is_balanced ?? (entry.value?.total_debit === entry.value?.total_credit);
});

// Post journal entry
const postEntry = () => {
    Swal.fire({
        title: 'Post Journal Entry?',
        text: 'This will post the entry to the general ledger. This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, post it!',
        confirmButtonColor: '#50cd89',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.post(`/finance/api/journal-entries/${entry.value.id}/post`)
                .then(() => {
                    Swal.fire('Posted!', 'Journal entry has been posted to the general ledger.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to post journal entry.', 'error');
                })
                .finally(() => {
                    isLoading.value = false;
                });
        }
    });
};

// Void journal entry
const voidEntry = () => {
    Swal.fire({
        title: 'Void Journal Entry?',
        text: 'Please provide a reason for voiding this entry.',
        icon: 'warning',
        input: 'textarea',
        inputLabel: 'Void Reason',
        inputPlaceholder: 'Enter the reason for voiding this entry...',
        inputAttributes: {
            'aria-label': 'Void reason',
        },
        inputValidator: (value) => {
            if (!value || value.trim().length === 0) {
                return 'You must provide a reason for voiding this entry.';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Void Entry',
        confirmButtonColor: '#f1416c',
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            isLoading.value = true;
            axios.post(`/finance/api/journal-entries/${entry.value.id}/void`, {
                reason: result.value,
            })
                .then(() => {
                    Swal.fire('Voided!', 'Journal entry has been voided.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to void journal entry.', 'error');
                })
                .finally(() => {
                    isLoading.value = false;
                });
        }
    });
};

// Delete journal entry
const deleteEntry = () => {
    Swal.fire({
        title: 'Delete Journal Entry?',
        text: 'This action cannot be undone. The entry will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f1416c',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.delete(`/finance/api/journal-entries/${entry.value.id}`)
                .then(() => {
                    Swal.fire('Deleted!', 'Journal entry has been deleted.', 'success');
                    router.visit('/finance/journal-entries');
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to delete journal entry.', 'error');
                    isLoading.value = false;
                });
        }
    });
};
</script>

<template>
    <AppLayout :title="entry?.entry_number">
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1 text-sm text-gray-500 mb-5">
                <Link href="/" class="hover:text-primary">Home</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance" class="hover:text-primary">Finance</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance/journal-entries" class="hover:text-primary">Journal Entries</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <span class="text-gray-700">{{ entry?.entry_number }}</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold text-gray-900">{{ entry?.entry_number }}</h1>
                    <span :class="['badge', statusColors[entry?.status] || 'badge-light']">
                        {{ entry?.status_label }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="entry?.can_edit"
                        :href="`/finance/journal-entries/${entry?.id}/edit`"
                        class="btn btn-sm btn-light"
                    >
                        <i class="ki-filled ki-pencil me-1"></i> Edit
                    </Link>
                    <button
                        v-if="entry?.can_post"
                        @click="postEntry"
                        class="btn btn-sm btn-success"
                        :disabled="isLoading"
                    >
                        <i class="ki-filled ki-send me-1"></i> Post
                    </button>
                    <button
                        v-if="entry?.can_void"
                        @click="voidEntry"
                        class="btn btn-sm btn-warning"
                        :disabled="isLoading"
                    >
                        <i class="ki-filled ki-cross-circle me-1"></i> Void
                    </button>
                    <button
                        v-if="entry?.can_delete"
                        @click="deleteEntry"
                        class="btn btn-sm btn-danger"
                        :disabled="isLoading"
                    >
                        <i class="ki-filled ki-trash me-1"></i> Delete
                    </button>
                    <Link href="/finance/journal-entries" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-arrow-left me-1"></i> Back to List
                    </Link>
                </div>
            </div>

            <!-- Void Reason Alert (shown when entry is voided) -->
            <div v-if="entry?.status === 'voided' && entry?.void_reason" class="mb-5">
                <div class="bg-danger/10 border border-danger/20 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-danger/20 flex items-center justify-center">
                            <i class="ki-filled ki-information-2 text-danger text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-danger mb-1">This entry has been voided</h4>
                            <p class="text-sm text-gray-700">{{ entry?.void_reason }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Two Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column (2/3) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Card 1: Entry Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Entry Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <dt class="text-sm text-gray-500">Entry Number</dt>
                                    <dd class="font-medium text-gray-900">{{ entry?.entry_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Entry Date</dt>
                                    <dd class="font-medium text-gray-900">{{ entry?.entry_date_formatted }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Entry Type</dt>
                                    <dd class="font-medium text-gray-900">
                                        <span class="badge badge-sm badge-outline badge-primary">
                                            {{ entry?.entry_type_label }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Fiscal Period</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ entry?.fiscal_period?.fiscal_year }} - {{ entry?.fiscal_period?.name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Currency</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ entry?.currency_code }}
                                        <span v-if="entry?.exchange_rate && entry?.exchange_rate !== 1" class="text-gray-500 text-sm ms-1">
                                            (Rate: {{ formatNumber(entry?.exchange_rate) }})
                                        </span>
                                    </dd>
                                </div>
                                <div class="md:col-span-2">
                                    <dt class="text-sm text-gray-500">Memo</dt>
                                    <dd class="font-medium text-gray-900">{{ entry?.memo || '-' }}</dd>
                                </div>

                                <!-- Reference -->
                                <div v-if="entry?.reference_type || entry?.reference_id">
                                    <dt class="text-sm text-gray-500">Reference</dt>
                                    <dd class="font-medium text-gray-900">
                                        <Link
                                            v-if="getReferenceUrl(entry?.reference_type, entry?.reference_id)"
                                            :href="getReferenceUrl(entry?.reference_type, entry?.reference_id)"
                                            class="text-primary hover:underline"
                                        >
                                            {{ getReferenceTypeLabel(entry?.reference_type) }} #{{ entry?.reference_id }}
                                            <i class="ki-filled ki-exit-right-corner text-xs ms-1"></i>
                                        </Link>
                                        <span v-else>
                                            {{ getReferenceTypeLabel(entry?.reference_type) }}
                                            <span v-if="entry?.reference_id">#{{ entry?.reference_id }}</span>
                                        </span>
                                    </dd>
                                </div>

                                <!-- Reverses (links to original entry this one reverses) -->
                                <div v-if="entry?.reverses">
                                    <dt class="text-sm text-gray-500">Reverses Entry</dt>
                                    <dd class="font-medium text-gray-900">
                                        <Link
                                            :href="`/finance/journal-entries/${entry?.reverses?.id}`"
                                            class="text-primary hover:underline"
                                        >
                                            {{ entry?.reverses?.entry_number }}
                                            <i class="ki-filled ki-exit-right-corner text-xs ms-1"></i>
                                        </Link>
                                    </dd>
                                </div>

                                <!-- Reversed By (links to reversing entry) -->
                                <div v-if="entry?.reversed_by">
                                    <dt class="text-sm text-gray-500">Reversed By</dt>
                                    <dd class="font-medium text-gray-900">
                                        <Link
                                            :href="`/finance/journal-entries/${entry?.reversed_by?.id}`"
                                            class="text-danger hover:underline"
                                        >
                                            {{ entry?.reversed_by?.entry_number }}
                                            <i class="ki-filled ki-exit-right-corner text-xs ms-1"></i>
                                        </Link>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Card 2: Journal Lines -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Journal Lines</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-border">
                                    <thead>
                                        <tr>
                                            <th class="w-[50px] text-center">#</th>
                                            <th class="w-[120px]">Account Code</th>
                                            <th class="min-w-[200px]">Account Name</th>
                                            <th class="min-w-[150px]">Description</th>
                                            <th class="w-[150px] text-end">Debit</th>
                                            <th class="w-[150px] text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="line in entry?.lines" :key="line.id">
                                            <td class="text-center text-gray-500">{{ line.line_number }}</td>
                                            <td>
                                                <span class="font-mono text-sm">{{ line.account?.code }}</span>
                                            </td>
                                            <td>
                                                <div class="font-medium">{{ line.account?.name }}</div>
                                                <div class="text-xs text-gray-500">{{ line.account?.type }}</div>
                                            </td>
                                            <td class="text-gray-600">{{ line.description || '-' }}</td>
                                            <td class="text-end">
                                                <span v-if="line.debit_amount > 0" class="font-medium">
                                                    {{ formatCurrency(line.debit_amount, entry?.currency_code) }}
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="text-end">
                                                <span v-if="line.credit_amount > 0" class="font-medium">
                                                    {{ formatCurrency(line.credit_amount, entry?.currency_code) }}
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                        </tr>

                                        <!-- Empty state -->
                                        <tr v-if="!entry?.lines || entry?.lines.length === 0">
                                            <td colspan="6" class="text-center text-gray-500 py-8">
                                                <i class="ki-outline ki-document text-gray-300 text-3xl mb-2 block"></i>
                                                No journal lines
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="text-end font-semibold text-gray-900">Total</td>
                                            <td class="text-end font-bold text-gray-900">
                                                {{ formatCurrency(entry?.total_debit, entry?.currency_code) }}
                                            </td>
                                            <td class="text-end font-bold text-gray-900">
                                                {{ formatCurrency(entry?.total_credit, entry?.currency_code) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end font-medium text-gray-700">Balance Status</td>
                                            <td colspan="2" class="text-end">
                                                <span
                                                    v-if="isBalanced"
                                                    class="inline-flex items-center gap-1 text-success font-medium"
                                                >
                                                    <i class="ki-filled ki-check-circle"></i>
                                                    Balanced
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 text-danger font-medium"
                                                >
                                                    <i class="ki-filled ki-information-2"></i>
                                                    Unbalanced
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1/3) -->
                <div class="space-y-5">
                    <!-- Card 3: Status & Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Status & Timeline</h3>
                        </div>
                        <div class="card-body">
                            <!-- Current Status -->
                            <div class="mb-5">
                                <div class="text-sm text-gray-500 mb-2">Current Status</div>
                                <span :class="['badge badge-lg', statusColors[entry?.status] || 'badge-light']">
                                    {{ entry?.status_label }}
                                </span>
                            </div>

                            <!-- Balanced Indicator -->
                            <div class="mb-5">
                                <div class="text-sm text-gray-500 mb-2">Balance Check</div>
                                <div
                                    v-if="isBalanced"
                                    class="flex items-center gap-2 p-3 rounded-lg bg-success/10"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-success/20 flex items-center justify-center">
                                        <i class="ki-filled ki-check text-success"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-success">Balanced</div>
                                        <div class="text-xs text-gray-500">Debits equal credits</div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="flex items-center gap-2 p-3 rounded-lg bg-danger/10"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-danger/20 flex items-center justify-center">
                                        <i class="ki-filled ki-information-2 text-danger"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-danger">Unbalanced</div>
                                        <div class="text-xs text-gray-500">Debits do not equal credits</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div class="border-t pt-4">
                                <div class="text-sm text-gray-500 mb-3">Timeline</div>
                                <div class="space-y-4">
                                    <!-- Created -->
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="ki-filled ki-plus-circle text-gray-500 text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900">Created</div>
                                            <div class="text-xs text-gray-500">{{ formatDateTime(entry?.created_at) }}</div>
                                            <div class="text-xs text-gray-500">by {{ entry?.creator?.name || '-' }}</div>
                                        </div>
                                    </div>

                                    <!-- Posted (if applicable) -->
                                    <div v-if="entry?.posted_at && entry?.poster" class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                                            <i class="ki-filled ki-send text-success text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900">Posted</div>
                                            <div class="text-xs text-gray-500">{{ formatDateTime(entry?.posted_at) }}</div>
                                            <div class="text-xs text-gray-500">by {{ entry?.poster?.name }}</div>
                                        </div>
                                    </div>

                                    <!-- Voided (if applicable) -->
                                    <div v-if="entry?.voided_at && entry?.voider" class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-danger/10 flex items-center justify-center">
                                            <i class="ki-filled ki-cross-circle text-danger text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900">Voided</div>
                                            <div class="text-xs text-gray-500">{{ formatDateTime(entry?.voided_at) }}</div>
                                            <div class="text-xs text-gray-500">by {{ entry?.voider?.name }}</div>
                                            <div v-if="entry?.void_reason" class="text-xs text-danger mt-1 italic">
                                                "{{ entry?.void_reason }}"
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Amount Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Amount Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Debit</span>
                                    <span class="font-medium text-gray-900">
                                        {{ formatCurrency(entry?.total_debit, entry?.currency_code) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Credit</span>
                                    <span class="font-medium text-gray-900">
                                        {{ formatCurrency(entry?.total_credit, entry?.currency_code) }}
                                    </span>
                                </div>
                                <div class="border-t pt-3 flex justify-between">
                                    <span class="font-semibold text-gray-900">Difference</span>
                                    <span
                                        class="font-bold"
                                        :class="isBalanced ? 'text-success' : 'text-danger'"
                                    >
                                        {{ formatCurrency(Math.abs((entry?.total_debit || 0) - (entry?.total_credit || 0)), entry?.currency_code) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Badge large size */
.badge-lg {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
</style>
