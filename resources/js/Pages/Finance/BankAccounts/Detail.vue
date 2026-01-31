<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { ref, computed, watch, reactive } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    bankAccount: {
        type: Object,
        required: true
    },
    transactions: {
        type: Object,
        default: () => ({ data: [], meta: {} })
    },
    summary: {
        type: Object,
        default: () => ({
            current_balance: 0,
            deposits: 0,
            withdrawals: 0,
            net_change: 0,
            transaction_count: 0,
            unreconciled_count: 0,
            last_reconciled_date: null,
            last_reconciled_balance: 0
        })
    },
    recentReconciliations: {
        type: Array,
        default: () => []
    },
    transactionTypes: {
        type: Array,
        default: () => []
    },
    reconciliationStatuses: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
});

const page = usePage();

// Permission check helper
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Loading state
const isLoading = ref(false);

// Filter state
const searchQuery = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedStatus = ref(props.filters?.status || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

// Modal state
const showTransactionModal = ref(false);
const transactionForm = reactive({
    transaction_date: '',
    transaction_type: '',
    amount: '',
    payee: '',
    reference: '',
    check_number: '',
    description: '',
    notes: '',
});
const formErrors = ref({});
const isSubmitting = ref(false);

// Transaction type options for filter
const transactionTypeOptions = computed(() => {
    return [
        { value: '', label: 'All Types' },
        ...props.transactionTypes.map(type => ({
            value: type.value,
            label: type.label
        }))
    ];
});

// Reconciliation status options for filter
const reconciliationStatusOptions = computed(() => {
    return [
        { value: '', label: 'All Status' },
        ...props.reconciliationStatuses.map(status => ({
            value: status.value,
            label: status.label
        }))
    ];
});

// Transaction type options for form
const formTransactionTypeOptions = computed(() => {
    return props.transactionTypes.map(type => ({
        value: type.value,
        label: type.label
    }));
});

// Currency formatting
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

// Number formatting
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
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

// Get transaction type badge class
const getTypeBadgeClass = (type) => {
    const typeColors = {
        deposit: 'badge-success',
        withdrawal: 'badge-danger',
        transfer_in: 'badge-info',
        transfer_out: 'badge-warning',
        fee: 'badge-dark',
        interest: 'badge-primary',
        adjustment: 'badge-secondary',
    };
    return typeColors[type] || 'badge-light';
};

// Get reconciliation status badge class
const getStatusBadgeClass = (status) => {
    const statusColors = {
        unreconciled: 'badge-outline badge-warning',
        matched: 'badge-outline badge-info',
        reconciled: 'badge-outline badge-success',
        cleared: 'badge-success',
    };
    return statusColors[status] || 'badge-outline badge-gray';
};

// Check if transaction type is credit (deposit)
const isCredit = (type) => {
    return ['deposit', 'transfer_in', 'interest'].includes(type);
};

// Check if active filters exist
const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedType.value || selectedStatus.value ||
           startDate.value || endDate.value;
});

// Debounce timer
let searchTimeout = null;

// Fetch data with filters
const fetchData = () => {
    const params = {
        search: searchQuery.value || undefined,
        type: selectedType.value || undefined,
        status: selectedStatus.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    };

    router.get(`/finance/bank-accounts/${props.bankAccount.id}`, params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Perform search with debounce
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchData();
    }, 400);
};

// Watch filter changes
watch([selectedType, selectedStatus, startDate, endDate], () => {
    fetchData();
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedType.value = '';
    selectedStatus.value = '';
    startDate.value = '';
    endDate.value = '';
    fetchData();
};

// Pagination
const pagination = computed(() => props.transactions?.meta || props.transactions);

const goToPage = (pageNum) => {
    if (pageNum < 1 || pageNum > pagination.value.last_page) return;

    router.get(`/finance/bank-accounts/${props.bankAccount.id}`, {
        ...props.filters,
        page: pageNum,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Get transactions list
const transactionsList = computed(() => {
    return props.transactions?.data || [];
});

// Open add transaction modal
const openTransactionModal = () => {
    // Reset form
    transactionForm.transaction_date = '';
    transactionForm.transaction_type = '';
    transactionForm.amount = '';
    transactionForm.payee = '';
    transactionForm.reference = '';
    transactionForm.check_number = '';
    transactionForm.description = '';
    transactionForm.notes = '';
    formErrors.value = {};
    showTransactionModal.value = true;
};

// Close modal
const closeTransactionModal = () => {
    showTransactionModal.value = false;
    formErrors.value = {};
};

// Submit new transaction
const submitTransaction = async () => {
    formErrors.value = {};
    isSubmitting.value = true;

    try {
        const response = await axios.post(
            `/finance/api/bank-accounts/${props.bankAccount.id}/transactions`,
            transactionForm
        );

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Transaction added successfully',
        });

        closeTransactionModal();
        router.reload({ only: ['transactions', 'summary'] });
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to add transaction',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Delete transaction
const deleteTransaction = async (transaction) => {
    // Only allow deletion of unreconciled transactions
    if (transaction.reconciliation_status !== 'unreconciled') {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Delete',
            text: 'Only unreconciled transactions can be deleted.',
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Delete Transaction?',
        text: `Are you sure you want to delete this transaction? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.delete(
                `/finance/api/bank-accounts/${props.bankAccount.id}/transactions/${transaction.id}`
            );

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Deleted!',
                text: response.data.message || 'Transaction deleted successfully',
            });

            router.reload({ only: ['transactions', 'summary'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to delete transaction',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Format transaction type label
const formatTypeLabel = (type) => {
    const labels = {
        deposit: 'Deposit',
        withdrawal: 'Withdrawal',
        transfer_in: 'Transfer In',
        transfer_out: 'Transfer Out',
        fee: 'Bank Fee',
        interest: 'Interest',
        adjustment: 'Adjustment',
    };
    return labels[type] || type;
};

// Format reconciliation status label
const formatStatusLabel = (status) => {
    const labels = {
        unreconciled: 'Unreconciled',
        matched: 'Matched',
        reconciled: 'Reconciled',
        cleared: 'Cleared',
    };
    return labels[status] || status;
};
</script>

<template>
    <AppLayout :title="bankAccount?.bank_name">
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1 text-sm text-gray-500 mb-5">
                <Link href="/" class="hover:text-primary">Home</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance" class="hover:text-primary">Finance</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <Link href="/finance/bank-accounts" class="hover:text-primary">Bank Accounts</Link>
                <i class="ki-filled ki-right text-xs"></i>
                <span class="text-gray-700">{{ bankAccount?.bank_name }}</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <Link href="/finance/bank-accounts" class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ bankAccount?.bank_name }}</h1>
                        <p class="text-sm text-gray-500">{{ bankAccount?.account_name }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="hasPermission('finance.bank-accounts.edit')"
                        :href="`/finance/bank-accounts/${bankAccount?.id}/edit`"
                        class="btn btn-sm btn-light"
                    >
                        <i class="ki-filled ki-pencil me-1"></i> Edit
                    </Link>
                    <Link
                        v-if="hasPermission('finance.bank-accounts.reconcile')"
                        :href="`/finance/bank-accounts/${bankAccount?.id}/reconcile`"
                        class="btn btn-sm btn-info"
                    >
                        <i class="ki-filled ki-check-square me-1"></i> Reconcile
                    </Link>
                    <button
                        v-if="hasPermission('finance.bank-accounts.transactions.create')"
                        @click="openTransactionModal"
                        class="btn btn-sm btn-primary"
                    >
                        <i class="ki-filled ki-plus me-1"></i> Add Transaction
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Current Balance -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-wallet text-primary text-xl"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-gray-900">
                                    {{ formatCurrency(summary?.current_balance, bankAccount?.currency_code) }}
                                </span>
                                <p class="text-xs text-gray-500">Current Balance</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deposits (Period) -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-arrow-down-left text-success text-xl"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-success">
                                    {{ formatCurrency(summary?.deposits, bankAccount?.currency_code) }}
                                </span>
                                <p class="text-xs text-gray-500">Deposits</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Withdrawals (Period) -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-arrow-up-right text-danger text-xl"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-danger">
                                    {{ formatCurrency(summary?.withdrawals, bankAccount?.currency_code) }}
                                </span>
                                <p class="text-xs text-gray-500">Withdrawals</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unreconciled Count -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-warning/10 flex items-center justify-center">
                                <i class="ki-filled ki-notepad-edit text-warning text-xl"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-gray-900">
                                    {{ formatNumber(summary?.unreconciled_count) }}
                                </span>
                                <p class="text-xs text-gray-500">Unreconciled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
                <!-- Bank Account Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Account Information</h3>
                    </div>
                    <div class="card-body">
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Account Number</dt>
                                <dd class="font-medium text-gray-900 font-mono">
                                    {{ maskAccountNumber(bankAccount?.account_number) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Currency</dt>
                                <dd class="font-medium text-gray-900">{{ bankAccount?.currency_code }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">GL Account</dt>
                                <dd class="font-medium text-gray-900">
                                    <span v-if="bankAccount?.gl_account">
                                        {{ bankAccount.gl_account.code }} - {{ bankAccount.gl_account.name }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Swift Code</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ bankAccount?.swift_code || '-' }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Branch</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ bankAccount?.branch || '-' }}
                                </dd>
                            </div>
                            <div class="border-t pt-4">
                                <dt class="text-sm text-gray-500 mb-1">Last Reconciled</dt>
                                <dd class="font-medium text-gray-900">
                                    <span v-if="summary?.last_reconciled_date">
                                        {{ formatDate(summary.last_reconciled_date) }}
                                        <span class="text-sm text-gray-500 block">
                                            Balance: {{ formatCurrency(summary.last_reconciled_balance, bankAccount?.currency_code) }}
                                        </span>
                                    </span>
                                    <span v-else class="text-gray-400">Never reconciled</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Transactions Card (spans 2 columns) -->
                <div class="lg:col-span-2 card">
                    <div class="card-header flex-wrap gap-4">
                        <h3 class="card-title">Transactions</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Search Input -->
                            <div class="relative">
                                <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                                <input
                                    type="text"
                                    class="input input-sm pl-9 w-[180px]"
                                    placeholder="Search..."
                                    v-model="searchQuery"
                                    @input="performSearch"
                                    @keyup.enter="fetchData"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="searchQuery = ''; fetchData()"
                                    class="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <i class="ki-filled ki-cross text-sm"></i>
                                </button>
                            </div>

                            <!-- Type Filter -->
                            <SearchableSelect
                                v-model="selectedType"
                                :options="transactionTypeOptions"
                                placeholder="All Types"
                                size="sm"
                                class="w-[130px]"
                            />

                            <!-- Status Filter -->
                            <SearchableSelect
                                v-model="selectedStatus"
                                :options="reconciliationStatusOptions"
                                placeholder="All Status"
                                size="sm"
                                class="w-[130px]"
                            />

                            <!-- Date Range -->
                            <DatePicker
                                v-model="startDate"
                                placeholder="Start Date"
                                size="sm"
                                class="w-[130px]"
                            />

                            <DatePicker
                                v-model="endDate"
                                placeholder="End Date"
                                size="sm"
                                class="w-[130px]"
                            />

                            <!-- Clear Filters -->
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="btn btn-sm btn-light"
                            >
                                <i class="ki-filled ki-cross me-1"></i>
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- Empty State -->
                        <div v-if="!transactionsList || transactionsList.length === 0" class="flex flex-col items-center justify-center py-16">
                            <i class="ki-filled ki-notepad text-6xl text-gray-300 mb-4"></i>
                            <h4 class="text-lg font-medium text-gray-700 mb-2">No Transactions Found</h4>
                            <p class="text-sm text-gray-500 mb-4">Add your first transaction to get started</p>
                            <button
                                v-if="hasPermission('finance.bank-accounts.transactions.create')"
                                @click="openTransactionModal"
                                class="btn btn-primary"
                            >
                                <i class="ki-filled ki-plus me-2"></i>
                                Add Transaction
                            </button>
                        </div>

                        <!-- Transactions Table -->
                        <div v-else class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[100px]">Date</th>
                                        <th class="w-[120px]">Reference</th>
                                        <th class="min-w-[200px]">Description</th>
                                        <th class="w-[110px]">Type</th>
                                        <th class="w-[120px] text-end">Debit</th>
                                        <th class="w-[120px] text-end">Credit</th>
                                        <th class="w-[130px] text-end">Running Balance</th>
                                        <th class="w-[110px] text-center">Status</th>
                                        <th class="w-[60px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="transaction in transactionsList" :key="transaction.id" class="hover:bg-slate-50">
                                        <!-- Date -->
                                        <td class="text-sm text-gray-600">
                                            {{ formatDate(transaction.transaction_date) }}
                                        </td>

                                        <!-- Reference -->
                                        <td>
                                            <span class="text-sm font-medium text-gray-900">
                                                {{ transaction.reference || '-' }}
                                            </span>
                                            <span v-if="transaction.check_number" class="text-xs text-gray-400 block">
                                                Check #{{ transaction.check_number }}
                                            </span>
                                        </td>

                                        <!-- Description -->
                                        <td>
                                            <div class="text-sm text-gray-700">{{ transaction.description || '-' }}</div>
                                            <div v-if="transaction.payee" class="text-xs text-gray-400">
                                                Payee: {{ transaction.payee }}
                                            </div>
                                        </td>

                                        <!-- Type -->
                                        <td>
                                            <span
                                                class="badge badge-sm"
                                                :class="getTypeBadgeClass(transaction.transaction_type)"
                                            >
                                                {{ formatTypeLabel(transaction.transaction_type) }}
                                            </span>
                                        </td>

                                        <!-- Debit (Withdrawals) -->
                                        <td class="text-end">
                                            <span
                                                v-if="!isCredit(transaction.transaction_type)"
                                                class="font-medium text-danger"
                                            >
                                                {{ formatCurrency(transaction.amount, bankAccount?.currency_code) }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>

                                        <!-- Credit (Deposits) -->
                                        <td class="text-end">
                                            <span
                                                v-if="isCredit(transaction.transaction_type)"
                                                class="font-medium text-success"
                                            >
                                                {{ formatCurrency(transaction.amount, bankAccount?.currency_code) }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>

                                        <!-- Running Balance -->
                                        <td class="text-end">
                                            <span class="font-semibold text-gray-900">
                                                {{ formatCurrency(transaction.running_balance, bankAccount?.currency_code) }}
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td class="text-center">
                                            <span
                                                class="badge badge-sm"
                                                :class="getStatusBadgeClass(transaction.reconciliation_status)"
                                            >
                                                {{ formatStatusLabel(transaction.reconciliation_status) }}
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-center">
                                            <div class="menu flex-inline justify-center" data-menu="true">
                                                <div
                                                    class="menu-item"
                                                    data-menu-item-offset="0, 10px"
                                                    data-menu-item-placement="bottom-end"
                                                    data-menu-item-toggle="dropdown"
                                                    data-menu-item-trigger="click|lg:click"
                                                >
                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                        <i class="ki-filled ki-dots-vertical"></i>
                                                    </button>
                                                    <div class="menu-dropdown menu-default w-full max-w-[150px]" data-menu-dismiss="true">
                                                        <!-- View Notes -->
                                                        <div v-if="transaction.notes" class="menu-item">
                                                            <span
                                                                class="menu-link cursor-pointer"
                                                                @click="Swal.fire({ title: 'Notes', text: transaction.notes, icon: 'info' })"
                                                            >
                                                                <span class="menu-icon">
                                                                    <i class="ki-filled ki-notepad"></i>
                                                                </span>
                                                                <span class="menu-title">View Notes</span>
                                                            </span>
                                                        </div>

                                                        <!-- Delete Action (only for unreconciled) -->
                                                        <div
                                                            v-if="transaction.reconciliation_status === 'unreconciled' && hasPermission('finance.bank-accounts.transactions.delete')"
                                                            class="menu-item"
                                                        >
                                                            <span
                                                                class="menu-link cursor-pointer"
                                                                @click="deleteTransaction(transaction)"
                                                            >
                                                                <span class="menu-icon">
                                                                    <i class="ki-filled ki-trash text-danger"></i>
                                                                </span>
                                                                <span class="menu-title text-danger">Delete</span>
                                                            </span>
                                                        </div>

                                                        <!-- No actions message -->
                                                        <div
                                                            v-if="transaction.reconciliation_status !== 'unreconciled' && !transaction.notes"
                                                            class="px-4 py-2 text-xs text-gray-400"
                                                        >
                                                            No actions available
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="transactionsList && transactionsList.length > 0 && pagination?.total > 0"
                        class="card-footer flex flex-col md:flex-row items-center justify-between gap-4"
                    >
                        <div class="text-sm text-gray-500">
                            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} transactions
                        </div>
                        <div class="flex items-center gap-1">
                            <!-- Previous Button -->
                            <button
                                @click="goToPage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="btn btn-sm btn-icon btn-light"
                                :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page === 1 }"
                            >
                                <i class="ki-filled ki-arrow-left"></i>
                            </button>

                            <!-- Page Numbers -->
                            <template v-for="pageNum in pagination.last_page" :key="pageNum">
                                <button
                                    v-if="pageNum === 1 || pageNum === pagination.last_page || (pageNum >= pagination.current_page - 1 && pageNum <= pagination.current_page + 1)"
                                    @click="goToPage(pageNum)"
                                    class="btn btn-sm"
                                    :class="pageNum === pagination.current_page ? 'btn-primary' : 'btn-light'"
                                >
                                    {{ pageNum }}
                                </button>
                                <span
                                    v-else-if="pageNum === 2 || pageNum === pagination.last_page - 1"
                                    class="px-2 text-gray-400"
                                >...</span>
                            </template>

                            <!-- Next Button -->
                            <button
                                @click="goToPage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="btn btn-sm btn-icon btn-light"
                                :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page === pagination.last_page }"
                            >
                                <i class="ki-filled ki-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Transaction Modal -->
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
                    v-if="showTransactionModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
                    @click.self="closeTransactionModal"
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
                            v-if="showTransactionModal"
                            class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden"
                        >
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-900">Add Transaction</h3>
                                <button
                                    @click="closeTransactionModal"
                                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                                >
                                    <i class="ki-filled ki-cross text-gray-500"></i>
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="px-6 py-4 overflow-y-auto max-h-[calc(90vh-140px)]">
                                <form @submit.prevent="submitTransaction" class="space-y-4">
                                    <!-- Transaction Date -->
                                    <div>
                                        <label class="form-label">
                                            Transaction Date <span class="text-danger">*</span>
                                        </label>
                                        <DatePicker
                                            v-model="transactionForm.transaction_date"
                                            placeholder="Select date"
                                        />
                                        <span v-if="formErrors.transaction_date" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.transaction_date[0] }}
                                        </span>
                                    </div>

                                    <!-- Transaction Type -->
                                    <div>
                                        <label class="form-label">
                                            Transaction Type <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="transactionForm.transaction_type"
                                            :options="formTransactionTypeOptions"
                                            placeholder="Select type"
                                        />
                                        <span v-if="formErrors.transaction_type" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.transaction_type[0] }}
                                        </span>
                                    </div>

                                    <!-- Amount -->
                                    <div>
                                        <label class="form-label">
                                            Amount <span class="text-danger">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                                                {{ bankAccount?.currency_code }}
                                            </span>
                                            <input
                                                type="number"
                                                v-model="transactionForm.amount"
                                                class="input pl-14"
                                                placeholder="0.00"
                                                step="0.01"
                                                min="0"
                                            />
                                        </div>
                                        <span v-if="formErrors.amount" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.amount[0] }}
                                        </span>
                                    </div>

                                    <!-- Payee -->
                                    <div>
                                        <label class="form-label">Payee</label>
                                        <input
                                            type="text"
                                            v-model="transactionForm.payee"
                                            class="input"
                                            placeholder="Enter payee name"
                                        />
                                        <span v-if="formErrors.payee" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.payee[0] }}
                                        </span>
                                    </div>

                                    <!-- Reference & Check Number (side by side) -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Reference</label>
                                            <input
                                                type="text"
                                                v-model="transactionForm.reference"
                                                class="input"
                                                placeholder="Reference #"
                                            />
                                            <span v-if="formErrors.reference" class="text-xs text-danger mt-1 block">
                                                {{ formErrors.reference[0] }}
                                            </span>
                                        </div>
                                        <div>
                                            <label class="form-label">Check Number</label>
                                            <input
                                                type="text"
                                                v-model="transactionForm.check_number"
                                                class="input"
                                                placeholder="Check #"
                                            />
                                            <span v-if="formErrors.check_number" class="text-xs text-danger mt-1 block">
                                                {{ formErrors.check_number[0] }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label class="form-label">Description</label>
                                        <input
                                            type="text"
                                            v-model="transactionForm.description"
                                            class="input"
                                            placeholder="Enter description"
                                        />
                                        <span v-if="formErrors.description" class="text-xs text-danger mt-1 block">
                                            {{ formErrors.description[0] }}
                                        </span>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="form-label">Notes</label>
                                        <textarea
                                            v-model="transactionForm.notes"
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
                                    @click="closeTransactionModal"
                                    class="btn btn-light"
                                    :disabled="isSubmitting"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="submitTransaction"
                                    class="btn btn-primary"
                                    :disabled="isSubmitting"
                                >
                                    <span v-if="isSubmitting" class="flex items-center gap-2">
                                        <i class="ki-filled ki-loading animate-spin"></i>
                                        Saving...
                                    </span>
                                    <span v-else>
                                        <i class="ki-filled ki-check me-1"></i>
                                        Save Transaction
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
</style>
