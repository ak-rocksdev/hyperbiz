<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, reactive } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    reconciliation: {
        type: Object,
        required: true
    },
    summary: {
        type: Object,
        default: () => ({
            statement_date: null,
            statement_ending_balance: 0,
            book_balance: 0,
            last_reconciled_balance: 0,
            last_reconciled_date: null,
            unreconciled: { count: 0, deposits: 0, withdrawals: 0 },
            matched: { count: 0, deposits: 0, withdrawals: 0 },
            cleared_balance: 0,
            difference: 0,
            is_balanced: false,
        })
    },
    unreconciledTransactions: {
        type: Array,
        default: () => []
    },
    matchedTransactions: {
        type: Array,
        default: () => []
    },
});

const page = usePage();

// Permission check helper
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Loading states
const isLoading = ref(false);
const isMatching = ref(false);
const isUnmatching = ref(false);
const isCompleting = ref(false);
const isCancelling = ref(false);
const isCreatingAdjustment = ref(false);

// Local summary state (updated after each action)
const localSummary = ref({ ...props.summary });

// Selected transactions for matching
const selectedTransactionIds = ref([]);

// Select all checkbox state
const selectAll = computed({
    get: () => {
        return props.unreconciledTransactions.length > 0 &&
               selectedTransactionIds.value.length === props.unreconciledTransactions.length;
    },
    set: (value) => {
        if (value) {
            selectedTransactionIds.value = props.unreconciledTransactions.map(t => t.id);
        } else {
            selectedTransactionIds.value = [];
        }
    }
});

// Indeterminate state for select all checkbox
const isIndeterminate = computed(() => {
    return selectedTransactionIds.value.length > 0 &&
           selectedTransactionIds.value.length < props.unreconciledTransactions.length;
});

// Adjustment modal state
const showAdjustmentModal = ref(false);
const adjustmentForm = reactive({
    description: '',
    notes: '',
});
const formErrors = ref({});

// Currency formatting
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
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

// Check if transaction is a deposit (positive amount)
const isDeposit = (transaction) => {
    return transaction.is_deposit || transaction.signed_amount > 0;
};

// Get signed amount display
const getSignedAmount = (transaction) => {
    if (transaction.signed_amount !== undefined) {
        return transaction.signed_amount;
    }
    return isDeposit(transaction) ? transaction.amount : -transaction.amount;
};

// Refresh summary from API
const refreshSummary = async () => {
    try {
        const response = await axios.get(`/finance/api/bank-reconciliations/${props.reconciliation.id}/summary`);
        localSummary.value = response.data;
    } catch (error) {
        console.error('Failed to refresh summary:', error);
    }
};

// Toggle transaction selection
const toggleSelection = (transactionId) => {
    const index = selectedTransactionIds.value.indexOf(transactionId);
    if (index === -1) {
        selectedTransactionIds.value.push(transactionId);
    } else {
        selectedTransactionIds.value.splice(index, 1);
    }
};

// Check if transaction is selected
const isSelected = (transactionId) => {
    return selectedTransactionIds.value.includes(transactionId);
};

// Match selected transactions
const matchSelected = async () => {
    if (selectedTransactionIds.value.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one transaction to match.',
        });
        return;
    }

    isMatching.value = true;
    try {
        const response = await axios.post(
            `/finance/api/bank-reconciliations/${props.reconciliation.id}/match`,
            { transaction_ids: selectedTransactionIds.value }
        );

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            icon: 'success',
            title: 'Matched!',
            text: response.data.message || 'Transactions matched successfully',
        });

        selectedTransactionIds.value = [];
        await refreshSummary();
        router.reload({ only: ['unreconciledTransactions', 'matchedTransactions'] });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to match transactions',
        });
    } finally {
        isMatching.value = false;
    }
};

// Unmatch a single transaction
const unmatchTransaction = async (transactionId) => {
    isUnmatching.value = true;
    try {
        const response = await axios.post(
            `/finance/api/bank-reconciliations/${props.reconciliation.id}/unmatch/${transactionId}`
        );

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            icon: 'success',
            title: 'Unmatched!',
            text: response.data.message || 'Transaction unmatched successfully',
        });

        await refreshSummary();
        router.reload({ only: ['unreconciledTransactions', 'matchedTransactions'] });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to unmatch transaction',
        });
    } finally {
        isUnmatching.value = false;
    }
};

// Open adjustment modal
const openAdjustmentModal = () => {
    adjustmentForm.description = `Reconciliation adjustment - ${formatDate(localSummary.value.statement_date)}`;
    adjustmentForm.notes = '';
    formErrors.value = {};
    showAdjustmentModal.value = true;
};

// Close adjustment modal
const closeAdjustmentModal = () => {
    showAdjustmentModal.value = false;
    formErrors.value = {};
};

// Create adjustment transaction
const createAdjustment = async () => {
    formErrors.value = {};
    isCreatingAdjustment.value = true;

    try {
        const response = await axios.post(
            `/finance/api/bank-reconciliations/${props.reconciliation.id}/adjustment`,
            adjustmentForm
        );

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Created!',
            text: response.data.message || 'Adjustment created successfully',
        });

        closeAdjustmentModal();
        await refreshSummary();
        router.reload({ only: ['unreconciledTransactions', 'matchedTransactions'] });
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to create adjustment',
            });
        }
    } finally {
        isCreatingAdjustment.value = false;
    }
};

// Complete reconciliation
const completeReconciliation = async () => {
    if (!localSummary.value.is_balanced) {
        Swal.fire({
            icon: 'warning',
            title: 'Not Balanced',
            text: 'The reconciliation is not balanced. Please ensure the difference is zero before completing.',
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Complete Reconciliation?',
        html: `
            <p class="mb-3">Are you sure you want to complete this reconciliation?</p>
            <div class="text-left bg-gray-50 p-3 rounded-lg">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Statement Date:</span>
                    <span class="font-medium">${formatDate(localSummary.value.statement_date)}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Ending Balance:</span>
                    <span class="font-medium">${formatCurrency(localSummary.value.statement_ending_balance)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Matched Transactions:</span>
                    <span class="font-medium">${localSummary.value.matched?.count || 0}</span>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#50cd89',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, complete it!',
    });

    if (result.isConfirmed) {
        isCompleting.value = true;
        try {
            const response = await axios.post(
                `/finance/api/bank-reconciliations/${props.reconciliation.id}/complete`
            );

            Swal.fire({
                icon: 'success',
                title: 'Completed!',
                text: response.data.message || 'Reconciliation completed successfully',
            });

            router.visit('/finance/bank-reconciliations');
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to complete reconciliation',
            });
        } finally {
            isCompleting.value = false;
        }
    }
};

// Cancel reconciliation
const cancelReconciliation = async () => {
    const result = await Swal.fire({
        title: 'Cancel Reconciliation?',
        text: 'Are you sure you want to cancel this reconciliation? All matched transactions will be unmatched.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, cancel it!',
    });

    if (result.isConfirmed) {
        isCancelling.value = true;
        try {
            const response = await axios.post(
                `/finance/api/bank-reconciliations/${props.reconciliation.id}/cancel`
            );

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Cancelled!',
                text: response.data.message || 'Reconciliation cancelled successfully',
            });

            router.visit('/finance/bank-reconciliations');
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to cancel reconciliation',
            });
        } finally {
            isCancelling.value = false;
        }
    }
};

// Computed: Total deposits in matched transactions
const matchedDepositsTotal = computed(() => {
    return props.matchedTransactions
        .filter(t => isDeposit(t))
        .reduce((sum, t) => sum + (t.amount || 0), 0);
});

// Computed: Total withdrawals in matched transactions
const matchedWithdrawalsTotal = computed(() => {
    return props.matchedTransactions
        .filter(t => !isDeposit(t))
        .reduce((sum, t) => sum + (t.amount || 0), 0);
});
</script>

<template>
    <AppLayout :title="`Reconcile - ${reconciliation?.bankAccount?.bank_name || 'Bank Account'}`">
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1 text-sm text-gray-500 mb-5">
                <Link href="/" class="hover:text-primary">Home</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance" class="hover:text-primary">Finance</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance/bank-reconciliations" class="hover:text-primary">Bank Reconciliations</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <span class="text-gray-700">Reconcile</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <Link href="/finance/bank-reconciliations" class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ reconciliation?.bankAccount?.bank_name || 'Bank Account' }}
                        </h1>
                        <p class="text-sm text-gray-500">
                            {{ reconciliation?.bankAccount?.account_name }} - Statement Date: {{ formatDate(localSummary.statement_date) }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="cancelReconciliation"
                        class="btn btn-sm btn-light"
                        :disabled="isCancelling"
                    >
                        <i class="ki-filled ki-cross-circle me-1"></i>
                        Cancel
                    </button>
                    <button
                        @click="completeReconciliation"
                        class="btn btn-sm btn-success"
                        :disabled="!localSummary.is_balanced || isCompleting"
                    >
                        <i class="ki-filled ki-check-circle me-1"></i>
                        Complete
                    </button>
                </div>
            </div>

            <!-- Reconciliation Summary Panel (Sticky) -->
            <div class="sticky top-0 z-20 bg-[--tw-page-bg] pb-4">
                <div class="card border-2" :class="localSummary.is_balanced ? 'border-success' : 'border-warning'">
                    <div class="card-body py-4">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 items-center">
                            <!-- Statement Ending Balance (Target) -->
                            <div class="text-center md:text-left">
                                <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Statement Ending Balance</span>
                                <span class="text-lg font-bold text-gray-900">
                                    {{ formatCurrency(localSummary.statement_ending_balance) }}
                                </span>
                                <span class="text-xs text-gray-400 block">Target</span>
                            </div>

                            <!-- Last Reconciled Balance (Starting Point) -->
                            <div class="text-center md:text-left">
                                <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Last Reconciled Balance</span>
                                <span class="text-lg font-bold text-gray-700">
                                    {{ formatCurrency(localSummary.last_reconciled_balance) }}
                                </span>
                                <span class="text-xs text-gray-400 block">Starting Point</span>
                            </div>

                            <!-- Cleared Balance (Calculated) -->
                            <div class="text-center md:text-left">
                                <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Cleared Balance</span>
                                <span class="text-lg font-bold text-info">
                                    {{ formatCurrency(localSummary.cleared_balance) }}
                                </span>
                                <span class="text-xs text-gray-400 block">Calculated</span>
                            </div>

                            <!-- Difference -->
                            <div class="text-center md:text-left">
                                <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Difference</span>
                                <span
                                    class="text-lg font-bold"
                                    :class="localSummary.is_balanced ? 'text-success' : 'text-danger'"
                                >
                                    {{ formatCurrency(localSummary.difference) }}
                                </span>
                                <span
                                    class="text-xs block"
                                    :class="localSummary.is_balanced ? 'text-success' : 'text-danger'"
                                >
                                    {{ localSummary.is_balanced ? 'Balanced' : 'Not Balanced' }}
                                </span>
                            </div>

                            <!-- Status Indicator -->
                            <div class="flex flex-col items-center md:items-end gap-2">
                                <div
                                    class="flex items-center gap-2 px-4 py-2 rounded-lg"
                                    :class="localSummary.is_balanced ? 'bg-success/10' : 'bg-warning/10'"
                                >
                                    <i
                                        class="ki-filled text-xl"
                                        :class="localSummary.is_balanced ? 'ki-check-circle text-success' : 'ki-information-2 text-warning'"
                                    ></i>
                                    <span
                                        class="font-medium"
                                        :class="localSummary.is_balanced ? 'text-success' : 'text-warning'"
                                    >
                                        {{ localSummary.is_balanced ? 'Ready to Complete' : 'Needs Adjustment' }}
                                    </span>
                                </div>
                                <button
                                    v-if="!localSummary.is_balanced"
                                    @click="openAdjustmentModal"
                                    class="btn btn-xs btn-outline btn-primary"
                                >
                                    <i class="ki-filled ki-plus me-1"></i>
                                    Create Adjustment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Left Column: Unreconciled Transactions -->
                <div class="card">
                    <div class="card-header flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <h3 class="card-title">Unreconciled Transactions</h3>
                            <span class="badge badge-sm badge-warning">{{ unreconciledTransactions.length }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="matchSelected"
                                class="btn btn-sm btn-primary"
                                :disabled="selectedTransactionIds.length === 0 || isMatching"
                            >
                                <template v-if="isMatching">
                                    <i class="ki-filled ki-loading animate-spin me-1"></i>
                                    Matching...
                                </template>
                                <template v-else>
                                    <i class="ki-filled ki-arrow-right me-1"></i>
                                    Match Selected ({{ selectedTransactionIds.length }})
                                </template>
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- Empty State -->
                        <div v-if="unreconciledTransactions.length === 0" class="flex flex-col items-center justify-center py-12">
                            <i class="ki-filled ki-check-circle text-5xl text-success mb-3"></i>
                            <h4 class="text-base font-medium text-gray-700 mb-1">All Transactions Matched</h4>
                            <p class="text-sm text-gray-500">No unreconciled transactions remaining</p>
                        </div>

                        <!-- Transaction List -->
                        <div v-else class="max-h-[500px] overflow-y-auto">
                            <!-- Select All Header -->
                            <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50 sticky top-0">
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    v-model="selectAll"
                                    :indeterminate="isIndeterminate"
                                />
                                <span class="text-sm font-medium text-gray-700">Select All</span>
                            </div>

                            <!-- Transaction Items -->
                            <div
                                v-for="transaction in unreconciledTransactions"
                                :key="transaction.id"
                                class="flex items-center gap-3 px-4 py-3 border-b last:border-b-0 hover:bg-gray-50 cursor-pointer"
                                @click="toggleSelection(transaction.id)"
                            >
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    :checked="isSelected(transaction.id)"
                                    @click.stop
                                    @change="toggleSelection(transaction.id)"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-xs text-gray-500">{{ formatDate(transaction.transaction_date) }}</span>
                                        <span class="text-xs text-gray-400 font-mono">{{ transaction.reference || '-' }}</span>
                                    </div>
                                    <div class="text-sm text-gray-700 truncate">{{ transaction.description || 'No description' }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span
                                        class="font-semibold"
                                        :class="isDeposit(transaction) ? 'text-success' : 'text-danger'"
                                    >
                                        {{ isDeposit(transaction) ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unreconciled Summary Footer -->
                    <div v-if="unreconciledTransactions.length > 0" class="card-footer bg-gray-50">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Deposits: <span class="font-medium text-success">{{ formatCurrency(localSummary.unreconciled?.deposits || 0) }}</span>
                            </span>
                            <span class="text-gray-500">
                                Withdrawals: <span class="font-medium text-danger">{{ formatCurrency(localSummary.unreconciled?.withdrawals || 0) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Matched Transactions -->
                <div class="card">
                    <div class="card-header flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <h3 class="card-title">Matched Transactions</h3>
                            <span class="badge badge-sm badge-success">{{ matchedTransactions.length }}</span>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- Empty State -->
                        <div v-if="matchedTransactions.length === 0" class="flex flex-col items-center justify-center py-12">
                            <i class="ki-filled ki-abstract-26 text-5xl text-gray-300 mb-3"></i>
                            <h4 class="text-base font-medium text-gray-700 mb-1">No Matched Transactions</h4>
                            <p class="text-sm text-gray-500">Select transactions from the left and click "Match Selected"</p>
                        </div>

                        <!-- Transaction List -->
                        <div v-else class="max-h-[500px] overflow-y-auto">
                            <div
                                v-for="transaction in matchedTransactions"
                                :key="transaction.id"
                                class="flex items-center gap-3 px-4 py-3 border-b last:border-b-0 hover:bg-gray-50"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-xs text-gray-500">{{ formatDate(transaction.transaction_date) }}</span>
                                        <span class="text-xs text-gray-400 font-mono">{{ transaction.reference || '-' }}</span>
                                    </div>
                                    <div class="text-sm text-gray-700 truncate">{{ transaction.description || 'No description' }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span
                                        class="font-semibold"
                                        :class="isDeposit(transaction) ? 'text-success' : 'text-danger'"
                                    >
                                        {{ isDeposit(transaction) ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                    </span>
                                </div>
                                <button
                                    @click="unmatchTransaction(transaction.id)"
                                    class="btn btn-xs btn-icon btn-light hover:btn-danger"
                                    :disabled="isUnmatching"
                                    title="Unmatch transaction"
                                >
                                    <i class="ki-filled ki-cross text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Matched Summary Footer -->
                    <div v-if="matchedTransactions.length > 0" class="card-footer bg-gray-50">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Total Deposits: <span class="font-medium text-success">{{ formatCurrency(localSummary.matched?.deposits || matchedDepositsTotal) }}</span>
                            </span>
                            <span class="text-gray-500">
                                Total Withdrawals: <span class="font-medium text-danger">{{ formatCurrency(localSummary.matched?.withdrawals || matchedWithdrawalsTotal) }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjustment Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showAdjustmentModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
                    @click.self="closeAdjustmentModal"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="showAdjustmentModal"
                            class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden"
                        >
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-900">Create Adjustment</h3>
                                <button
                                    @click="closeAdjustmentModal"
                                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                                >
                                    <i class="ki-filled ki-cross text-gray-500"></i>
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="px-6 py-4">
                                <!-- Info Alert -->
                                <div class="flex items-start gap-3 p-4 bg-info/10 rounded-lg mb-4">
                                    <i class="ki-filled ki-information-3 text-info text-lg mt-0.5"></i>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            An adjustment transaction will be created for
                                            <span class="font-bold" :class="localSummary.difference < 0 ? 'text-danger' : 'text-success'">
                                                {{ formatCurrency(Math.abs(localSummary.difference)) }}
                                            </span>
                                            to balance the reconciliation.
                                        </p>
                                    </div>
                                </div>

                                <form @submit.prevent="createAdjustment" class="space-y-4">
                                    <!-- Description -->
                                    <div>
                                        <label class="form-label">
                                            Description <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="adjustmentForm.description"
                                            class="input"
                                            placeholder="Enter adjustment description"
                                        />
                                        <span v-if="formErrors.description" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.description[0] }}
                                        </span>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="form-label">Notes</label>
                                        <textarea
                                            v-model="adjustmentForm.notes"
                                            class="textarea"
                                            rows="3"
                                            placeholder="Additional notes..."
                                        ></textarea>
                                        <span v-if="formErrors.notes" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.notes[0] }}
                                        </span>
                                    </div>
                                </form>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                                <button
                                    type="button"
                                    @click="closeAdjustmentModal"
                                    class="btn btn-light"
                                    :disabled="isCreatingAdjustment"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="createAdjustment"
                                    class="btn btn-primary"
                                    :disabled="isCreatingAdjustment"
                                >
                                    <span v-if="isCreatingAdjustment" class="flex items-center gap-2">
                                        <i class="ki-filled ki-loading animate-spin"></i>
                                        Creating...
                                    </span>
                                    <span v-else>
                                        <i class="ki-filled ki-check me-1"></i>
                                        Create Adjustment
                                    </span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* Form label styling */
.form-label {
    @apply block text-sm font-medium text-gray-700 mb-1.5;
}

/* Textarea styling to match Metronic */
.textarea {
    @apply w-full px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400;
    @apply focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20;
    @apply transition-colors;
}

/* Custom checkbox indeterminate style */
input[type="checkbox"]:indeterminate {
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='8' height='2' x='4' y='7' rx='1'/%3e%3c/svg%3e");
    background-color: var(--tw-primary);
    border-color: var(--tw-primary);
}
</style>
