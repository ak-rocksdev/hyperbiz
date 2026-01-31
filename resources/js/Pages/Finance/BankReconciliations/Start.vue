<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    bankAccount: {
        type: Object,
        required: true
    },
    lastReconciliation: {
        type: Object,
        default: null
    }
});

// Form state - statement date defaults to today
const form = ref({
    statement_date: new Date().toISOString().split('T')[0],
    statement_ending_balance: '',
});

const errors = ref({});
const isSubmitting = ref(false);

// Currency formatting
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency || 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

// Format date
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Mask account number (show last 4 digits)
const maskAccountNumber = (accountNumber) => {
    if (!accountNumber) return '-';
    const lastFour = accountNumber.slice(-4);
    const masked = '*'.repeat(Math.max(0, accountNumber.length - 4));
    return masked + lastFour;
};

// Computed: formatted statement ending balance for display
const formattedStatementBalance = computed(() => {
    const value = parseFloat(form.value.statement_ending_balance) || 0;
    return formatCurrency(value, props.bankAccount?.currency_code);
});

// Validate form
const validateForm = () => {
    errors.value = {};

    if (!form.value.statement_date) {
        errors.value.statement_date = ['Statement date is required'];
    }

    if (form.value.statement_ending_balance === '' || form.value.statement_ending_balance === null) {
        errors.value.statement_ending_balance = ['Statement ending balance is required'];
    }

    return Object.keys(errors.value).length === 0;
};

// Submit form to start reconciliation
const submitForm = async () => {
    if (!validateForm()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
        });
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await axios.post(
            `/finance/api/bank-accounts/${props.bankAccount.id}/reconcile/start`,
            {
                statement_date: form.value.statement_date,
                statement_ending_balance: parseFloat(form.value.statement_ending_balance),
            }
        );

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Reconciliation session started',
        });

        // Redirect to the reconciliation detail page
        if (response.data.reconciliation?.id) {
            router.visit(`/finance/bank-reconciliations/${response.data.reconciliation.id}`);
        } else {
            router.visit('/finance/bank-reconciliations');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to start reconciliation',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Go back to bank account detail
const goBack = () => {
    router.visit(`/finance/bank-accounts/${props.bankAccount.id}`);
};
</script>

<template>
    <AppLayout title="Start Bank Reconciliation">
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <Link href="/dashboard" class="hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <Link href="/finance/bank-accounts" class="hover:text-primary">Bank Accounts</Link>
                <span class="text-gray-400">/</span>
                <Link :href="`/finance/bank-accounts/${bankAccount?.id}`" class="hover:text-primary">
                    {{ bankAccount?.bank_name }}
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Start Reconciliation</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <button
                        @click="goBack"
                        class="btn btn-icon btn-light btn-sm"
                    >
                        <i class="ki-filled ki-arrow-left"></i>
                    </button>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Start Bank Reconciliation</h1>
                        <p class="text-sm text-gray-500">Begin a new reconciliation session for {{ bankAccount?.bank_name }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Bank Account Info & Last Reconciliation -->
                <div class="lg:col-span-1 space-y-5">
                    <!-- Bank Account Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-bank me-2 text-gray-500"></i>
                                Bank Account
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <i class="ki-filled ki-bank text-primary text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ bankAccount?.bank_name }}</h4>
                                    <p class="text-sm text-gray-500">{{ bankAccount?.account_name }}</p>
                                </div>
                            </div>

                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Account Number</dt>
                                    <dd class="font-medium text-gray-900 font-mono">
                                        {{ maskAccountNumber(bankAccount?.account_number) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Currency</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{ bankAccount?.currency_code || 'IDR' }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">GL Account</dt>
                                    <dd class="font-medium text-gray-900 text-right">
                                        <span v-if="bankAccount?.gl_account" class="text-sm">
                                            {{ bankAccount.gl_account.code }}
                                            <span class="block text-xs text-gray-500">{{ bankAccount.gl_account.name }}</span>
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </dd>
                                </div>
                                <div class="border-t pt-3 flex justify-between items-center">
                                    <dt class="text-sm text-gray-500">Current Balance</dt>
                                    <dd class="font-bold text-lg text-primary">
                                        {{ formatCurrency(bankAccount?.current_balance, bankAccount?.currency_code) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Last Reconciliation Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-calendar-tick me-2 text-gray-500"></i>
                                Last Reconciliation
                            </h3>
                        </div>
                        <div class="card-body">
                            <div v-if="lastReconciliation">
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Statement Date</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ formatDate(lastReconciliation.statement_date) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Statement Balance</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ formatCurrency(lastReconciliation.statement_ending_balance, bankAccount?.currency_code) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Reconciled Balance</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ formatCurrency(lastReconciliation.reconciled_balance, bankAccount?.currency_code) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Completed</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ formatDate(lastReconciliation.completed_at) }}
                                        </dd>
                                    </div>
                                </dl>

                                <!-- Status Indicator -->
                                <div class="mt-4 p-3 bg-success/10 rounded-lg flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-success/20 flex items-center justify-center">
                                        <i class="ki-filled ki-check text-success"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-success">Reconciled</p>
                                        <p class="text-xs text-gray-500">Last reconciled on {{ formatDate(lastReconciliation.completed_at) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- No Previous Reconciliation -->
                            <div v-else class="text-center py-6">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <i class="ki-filled ki-information-3 text-gray-400 text-2xl"></i>
                                </div>
                                <h4 class="text-sm font-medium text-gray-700 mb-1">No Previous Reconciliation</h4>
                                <p class="text-xs text-gray-500">
                                    This will be the first reconciliation for this bank account.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Start Reconciliation Form -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-notepad-edit me-2 text-gray-500"></i>
                                Reconciliation Details
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Help Text / Instructions -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <i class="ki-filled ki-information-2 text-blue-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-blue-800 mb-1">How to Reconcile</h4>
                                        <ul class="text-sm text-blue-700 space-y-1">
                                            <li class="flex items-start gap-2">
                                                <span class="text-blue-400 mt-1">1.</span>
                                                <span>Enter the <strong>Statement Date</strong> from your bank statement (the date the statement was issued).</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-blue-400 mt-1">2.</span>
                                                <span>Enter the <strong>Statement Ending Balance</strong> - the closing balance shown on your bank statement.</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-blue-400 mt-1">3.</span>
                                                <span>Click "Start Reconciliation" to begin matching transactions.</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Validation Errors Summary -->
                            <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-6 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="ki-filled ki-information-2 text-red-500"></i>
                                    <p class="font-semibold">Please fix the following errors:</p>
                                </div>
                                <ul class="list-disc pl-5 text-sm">
                                    <li v-for="(messages, field) in errors" :key="field">
                                        <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="submitForm" class="space-y-6">
                                <!-- Statement Date -->
                                <div>
                                    <label class="form-label text-gray-700">
                                        Statement Date <span class="text-danger">*</span>
                                    </label>
                                    <DatePicker
                                        v-model="form.statement_date"
                                        placeholder="Select statement date"
                                    />
                                    <p class="text-xs text-gray-500 mt-1.5">
                                        The date shown on your bank statement (usually the statement closing date).
                                    </p>
                                    <span v-if="errors.statement_date" class="text-xs text-danger mt-1 block">
                                        {{ errors.statement_date[0] }}
                                    </span>
                                </div>

                                <!-- Statement Ending Balance -->
                                <div>
                                    <label class="form-label text-gray-700">
                                        Statement Ending Balance <span class="text-danger">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">
                                            {{ bankAccount?.currency_code || 'IDR' }}
                                        </span>
                                        <input
                                            type="number"
                                            v-model="form.statement_ending_balance"
                                            class="input w-full pl-14"
                                            placeholder="0.00"
                                            step="0.01"
                                        />
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1.5">
                                        The ending/closing balance shown on your bank statement. This is the amount the bank says you have.
                                    </p>
                                    <span v-if="errors.statement_ending_balance" class="text-xs text-danger mt-1 block">
                                        {{ errors.statement_ending_balance[0] }}
                                    </span>
                                </div>

                                <!-- Summary Preview -->
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Reconciliation Summary</h4>
                                    <dl class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <dt class="text-gray-500">Bank Account</dt>
                                            <dd class="font-medium text-gray-900">{{ bankAccount?.bank_name }}</dd>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <dt class="text-gray-500">Statement Date</dt>
                                            <dd class="font-medium text-gray-900">
                                                {{ form.statement_date ? formatDate(form.statement_date) : '-' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <dt class="text-gray-500">Statement Ending Balance</dt>
                                            <dd class="font-medium text-gray-900">
                                                {{ form.statement_ending_balance ? formattedStatementBalance : '-' }}
                                            </dd>
                                        </div>
                                        <div class="border-t pt-2 flex justify-between text-sm">
                                            <dt class="text-gray-500">Current Book Balance</dt>
                                            <dd class="font-bold text-primary">
                                                {{ formatCurrency(bankAccount?.current_balance, bankAccount?.currency_code) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                                    <button
                                        type="button"
                                        @click="goBack"
                                        class="btn btn-light"
                                        :disabled="isSubmitting"
                                    >
                                        <i class="ki-filled ki-cross me-2"></i>
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="isSubmitting"
                                    >
                                        <span v-if="isSubmitting" class="flex items-center gap-2">
                                            <i class="ki-filled ki-loading animate-spin"></i>
                                            Starting...
                                        </span>
                                        <span v-else class="flex items-center gap-2">
                                            <i class="ki-filled ki-check-square"></i>
                                            Start Reconciliation
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Additional Tips Card -->
                    <div class="card mt-5">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-bulb me-2 text-warning"></i>
                                Tips for Bank Reconciliation
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-document text-primary text-sm"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-medium text-gray-900">Have your statement ready</h5>
                                        <p class="text-xs text-gray-500">Keep your bank statement handy to verify transactions during reconciliation.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-check-circle text-success text-sm"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-medium text-gray-900">Match cleared transactions</h5>
                                        <p class="text-xs text-gray-500">Mark transactions that have cleared the bank as reconciled.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-time text-warning text-sm"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-medium text-gray-900">Account for timing differences</h5>
                                        <p class="text-xs text-gray-500">Outstanding checks and deposits in transit cause temporary differences.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-magnifier text-info text-sm"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-medium text-gray-900">Investigate discrepancies</h5>
                                        <p class="text-xs text-gray-500">Look for bank fees, errors, or unrecorded transactions if balances do not match.</p>
                                    </div>
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
/* Form label styling consistent with other pages */
.form-label {
    @apply block text-sm font-medium text-gray-700 mb-1.5;
}
</style>
