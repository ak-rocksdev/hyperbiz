<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    reconciliations: {
        type: Object,
        default: () => ({ data: [], meta: {} })
    },
    bankAccounts: {
        type: Array,
        default: () => []
    },
    statuses: {
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

// Reactive filter state
const selectedBankAccountId = ref(props.filters?.bank_account_id || '');
const selectedStatus = ref(props.filters?.status || '');

// Loading state
const isLoading = ref(false);

// Dropdown state for "Start Reconciliation" button
const showBankAccountDropdown = ref(false);

// Bank account options for filter dropdown
const bankAccountOptions = computed(() => {
    return [
        { value: '', label: 'All Bank Accounts' },
        ...props.bankAccounts.map(account => ({
            value: account.id,
            label: `${account.bank_name} - ${account.account_name}`,
            sublabel: account.account_number
        }))
    ];
});

// Status options for filter dropdown
const statusOptions = computed(() => {
    const options = [{ value: '', label: 'All Status' }];
    if (props.statuses) {
        props.statuses.forEach(status => {
            options.push({
                value: status.value || status,
                label: status.label || status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
            });
        });
    }
    return options;
});

// Format currency helper
const formatCurrency = (amount, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Format date helper
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Check if filters are active
const hasActiveFilters = computed(() => {
    return selectedBankAccountId.value || selectedStatus.value;
});

// Fetch data with filters
const fetchData = () => {
    const params = {
        bank_account_id: selectedBankAccountId.value || undefined,
        status: selectedStatus.value || undefined,
    };

    router.get('/finance/bank-reconciliations', params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Watch filter changes
watch([selectedBankAccountId, selectedStatus], () => {
    fetchData();
});

// Clear all filters
const clearFilters = () => {
    selectedBankAccountId.value = '';
    selectedStatus.value = '';
    fetchData();
};

// Pagination helpers
const pagination = computed(() => props.reconciliations?.meta || props.reconciliations);

const goToPage = (pageNum) => {
    if (pageNum < 1 || pageNum > pagination.value.last_page) return;

    router.get('/finance/bank-reconciliations', {
        ...props.filters,
        page: pageNum,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Get status badge class
const getStatusBadgeClass = (status) => {
    const statusMap = {
        in_progress: 'badge-warning',
        completed: 'badge-success',
        cancelled: 'badge-danger',
    };
    return statusMap[status] || 'badge-light';
};

// Get status label
const getStatusLabel = (status) => {
    const labelMap = {
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labelMap[status] || status;
};

// Check if difference is not zero
const hasDifference = (difference) => {
    return difference !== 0 && difference !== null && difference !== undefined;
};

// Navigate to reconciliation detail
const viewReconciliation = (reconciliation) => {
    router.visit(`/finance/bank-reconciliations/${reconciliation.id}`);
};

// Navigate to start reconciliation for a bank account
const startReconciliation = (bankAccount) => {
    showBankAccountDropdown.value = false;
    router.visit(`/finance/bank-accounts/${bankAccount.id}/reconcile`);
};

// Cancel reconciliation
const cancelReconciliation = async (reconciliation) => {
    const result = await Swal.fire({
        title: 'Cancel Reconciliation?',
        text: `Are you sure you want to cancel this reconciliation for ${reconciliation.bankAccount?.bank_name || 'this bank account'}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, cancel it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.post(`/finance/api/bank-reconciliations/${reconciliation.id}/cancel`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Cancelled!',
                text: response.data.message || 'Reconciliation cancelled successfully',
            });

            router.reload({ only: ['reconciliations'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to cancel reconciliation',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Get reconciliations data (handle both paginated and direct array)
const reconciliationsList = computed(() => {
    return props.reconciliations?.data || [];
});

// Close dropdown when clicking outside
const closeDropdown = (e) => {
    if (!e.target.closest('.bank-dropdown-container')) {
        showBankAccountDropdown.value = false;
    }
};

// Add event listener for closing dropdown
if (typeof window !== 'undefined') {
    window.addEventListener('click', closeDropdown);
}
</script>

<template>
    <AppLayout title="Bank Reconciliations">
        <!-- Container -->
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <Link href="/dashboard" class="hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Bank Reconciliations</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Bank Reconciliations</h1>
                    <p class="text-sm text-gray-500">View and manage bank reconciliation history</p>
                </div>

                <!-- Start Reconciliation Dropdown Button -->
                <div class="relative bank-dropdown-container">
                    <button
                        v-if="hasPermission('finance.bank_reconciliations.create') && bankAccounts.length > 0"
                        @click.stop="showBankAccountDropdown = !showBankAccountDropdown"
                        class="btn btn-primary"
                    >
                        <i class="ki-filled ki-plus me-2"></i>
                        Start Reconciliation
                        <i class="ki-filled ki-down ms-2 text-xs"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div
                            v-if="showBankAccountDropdown"
                            class="absolute right-0 z-50 mt-2 w-72 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                        >
                            <div class="p-3 border-b border-gray-100 bg-gray-50">
                                <span class="text-sm font-medium text-gray-700">Select Bank Account</span>
                            </div>
                            <div class="max-h-[300px] overflow-y-auto">
                                <div
                                    v-for="account in bankAccounts"
                                    :key="account.id"
                                    @click="startReconciliation(account)"
                                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                            <i class="ki-filled ki-bank text-primary"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-900 truncate">
                                                {{ account.bank_name }}
                                            </div>
                                            <div class="text-xs text-gray-500 truncate">
                                                {{ account.account_name }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{ account.account_number }}
                                            </div>
                                        </div>
                                        <i class="ki-filled ki-arrow-right text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <!-- Card Header with Filters -->
                <div class="card-header flex-wrap gap-4">
                    <h3 class="card-title">Reconciliation History</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Bank Account Filter -->
                        <SearchableSelect
                            v-model="selectedBankAccountId"
                            :options="bankAccountOptions"
                            placeholder="All Bank Accounts"
                            size="sm"
                            class="w-[220px]"
                        />

                        <!-- Status Filter -->
                        <SearchableSelect
                            v-model="selectedStatus"
                            :options="statusOptions"
                            placeholder="All Status"
                            size="sm"
                            class="w-[150px]"
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

                <!-- Card Body - Table -->
                <div class="card-body">
                    <!-- Empty State -->
                    <div v-if="!reconciliationsList || reconciliationsList.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-questionnaire-tablet text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Reconciliations Found</h4>
                        <p class="text-sm text-gray-500 mb-4">Start reconciling your bank accounts to track discrepancies</p>
                        <div v-if="hasPermission('finance.bank_reconciliations.create') && bankAccounts.length > 0" class="relative bank-dropdown-container">
                            <button
                                @click.stop="showBankAccountDropdown = !showBankAccountDropdown"
                                class="btn btn-primary"
                            >
                                <i class="ki-filled ki-plus me-2"></i>
                                Start Reconciliation
                            </button>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[180px]">Bank Account</th>
                                    <th class="w-[120px]">Statement Date</th>
                                    <th class="w-[140px] text-end">Statement Balance</th>
                                    <th class="w-[140px] text-end">Book Balance</th>
                                    <th class="w-[130px] text-end">Difference</th>
                                    <th class="w-[110px] text-center">Status</th>
                                    <th class="min-w-[130px]">Completed By</th>
                                    <th class="w-[80px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="reconciliation in reconciliationsList"
                                    :key="reconciliation.id"
                                    class="hover:bg-slate-50 cursor-pointer"
                                    @click="viewReconciliation(reconciliation)"
                                >
                                    <!-- Bank Account -->
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <i class="ki-filled ki-bank text-primary text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">
                                                    {{ reconciliation.bankAccount?.bank_name || '-' }}
                                                </div>
                                                <div class="text-xs text-gray-500 truncate">
                                                    {{ reconciliation.bankAccount?.account_name || '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Statement Date -->
                                    <td class="text-sm text-gray-600">
                                        {{ formatDate(reconciliation.statement_date) }}
                                    </td>

                                    <!-- Statement Ending Balance -->
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ formatCurrency(reconciliation.statement_ending_balance) }}
                                        </span>
                                    </td>

                                    <!-- Book Balance -->
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ formatCurrency(reconciliation.book_balance) }}
                                        </span>
                                    </td>

                                    <!-- Difference -->
                                    <td class="text-end">
                                        <span
                                            class="font-semibold"
                                            :class="hasDifference(reconciliation.difference) ? 'text-danger' : 'text-success'"
                                        >
                                            {{ formatCurrency(reconciliation.difference) }}
                                        </span>
                                        <span
                                            v-if="hasDifference(reconciliation.difference)"
                                            class="ms-1"
                                            title="Unreconciled difference"
                                        >
                                            <i class="ki-filled ki-information-2 text-danger text-xs"></i>
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center" @click.stop>
                                        <span
                                            class="badge badge-sm"
                                            :class="getStatusBadgeClass(reconciliation.status)"
                                        >
                                            {{ getStatusLabel(reconciliation.status) }}
                                        </span>
                                    </td>

                                    <!-- Completed By -->
                                    <td>
                                        <div v-if="reconciliation.completed_by && reconciliation.completedBy" class="flex flex-col">
                                            <span class="text-sm text-gray-700">
                                                {{ reconciliation.completedBy?.name || '-' }}
                                            </span>
                                            <span v-if="reconciliation.completed_at" class="text-xs text-gray-400">
                                                {{ formatDate(reconciliation.completed_at) }}
                                            </span>
                                        </div>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center" @click.stop>
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
                                                <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                    <!-- View Action (for in_progress or completed) -->
                                                    <div
                                                        v-if="reconciliation.status === 'in_progress' || reconciliation.status === 'completed'"
                                                        class="menu-item"
                                                    >
                                                        <Link
                                                            :href="`/finance/bank-reconciliations/${reconciliation.id}`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-eye"></i>
                                                            </span>
                                                            <span class="menu-title">View</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Continue Reconciling (for in_progress) -->
                                                    <div
                                                        v-if="reconciliation.status === 'in_progress' && hasPermission('finance.bank_reconciliations.edit')"
                                                        class="menu-item"
                                                    >
                                                        <Link
                                                            :href="`/finance/bank-accounts/${reconciliation.bank_account_id}/reconcile`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-pencil text-info"></i>
                                                            </span>
                                                            <span class="menu-title">Continue</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Separator -->
                                                    <div
                                                        v-if="reconciliation.status === 'in_progress' && hasPermission('finance.bank_reconciliations.cancel')"
                                                        class="menu-separator"
                                                    ></div>

                                                    <!-- Cancel Action (for in_progress only) -->
                                                    <div
                                                        v-if="reconciliation.status === 'in_progress' && hasPermission('finance.bank_reconciliations.cancel')"
                                                        class="menu-item"
                                                    >
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="cancelReconciliation(reconciliation)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-cross-circle text-danger"></i>
                                                            </span>
                                                            <span class="menu-title text-danger">Cancel</span>
                                                        </span>
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
                    v-if="reconciliationsList && reconciliationsList.length > 0 && pagination?.total > 0"
                    class="card-footer flex flex-col md:flex-row items-center justify-between gap-4"
                >
                    <div class="text-sm text-gray-500">
                        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} entries
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
    </AppLayout>
</template>
