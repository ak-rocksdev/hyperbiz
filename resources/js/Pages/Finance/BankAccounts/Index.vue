<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    accounts: {
        type: Object,
        default: () => ({ data: [], meta: {} })
    },
    glAccounts: {
        type: Array,
        default: () => []
    },
    totals: {
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
const searchQuery = ref(props.filters?.search || '');
const selectedCurrency = ref(props.filters?.currency || '');
const selectedStatus = ref(props.filters?.is_active ?? '');

// Loading state
const isLoading = ref(false);

// Modal state
const showFormModal = ref(false);
const isEditMode = ref(false);
const editingAccountId = ref(null);

// Form state
const form = ref({
    gl_account_id: null,
    bank_name: '',
    account_name: '',
    account_number: '',
    currency_code: 'IDR',
    swift_code: '',
    branch: '',
    opening_balance: 0,
    opening_balance_date: '',
    notes: '',
});
const formErrors = ref({});

// Currency options
const currencyOptions = [
    { value: 'IDR', label: 'IDR - Indonesian Rupiah' },
    { value: 'USD', label: 'USD - US Dollar' },
    { value: 'SGD', label: 'SGD - Singapore Dollar' },
    { value: 'EUR', label: 'EUR - Euro' },
];

// Currency filter options
const currencyFilterOptions = computed(() => [
    { value: '', label: 'All Currencies' },
    ...currencyOptions.map(c => ({ value: c.value, label: c.value }))
]);

// Status filter options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

// GL Account options for form dropdown
const glAccountOptions = computed(() => {
    return [
        { value: '', label: 'Select GL Account' },
        ...props.glAccounts.map(acc => ({
            value: acc.id,
            label: `${acc.account_code} - ${acc.account_name}`,
        }))
    ];
});

// Format currency helper
const formatCurrency = (amount, currency = 'IDR') => {
    const localeMap = {
        'IDR': 'id-ID',
        'USD': 'en-US',
        'SGD': 'en-SG',
        'EUR': 'de-DE',
    };
    return new Intl.NumberFormat(localeMap[currency] || 'id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
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
    return searchQuery.value || selectedCurrency.value || selectedStatus.value !== '';
});

// Debounce timer
let searchTimeout = null;

// Fetch data with filters
const fetchData = () => {
    const params = {
        search: searchQuery.value || undefined,
        currency: selectedCurrency.value || undefined,
        is_active: selectedStatus.value !== '' ? selectedStatus.value : undefined,
    };

    router.get('/finance/bank-accounts', params, {
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

// Watch filter changes (except search which has its own debounce)
watch([selectedCurrency, selectedStatus], () => {
    fetchData();
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedCurrency.value = '';
    selectedStatus.value = '';
    fetchData();
};

// Pagination helpers
const pagination = computed(() => props.accounts?.meta || props.accounts);

const goToPage = (pageNum) => {
    if (pageNum < 1 || pageNum > pagination.value.last_page) return;

    router.get('/finance/bank-accounts', {
        ...props.filters,
        page: pageNum,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Get accounts list
const accountsList = computed(() => {
    return props.accounts?.data || [];
});

// Reset form
const resetForm = () => {
    form.value = {
        gl_account_id: null,
        bank_name: '',
        account_name: '',
        account_number: '',
        currency_code: 'IDR',
        swift_code: '',
        branch: '',
        opening_balance: 0,
        opening_balance_date: '',
        notes: '',
    };
    formErrors.value = {};
    isEditMode.value = false;
    editingAccountId.value = null;
};

// Open create modal
const openCreateModal = () => {
    resetForm();
    isEditMode.value = false;
    showFormModal.value = true;
    nextTick(() => {
        if (window.KTComponents) {
            window.KTComponents.init();
        }
    });
};

// Open edit modal
const openEditModal = (account) => {
    resetForm();
    isEditMode.value = true;
    editingAccountId.value = account.id;
    form.value = {
        gl_account_id: account.gl_account_id || null,
        bank_name: account.bank_name || '',
        account_name: account.account_name || '',
        account_number: account.account_number || '',
        currency_code: account.currency_code || 'IDR',
        swift_code: account.swift_code || '',
        branch: account.branch || '',
        opening_balance: 0, // Not editable after creation
        opening_balance_date: '', // Not editable after creation
        notes: account.notes || '',
    };
    showFormModal.value = true;
    nextTick(() => {
        if (window.KTComponents) {
            window.KTComponents.init();
        }
    });
};

// Close modal
const closeFormModal = () => {
    showFormModal.value = false;
    resetForm();
};

// Submit form (create or update)
const submitForm = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    formErrors.value = {};

    const url = isEditMode.value
        ? `/finance/api/bank-accounts/${editingAccountId.value}`
        : '/finance/api/bank-accounts';

    const method = isEditMode.value ? 'put' : 'post';

    // Prepare payload
    const payload = {
        gl_account_id: form.value.gl_account_id || null,
        bank_name: form.value.bank_name,
        account_name: form.value.account_name,
        account_number: form.value.account_number,
        currency_code: form.value.currency_code,
        swift_code: form.value.swift_code || null,
        branch: form.value.branch || null,
        notes: form.value.notes || null,
    };

    // Add opening balance fields only for new accounts
    if (!isEditMode.value) {
        payload.opening_balance = parseFloat(form.value.opening_balance) || 0;
        payload.opening_balance_date = form.value.opening_balance_date || null;
    }

    try {
        const response = await axios[method](url, payload);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message,
        });

        closeFormModal();
        router.reload({ only: ['accounts', 'totals'] });
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        }
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'An error occurred',
        });
    } finally {
        isLoading.value = false;
    }
};

// Toggle account status
const toggleStatus = (account) => {
    const action = account.is_active ? 'deactivate' : 'activate';
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Bank Account?`,
        text: `Are you sure you want to ${action} "${account.bank_name} - ${account.account_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: account.is_active ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Yes, ${action} it!`,
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`/finance/api/bank-accounts/${account.id}/toggle-status`)
                .then((response) => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                    });
                    router.reload({ only: ['accounts', 'totals'] });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.response?.data?.message || 'Failed to update status',
                    });
                });
        }
    });
};

// Delete account
const deleteAccount = (account) => {
    Swal.fire({
        title: 'Delete Bank Account?',
        text: `Are you sure you want to delete "${account.bank_name} - ${account.account_name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/finance/api/bank-accounts/${account.id}`)
                .then((response) => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.data.message,
                    });
                    router.reload({ only: ['accounts', 'totals'] });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        text: error.response?.data?.message || 'Failed to delete bank account.',
                    });
                });
        }
    });
};

// Get currency icon/color for summary cards
const getCurrencyStyle = (currency) => {
    const styles = {
        'IDR': { bg: 'bg-primary/10', icon: 'text-primary', symbol: 'Rp' },
        'USD': { bg: 'bg-success/10', icon: 'text-success', symbol: '$' },
        'SGD': { bg: 'bg-info/10', icon: 'text-info', symbol: 'S$' },
        'EUR': { bg: 'bg-warning/10', icon: 'text-warning', symbol: 'E' },
    };
    return styles[currency] || styles['IDR'];
};
</script>

<template>
    <AppLayout title="Bank Accounts">
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
                <span class="text-gray-900 font-medium">Bank Accounts</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Bank Accounts</h1>
                    <p class="text-sm text-gray-500">Manage company bank accounts and balances</p>
                </div>
                <button
                    v-if="hasPermission('finance.bank-accounts.create')"
                    class="btn btn-primary"
                    @click="openCreateModal"
                >
                    <i class="ki-filled ki-plus me-2"></i>
                    Add Account
                </button>
            </div>

            <!-- Summary Cards by Currency -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <template v-if="totals && totals.length > 0">
                    <div v-for="total in totals" :key="total.currency_code" class="card">
                        <div class="card-body p-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-lg flex items-center justify-center"
                                    :class="getCurrencyStyle(total.currency_code).bg"
                                >
                                    <span
                                        class="font-bold text-lg"
                                        :class="getCurrencyStyle(total.currency_code).icon"
                                    >
                                        {{ getCurrencyStyle(total.currency_code).symbol }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-gray-500">{{ total.currency_code }}</span>
                                        <span class="badge badge-xs badge-outline badge-gray">{{ total.account_count }} accounts</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900 block truncate">
                                        {{ formatCurrency(total.total_balance, total.currency_code) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <!-- Empty state for no totals -->
                <div v-else class="col-span-full">
                    <div class="card">
                        <div class="card-body p-4 text-center text-gray-500">
                            <i class="ki-filled ki-bank text-3xl text-gray-300 mb-2"></i>
                            <p class="text-sm">No bank accounts configured yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <!-- Card Header with Filters -->
                <div class="card-header flex-wrap gap-4">
                    <h3 class="card-title">Bank Account List</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                            <input
                                type="text"
                                class="input input-sm pl-9 w-[200px]"
                                placeholder="Search accounts..."
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

                        <!-- Currency Filter -->
                        <SearchableSelect
                            v-model="selectedCurrency"
                            :options="currencyFilterOptions"
                            placeholder="All Currencies"
                            size="sm"
                            class="w-[150px]"
                        />

                        <!-- Status Filter -->
                        <SearchableSelect
                            v-model="selectedStatus"
                            :options="statusOptions"
                            placeholder="All Status"
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

                <!-- Card Body - Table -->
                <div class="card-body">
                    <!-- Empty State -->
                    <div v-if="!accountsList || accountsList.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-bank text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Bank Accounts Found</h4>
                        <p class="text-sm text-gray-500 mb-4">Get started by adding your first bank account</p>
                        <button
                            v-if="hasPermission('finance.bank-accounts.create')"
                            class="btn btn-primary"
                            @click="openCreateModal"
                        >
                            <i class="ki-filled ki-plus me-2"></i>
                            Add Account
                        </button>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[150px]">Bank Name</th>
                                    <th class="min-w-[180px]">Account Name</th>
                                    <th class="w-[150px]">Account Number</th>
                                    <th class="w-[80px] text-center">Currency</th>
                                    <th class="w-[150px] text-end">Current Balance</th>
                                    <th class="w-[130px] text-center">Last Reconciled</th>
                                    <th class="w-[90px] text-center">Status</th>
                                    <th class="w-[80px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in accountsList" :key="account.id" class="hover:bg-slate-50">
                                    <!-- Bank Name -->
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                                <i class="ki-filled ki-bank text-primary"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900">{{ account.bank_name }}</span>
                                                <span v-if="account.branch" class="text-xs text-gray-400">{{ account.branch }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Account Name -->
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-900">{{ account.account_name }}</span>
                                            <span v-if="account.swift_code" class="text-xs text-gray-400">SWIFT: {{ account.swift_code }}</span>
                                        </div>
                                    </td>

                                    <!-- Account Number -->
                                    <td>
                                        <span class="font-mono text-sm text-gray-700">{{ account.account_number }}</span>
                                    </td>

                                    <!-- Currency -->
                                    <td class="text-center">
                                        <span class="badge badge-sm badge-outline badge-primary">
                                            {{ account.currency_code }}
                                        </span>
                                    </td>

                                    <!-- Current Balance -->
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ formatCurrency(account.current_balance, account.currency_code) }}
                                        </span>
                                    </td>

                                    <!-- Last Reconciled -->
                                    <td class="text-center">
                                        <div v-if="account.last_reconciled_date" class="flex flex-col">
                                            <span class="text-sm text-gray-700">{{ formatDate(account.last_reconciled_date) }}</span>
                                            <span class="text-xs text-gray-400">
                                                {{ formatCurrency(account.last_reconciled_balance, account.currency_code) }}
                                            </span>
                                        </div>
                                        <span v-else class="text-sm text-gray-400">Never</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <span
                                            v-if="account.is_active"
                                            class="badge badge-sm badge-outline badge-success"
                                        >
                                            Active
                                        </span>
                                        <span
                                            v-else
                                            class="badge badge-sm badge-outline badge-danger"
                                        >
                                            Inactive
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
                                                <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                    <!-- View Detail Action -->
                                                    <div class="menu-item">
                                                        <Link
                                                            :href="`/finance/bank-accounts/${account.id}`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-eye"></i>
                                                            </span>
                                                            <span class="menu-title">View Detail</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Edit Action -->
                                                    <div v-if="hasPermission('finance.bank-accounts.edit')" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="openEditModal(account)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="menu-title">Edit</span>
                                                        </span>
                                                    </div>

                                                    <!-- Toggle Status Action -->
                                                    <div
                                                        v-if="hasPermission('finance.bank-accounts.edit')"
                                                        class="menu-item"
                                                    >
                                                        <span class="menu-link cursor-pointer" @click="toggleStatus(account)">
                                                            <span class="menu-icon">
                                                                <i
                                                                    :class="account.is_active
                                                                        ? 'ki-filled ki-cross-circle text-warning'
                                                                        : 'ki-filled ki-check-circle text-success'"
                                                                ></i>
                                                            </span>
                                                            <span class="menu-title">
                                                                {{ account.is_active ? 'Deactivate' : 'Activate' }}
                                                            </span>
                                                        </span>
                                                    </div>

                                                    <!-- Separator -->
                                                    <div
                                                        v-if="hasPermission('finance.bank-accounts.delete')"
                                                        class="menu-separator"
                                                    ></div>

                                                    <!-- Delete Action -->
                                                    <div
                                                        v-if="hasPermission('finance.bank-accounts.delete')"
                                                        class="menu-item"
                                                    >
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="deleteAccount(account)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-trash text-danger"></i>
                                                            </span>
                                                            <span class="menu-title text-danger">Delete</span>
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
                    v-if="accountsList && accountsList.length > 0 && pagination?.total > 0"
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

        <!-- Bank Account Form Modal (Create/Edit) -->
        <Teleport to="body">
            <div
                v-if="showFormModal"
                class="fixed inset-0 z-[100] flex items-start justify-center pt-[5%] px-4"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50"
                    @click="closeFormModal"
                ></div>

                <!-- Modal Content -->
                <div class="modal-content max-w-[600px] w-full relative z-10 bg-white rounded-lg shadow-xl">
                    <form @submit.prevent="submitForm">
                        <div class="modal-header flex items-center justify-between p-5 border-b border-gray-200">
                            <h3 class="modal-title text-lg font-semibold text-gray-900">
                                {{ isEditMode ? 'Edit Bank Account' : 'Add New Bank Account' }}
                            </h3>
                            <button
                                type="button"
                                class="btn btn-xs btn-icon btn-light"
                                @click="closeFormModal"
                            >
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>

                        <div class="modal-body p-5 max-h-[70vh] overflow-y-auto">
                            <!-- Validation Errors -->
                            <div v-if="Object.keys(formErrors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded">
                                <p class="font-bold mb-2">Please fix the following errors:</p>
                                <ul class="list-disc pl-5 text-sm">
                                    <li v-for="(messages, field) in formErrors" :key="field">
                                        <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-4">
                                <!-- GL Account -->
                                <div>
                                    <label class="form-label text-gray-700">
                                        GL Account <span class="text-danger">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.gl_account_id"
                                        :options="glAccountOptions"
                                        placeholder="Select GL Account"
                                        :clearable="true"
                                    />
                                    <span class="text-xs text-gray-500 mt-1">
                                        Link this bank account to a General Ledger account
                                    </span>
                                </div>

                                <!-- Bank Name & Account Name -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Bank Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.bank_name"
                                            placeholder="e.g., Bank Central Asia"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Account Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.account_name"
                                            placeholder="e.g., PT Company Name"
                                            required
                                        />
                                    </div>
                                </div>

                                <!-- Account Number & Currency -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Account Number <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.account_number"
                                            placeholder="e.g., 1234567890"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Currency <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.currency_code"
                                            :options="currencyOptions"
                                            placeholder="Select currency"
                                            :searchable="false"
                                        />
                                    </div>
                                </div>

                                <!-- SWIFT Code & Branch -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label text-gray-700">SWIFT Code</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.swift_code"
                                            placeholder="e.g., CENAIDJA"
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">Branch</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.branch"
                                            placeholder="e.g., Jakarta Main Branch"
                                        />
                                    </div>
                                </div>

                                <!-- Opening Balance Fields (only for new accounts) -->
                                <template v-if="!isEditMode">
                                    <div class="border-t border-gray-200 pt-4 mt-4">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Opening Balance</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label text-gray-700">Opening Balance</label>
                                                <input
                                                    type="number"
                                                    class="input w-full"
                                                    v-model="form.opening_balance"
                                                    placeholder="0"
                                                    step="0.01"
                                                />
                                            </div>
                                            <div>
                                                <label class="form-label text-gray-700">Opening Balance Date</label>
                                                <DatePicker
                                                    v-model="form.opening_balance_date"
                                                    placeholder="Select date"
                                                />
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-500 mt-1 block">
                                            Set the initial balance and date when this account was opened in the system
                                        </span>
                                    </div>
                                </template>

                                <!-- Notes -->
                                <div>
                                    <label class="form-label text-gray-700">Notes</label>
                                    <textarea
                                        class="textarea w-full"
                                        v-model="form.notes"
                                        placeholder="Additional notes about this bank account"
                                        rows="2"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                            <button
                                type="button"
                                class="btn btn-light"
                                @click="closeFormModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="isLoading"
                            >
                                <span v-if="isLoading" class="flex items-center gap-2">
                                    <i class="ki-filled ki-loading animate-spin"></i>
                                    Saving...
                                </span>
                                <span v-else>
                                    {{ isEditMode ? 'Update Account' : 'Create Account' }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
