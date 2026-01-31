<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    expense: Object,
});

const expense = computed(() => props.expense);
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

// Format file size
const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
};

// Format payment method
const formatPaymentMethod = (method) => {
    const methods = {
        cash: 'Cash',
        bank_transfer: 'Bank Transfer',
        credit_card: 'Credit Card',
        cheque: 'Cheque',
        giro: 'Giro',
        e_wallet: 'E-Wallet',
    };
    return methods[method] || method || '-';
};

// Format recurring frequency
const formatRecurringFrequency = (frequency) => {
    const frequencies = {
        daily: 'Daily',
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        yearly: 'Yearly',
    };
    return frequencies[frequency] || frequency || '-';
};

// Status badge colors
const statusColors = {
    draft: 'badge-light',
    approved: 'badge-info',
    posted: 'badge-success',
    cancelled: 'badge-danger',
};

// Payment status badge colors
const paymentStatusColors = {
    unpaid: 'badge-danger',
    partial: 'badge-warning',
    paid: 'badge-success',
};

// Get file icon based on file type
const getFileIcon = (attachment) => {
    if (attachment.is_image) return 'ki-filled ki-picture';
    if (attachment.is_pdf) return 'ki-filled ki-document';
    return 'ki-filled ki-file';
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

// Approve expense
const approveExpense = () => {
    Swal.fire({
        title: 'Approve Expense?',
        text: 'This will approve the expense for posting.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, approve it!',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.post(`/finance/api/expenses/${expense.value.id}/approve`)
                .then(() => {
                    Swal.fire('Approved!', 'Expense has been approved.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to approve expense.', 'error');
                })
                .finally(() => {
                    isLoading.value = false;
                });
        }
    });
};

// Post expense
const postExpense = () => {
    Swal.fire({
        title: 'Post Expense?',
        text: 'This will post the expense to the general ledger.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, post it!',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.post(`/finance/api/expenses/${expense.value.id}/post`)
                .then(() => {
                    Swal.fire('Posted!', 'Expense has been posted.', 'success');
                    router.reload();
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to post expense.', 'error');
                })
                .finally(() => {
                    isLoading.value = false;
                });
        }
    });
};

// Delete expense
const deleteExpense = () => {
    Swal.fire({
        title: 'Delete Expense?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            isLoading.value = true;
            axios.delete(`/finance/api/expenses/${expense.value.id}`)
                .then(() => {
                    Swal.fire('Deleted!', 'Expense has been deleted.', 'success');
                    router.visit('/finance/expenses');
                })
                .catch((error) => {
                    Swal.fire('Error!', error.response?.data?.message || 'Failed to delete expense.', 'error');
                    isLoading.value = false;
                });
        }
    });
};
</script>

<template>
    <AppLayout :title="expense?.expense_number">
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1 text-sm text-gray-500 mb-5">
                <Link href="/" class="hover:text-primary">Home</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance" class="hover:text-primary">Finance</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance/expenses" class="hover:text-primary">Expenses</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <span class="text-gray-700">{{ expense?.expense_number }}</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold text-gray-900">{{ expense?.expense_number }}</h1>
                    <span :class="['badge', statusColors[expense?.status]]">
                        {{ expense?.status_label }}
                    </span>
                    <span :class="['badge', paymentStatusColors[expense?.payment_status]]">
                        {{ expense?.payment_status_label }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="expense?.can_edit"
                        :href="`/finance/expenses/${expense?.id}/edit`"
                        class="btn btn-sm btn-light"
                    >
                        <i class="ki-filled ki-pencil me-1"></i> Edit
                    </Link>
                    <button
                        v-if="expense?.can_approve"
                        @click="approveExpense"
                        class="btn btn-sm btn-info"
                        :disabled="isLoading"
                    >
                        <i class="ki-filled ki-check-circle me-1"></i> Approve
                    </button>
                    <button
                        v-if="expense?.can_post"
                        @click="postExpense"
                        class="btn btn-sm btn-success"
                        :disabled="isLoading"
                    >
                        <i class="ki-filled ki-send me-1"></i> Post
                    </button>
                    <button
                        v-if="expense?.can_delete"
                        @click="deleteExpense"
                        class="btn btn-sm btn-danger"
                        :disabled="isLoading"
                    >
                        <i class="ki-filled ki-trash me-1"></i> Delete
                    </button>
                    <Link href="/finance/expenses" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-arrow-left me-1"></i> Back to List
                    </Link>
                </div>
            </div>

            <!-- Main Content: Two Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Card 1: Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <dt class="text-sm text-gray-500">Expense Date</dt>
                                    <dd class="font-medium text-gray-900">{{ expense?.expense_date_formatted }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Category</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ expense?.category?.code }} - {{ expense?.category?.name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Expense Account</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ expense?.account?.code }} - {{ expense?.account?.name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Payee</dt>
                                    <dd class="font-medium text-gray-900">{{ expense?.payee || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Reference Number</dt>
                                    <dd class="font-medium text-gray-900">{{ expense?.reference_number || '-' }}</dd>
                                </div>
                                <div class="md:col-span-2">
                                    <dt class="text-sm text-gray-500">Description</dt>
                                    <dd class="font-medium text-gray-900">{{ expense?.description || '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Card 2: Amount Details -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Amount Details</h3>
                        </div>
                        <div class="card-body">
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Currency</dt>
                                    <dd class="font-medium text-gray-900">{{ expense?.currency_code }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Exchange Rate</dt>
                                    <dd class="font-medium text-gray-900">{{ formatNumber(expense?.exchange_rate) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Subtotal</dt>
                                    <dd class="font-medium text-gray-900">{{ formatCurrency(expense?.amount, expense?.currency_code) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Tax</dt>
                                    <dd class="font-medium text-gray-900">{{ formatCurrency(expense?.tax_amount, expense?.currency_code) }}</dd>
                                </div>
                                <div class="flex justify-between border-t pt-3">
                                    <dt class="font-semibold text-gray-900">Total Amount</dt>
                                    <dd class="font-bold text-lg text-primary">{{ formatCurrency(expense?.total_amount, expense?.currency_code) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Amount Paid</dt>
                                    <dd class="font-medium text-success">{{ formatCurrency(expense?.amount_paid, expense?.currency_code) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-semibold text-gray-900">Balance Due</dt>
                                    <dd
                                        class="font-bold"
                                        :class="expense?.balance_due > 0 ? 'text-danger' : 'text-gray-900'"
                                    >
                                        {{ formatCurrency(expense?.balance_due, expense?.currency_code) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Card 3: Payment Information (conditional) -->
                    <div
                        v-if="expense?.payment_method || expense?.paid_from_account"
                        class="card"
                    >
                        <div class="card-header">
                            <h3 class="card-title">Payment Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div v-if="expense?.payment_method">
                                    <dt class="text-sm text-gray-500">Payment Method</dt>
                                    <dd class="font-medium text-gray-900">{{ formatPaymentMethod(expense?.payment_method) }}</dd>
                                </div>
                                <div v-if="expense?.paid_from_account">
                                    <dt class="text-sm text-gray-500">Paid From</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ expense?.paid_from_account?.code }} - {{ expense?.paid_from_account?.name }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Card 4: Recurring Information (conditional) -->
                    <div v-if="expense?.is_recurring" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recurring Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <dt class="text-sm text-gray-500">Recurring</dt>
                                    <dd class="font-medium text-gray-900">
                                        <span class="badge badge-sm badge-success">Yes</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Frequency</dt>
                                    <dd class="font-medium text-gray-900">{{ formatRecurringFrequency(expense?.recurring_frequency) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Card 5: Notes (conditional) -->
                    <div v-if="expense?.notes" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Notes</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-gray-700 whitespace-pre-wrap">{{ expense?.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-5">
                    <!-- Card 6: Status & Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Status & Timeline</h3>
                        </div>
                        <div class="card-body">
                            <!-- Current Status -->
                            <div class="mb-5">
                                <div class="text-sm text-gray-500 mb-2">Current Status</div>
                                <span :class="['badge badge-lg', statusColors[expense?.status]]">
                                    {{ expense?.status_label }}
                                </span>
                            </div>

                            <!-- Payment Status -->
                            <div class="mb-5">
                                <div class="text-sm text-gray-500 mb-2">Payment Status</div>
                                <span :class="['badge badge-lg', paymentStatusColors[expense?.payment_status]]">
                                    {{ expense?.payment_status_label }}
                                </span>
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
                                            <div class="text-xs text-gray-500">{{ formatDateTime(expense?.created_at) }}</div>
                                            <div class="text-xs text-gray-500">by {{ expense?.creator?.name }}</div>
                                        </div>
                                    </div>

                                    <!-- Approved (if applicable) -->
                                    <div v-if="expense?.approved_at && expense?.approver" class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-info/10 flex items-center justify-center">
                                            <i class="ki-filled ki-check-circle text-info text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900">Approved</div>
                                            <div class="text-xs text-gray-500">{{ formatDateTime(expense?.approved_at) }}</div>
                                            <div class="text-xs text-gray-500">by {{ expense?.approver?.name }}</div>
                                        </div>
                                    </div>

                                    <!-- Posted (if applicable) -->
                                    <div v-if="expense?.status === 'posted'" class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                                            <i class="ki-filled ki-send text-success text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900">Posted</div>
                                            <div class="text-xs text-gray-500">Expense posted to general ledger</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Linked Journal Entry (conditional) -->
                    <div v-if="expense?.journal_entry" class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-book text-gray-500 me-2"></i>
                                Linked Journal Entry
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="space-y-3">
                                <!-- Entry Number -->
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-500">Entry Number</dt>
                                    <dd>
                                        <Link
                                            :href="`/finance/journal-entries/${expense.journal_entry.id}`"
                                            class="font-medium text-primary hover:underline"
                                        >
                                            {{ expense.journal_entry.entry_number }}
                                        </Link>
                                    </dd>
                                </div>

                                <!-- Entry Date -->
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-500">Entry Date</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ expense.journal_entry.entry_date_formatted }}
                                    </dd>
                                </div>

                                <!-- Status -->
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-500">Status</dt>
                                    <dd>
                                        <span
                                            :class="[
                                                'badge badge-sm',
                                                expense.journal_entry.status_color === 'success' ? 'badge-success' :
                                                expense.journal_entry.status_color === 'danger' ? 'badge-danger' :
                                                'badge-light'
                                            ]"
                                        >
                                            {{ expense.journal_entry.status_label }}
                                        </span>
                                    </dd>
                                </div>

                                <!-- Total Amount -->
                                <div class="flex justify-between items-center border-t pt-3">
                                    <dt class="text-sm font-medium text-gray-700">Total Amount</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ formatCurrency(expense.journal_entry.total_debit) }}
                                    </dd>
                                </div>
                            </dl>

                            <!-- View Journal Entry Button -->
                            <div class="mt-4 pt-3 border-t">
                                <Link
                                    :href="`/finance/journal-entries/${expense.journal_entry.id}`"
                                    class="btn btn-sm btn-light w-full justify-center"
                                >
                                    <i class="ki-filled ki-eye me-1"></i>
                                    View Journal Entry
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7: Attachments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Attachments</h3>
                        </div>
                        <div class="card-body">
                            <div v-if="expense?.attachments && expense.attachments.length > 0" class="space-y-3">
                                <div
                                    v-for="attachment in expense.attachments"
                                    :key="attachment.id"
                                    class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg"
                                >
                                    <!-- Thumbnail for images -->
                                    <div v-if="attachment.is_image" class="flex-shrink-0">
                                        <a :href="attachment.url" target="_blank" class="block">
                                            <img
                                                :src="attachment.url"
                                                :alt="attachment.file_name"
                                                class="w-12 h-12 object-cover rounded border"
                                            />
                                        </a>
                                    </div>
                                    <!-- Icon for non-images -->
                                    <div v-else class="flex-shrink-0 w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <i :class="[getFileIcon(attachment), 'text-gray-500 text-lg']"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a
                                            :href="attachment.url"
                                            target="_blank"
                                            class="text-sm font-medium text-primary hover:underline truncate block"
                                            :title="attachment.file_name"
                                        >
                                            {{ attachment.file_name }}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ formatFileSize(attachment.file_size) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <i class="ki-outline ki-folder text-gray-300 text-4xl mb-2 block"></i>
                                <p class="text-sm text-gray-500">No attachments</p>
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
