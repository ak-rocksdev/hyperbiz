<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AccountTreeRow from './AccountTreeRow.vue';
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    accounts: Array,
    accountTree: Array,
    stats: Object,
    filters: Object,
    accountTypes: Object,
    accountTypeColors: Object,
});

const page = usePage();

// Permission check helper
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Reactive state
const searchQuery = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || null);
const selectedStatus = ref(props.filters?.status || null);
const selectedLevel = ref(props.filters?.level || null);
const expandedNodes = ref(new Set());
const isLoading = ref(false);

// Form state for modal
const isEditMode = ref(false);
const editingAccountId = ref(null);
const form = ref({
    account_code: '',
    account_name: '',
    account_type: '',
    normal_balance: 'debit',
    parent_id: null,
    is_header: false,
    is_bank_account: false,
    description: '',
});
const formErrors = ref({});

// Filter options
const typeOptions = computed(() => [
    { value: '', label: 'All Types' },
    ...Object.entries(props.accountTypes || {}).map(([value, label]) => ({ value, label }))
]);

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const levelOptions = [
    { value: '', label: 'All Levels' },
    { value: '1', label: 'Level 1' },
    { value: '2', label: 'Level 2' },
    { value: '3', label: 'Level 3' },
    { value: '4', label: 'Level 4' },
    { value: '5', label: 'Level 5' },
];

// Account type options for form
const accountTypeOptions = computed(() => {
    return Object.entries(props.accountTypes || {}).map(([value, label]) => ({ value, label }));
});

// Parent account options (only header accounts)
const parentAccountOptions = computed(() => {
    const options = [{ value: '', label: 'None (Top Level)' }];
    const flattenAccounts = (accounts, prefix = '') => {
        accounts.forEach(account => {
            if (account.is_header) {
                options.push({
                    value: account.id,
                    label: `${prefix}${account.account_code} - ${account.account_name}`,
                });
                if (account.children && account.children.length > 0) {
                    flattenAccounts(account.children, prefix + '  ');
                }
            }
        });
    };
    flattenAccounts(props.accountTree || []);
    return options;
});

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Get badge color class for account type
const getTypeColor = (type) => {
    const colors = props.accountTypeColors || {};
    return colors[type] || 'secondary';
};

// Tree expand/collapse
const toggleExpand = (accountId) => {
    if (expandedNodes.value.has(accountId)) {
        expandedNodes.value.delete(accountId);
    } else {
        expandedNodes.value.add(accountId);
    }
};

const isExpanded = (accountId) => {
    return expandedNodes.value.has(accountId);
};

// Expand all nodes
const expandAll = () => {
    const collectIds = (accounts) => {
        accounts.forEach(account => {
            if (account.children && account.children.length > 0) {
                expandedNodes.value.add(account.id);
                collectIds(account.children);
            }
        });
    };
    collectIds(props.accountTree || []);
};

const collapseAll = () => {
    expandedNodes.value.clear();
};

// Fetch data with filters
const fetchData = () => {
    router.get(route('chart-of-accounts.index'), {
        search: searchQuery.value || undefined,
        type: selectedType.value || undefined,
        status: selectedStatus.value || undefined,
        level: selectedLevel.value || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Perform search
const performSearch = () => {
    fetchData();
};

// Clear filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedType.value = null;
    selectedStatus.value = null;
    selectedLevel.value = null;
    fetchData();
};

// Reset form
const resetForm = () => {
    form.value = {
        account_code: '',
        account_name: '',
        account_type: '',
        normal_balance: 'debit',
        parent_id: null,
        is_header: false,
        is_bank_account: false,
        description: '',
    };
    formErrors.value = {};
    isEditMode.value = false;
    editingAccountId.value = null;
};

// Open create modal
const openCreateModal = () => {
    resetForm();
    isEditMode.value = false;
};

// Open edit modal
const openEditModal = (account) => {
    resetForm();
    isEditMode.value = true;
    editingAccountId.value = account.id;
    form.value = {
        account_code: account.account_code,
        account_name: account.account_name,
        account_type: account.account_type,
        normal_balance: account.normal_balance,
        parent_id: account.parent_id || '',
        is_header: account.is_header,
        is_bank_account: account.is_bank_account,
        description: account.description || '',
    };
};

// Submit form (create or update)
const submitForm = () => {
    isLoading.value = true;
    formErrors.value = {};

    const url = isEditMode.value
        ? `/finance/chart-of-accounts/api/update/${editingAccountId.value}`
        : '/finance/chart-of-accounts/api/store';

    const method = isEditMode.value ? 'put' : 'post';

    axios[method](url, {
        ...form.value,
        parent_id: form.value.parent_id || null,
    })
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

        closeModal('modal_account_form');
        resetForm();
        fetchData();
    })
    .catch((error) => {
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
    })
    .finally(() => {
        isLoading.value = false;
    });
};

// Toggle account status
const toggleStatus = (account) => {
    const action = account.is_active ? 'deactivate' : 'activate';
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Account?`,
        text: `Are you sure you want to ${action} "${account.account_code} - ${account.account_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: account.is_active ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Yes, ${action} it!`,
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`/finance/chart-of-accounts/api/toggle-status/${account.id}`)
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
                    fetchData();
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
    if (account.is_system) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Delete',
            text: 'System accounts cannot be deleted.',
        });
        return;
    }

    Swal.fire({
        title: 'Delete Account?',
        text: `Are you sure you want to delete "${account.account_code} - ${account.account_name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/finance/chart-of-accounts/api/delete/${account.id}`)
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
                    fetchData();
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        text: error.response?.data?.message || 'Failed to delete account.',
                    });
                });
        }
    });
};

// Close modal helper
const closeModal = (modalId) => {
    const modalEl = document.querySelector(`#${modalId}`);
    if (modalEl && window.KTModal) {
        const modal = window.KTModal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }
    if (document.querySelector('.modal-backdrop')) {
        document.querySelector('.modal-backdrop').remove();
    }
};

// Initialize
onMounted(() => {
    if (window.KTModal) {
        window.KTModal.init();
    }
    expandAll(); // Expand all nodes by default
});
</script>

<template>
    <AppLayout title="Chart of Accounts">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Chart of Accounts
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link href="/dashboard" class="text-gray-500 hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Chart of Accounts</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Chart of Accounts</h1>
                    <p class="text-sm text-gray-500">Manage your general ledger account structure</p>
                </div>
                <button
                    v-if="hasPermission('chart-of-accounts.create')"
                    class="btn btn-primary"
                    data-modal-toggle="#modal_account_form"
                    @click="openCreateModal"
                >
                    <i class="ki-filled ki-plus-squared me-2"></i>
                    Add Account
                </button>
            </div>

            <!-- Stats Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                <!-- Total Accounts -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-book text-gray-600 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.total) }}</span>
                                <p class="text-xs text-gray-500">Total</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Accounts -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-check-circle text-success text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.active) }}</span>
                                <p class="text-xs text-gray-500">Active</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Postable Accounts -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                                <i class="ki-filled ki-file-added text-info text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.postable) }}</span>
                                <p class="text-xs text-gray-500">Postable</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assets -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-bank text-primary text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.by_type?.asset) }}</span>
                                <p class="text-xs text-gray-500">Assets</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liabilities -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-cheque text-danger text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.by_type?.liability) }}</span>
                                <p class="text-xs text-gray-500">Liabilities</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-dollar text-success text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.by_type?.revenue) }}</span>
                                <p class="text-xs text-gray-500">Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <!-- Card Header with Filters -->
                <div class="card-header flex-wrap gap-4">
                    <h3 class="card-title">Account Structure</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                            <input
                                type="text"
                                class="input input-sm pl-9 w-[200px]"
                                placeholder="Search accounts..."
                                v-model="searchQuery"
                                @keyup.enter="performSearch"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''; performSearch()"
                                class="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <i class="ki-filled ki-cross text-sm"></i>
                            </button>
                        </div>

                        <!-- Type Filter -->
                        <SearchableSelect
                            v-model="selectedType"
                            :options="typeOptions"
                            placeholder="All Types"
                            size="sm"
                            class="w-[150px]"
                            @update:model-value="performSearch"
                        />

                        <!-- Status Filter -->
                        <SearchableSelect
                            v-model="selectedStatus"
                            :options="statusOptions"
                            placeholder="All Status"
                            size="sm"
                            class="w-[130px]"
                            @update:model-value="performSearch"
                        />

                        <!-- Level Filter -->
                        <SearchableSelect
                            v-model="selectedLevel"
                            :options="levelOptions"
                            placeholder="All Levels"
                            size="sm"
                            class="w-[130px]"
                            @update:model-value="performSearch"
                        />

                        <!-- Clear Filters -->
                        <button
                            v-if="searchQuery || selectedType || selectedStatus || selectedLevel"
                            @click="clearFilters"
                            class="btn btn-sm btn-light"
                        >
                            <i class="ki-filled ki-cross me-1"></i>
                            Clear
                        </button>

                        <!-- Expand/Collapse All -->
                        <div class="flex items-center gap-1 border-l border-gray-200 pl-3">
                            <button
                                @click="expandAll"
                                class="btn btn-xs btn-icon btn-light"
                                title="Expand All"
                            >
                                <i class="ki-filled ki-plus"></i>
                            </button>
                            <button
                                @click="collapseAll"
                                class="btn btn-xs btn-icon btn-light"
                                title="Collapse All"
                            >
                                <i class="ki-filled ki-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card Body - Tree View -->
                <div class="card-body">
                    <!-- Empty State -->
                    <div v-if="!accountTree || accountTree.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-book text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Accounts Found</h4>
                        <p class="text-sm text-gray-500 mb-4">Get started by creating your first account</p>
                        <button
                            v-if="hasPermission('chart-of-accounts.create')"
                            class="btn btn-primary"
                            data-modal-toggle="#modal_account_form"
                            @click="openCreateModal"
                        >
                            <i class="ki-filled ki-plus-squared me-2"></i>
                            Add Account
                        </button>
                    </div>

                    <!-- Tree Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[350px]">Account</th>
                                    <th class="w-[120px]">Type</th>
                                    <th class="w-[100px] text-center">Balance</th>
                                    <th class="w-[80px] text-center">Status</th>
                                    <th class="w-[80px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Recursive Tree Rendering -->
                                <template v-for="account in accountTree" :key="account.id">
                                    <AccountTreeRow
                                        :account="account"
                                        :level="0"
                                        :expanded-nodes="expandedNodes"
                                        :account-type-colors="accountTypeColors"
                                        :account-types="accountTypes"
                                        :has-permission="hasPermission"
                                        @toggle-expand="toggleExpand"
                                        @edit="openEditModal"
                                        @toggle-status="toggleStatus"
                                        @delete="deleteAccount"
                                    />
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Form Modal (Create/Edit) -->
        <div id="modal_account_form" data-modal="true" class="modal">
            <div class="modal-content max-w-[550px] top-[10%]">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEditMode ? 'Edit Account' : 'Create New Account' }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body py-5 max-h-[65vh] overflow-y-auto">
                        <!-- Validation Errors -->
                        <div v-if="Object.keys(formErrors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded">
                            <p class="font-bold mb-2">Please fix the following errors:</p>
                            <ul class="list-disc pl-5 text-sm">
                                <li v-for="(messages, field) in formErrors" :key="field">
                                    <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Account Code & Name -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label text-gray-700">
                                    Account Code <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="input w-full"
                                    v-model="form.account_code"
                                    placeholder="e.g., 1000"
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
                                    placeholder="e.g., Cash"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div class="mb-4">
                            <label class="form-label text-gray-700">
                                Account Type <span class="text-danger">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.account_type"
                                :options="accountTypeOptions"
                                placeholder="Select account type"
                                :searchable="false"
                            />
                        </div>

                        <!-- Normal Balance -->
                        <div class="mb-4">
                            <label class="form-label text-gray-700">
                                Normal Balance <span class="text-danger">*</span>
                            </label>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        class="radio"
                                        v-model="form.normal_balance"
                                        value="debit"
                                    />
                                    <span class="text-sm text-gray-700">Debit</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        class="radio"
                                        v-model="form.normal_balance"
                                        value="credit"
                                    />
                                    <span class="text-sm text-gray-700">Credit</span>
                                </label>
                            </div>
                        </div>

                        <!-- Parent Account -->
                        <div class="mb-4">
                            <label class="form-label text-gray-700">Parent Account</label>
                            <SearchableSelect
                                v-model="form.parent_id"
                                :options="parentAccountOptions"
                                placeholder="Select parent account (optional)"
                                :clearable="true"
                            />
                            <span class="text-xs text-gray-500 mt-1">
                                Leave empty for a top-level account
                            </span>
                        </div>

                        <!-- Account Flags -->
                        <div class="mb-4">
                            <label class="form-label text-gray-700 mb-3">Account Properties</label>
                            <div class="flex flex-wrap items-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="checkbox"
                                        v-model="form.is_header"
                                    />
                                    <span class="text-sm text-gray-700">Header Account</span>
                                    <span class="text-xs text-gray-400">(grouping only)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="checkbox"
                                        v-model="form.is_bank_account"
                                    />
                                    <span class="text-sm text-gray-700">Bank Account</span>
                                </label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label text-gray-700">Description</label>
                            <textarea
                                class="textarea w-full"
                                v-model="form.description"
                                placeholder="Optional description for this account"
                                rows="2"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                            {{ isLoading ? 'Saving...' : (isEditMode ? 'Update Account' : 'Create Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
