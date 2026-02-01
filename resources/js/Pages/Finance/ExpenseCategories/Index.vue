<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import { KTModal } from '../../../../metronic/core/components/modal';

// Props from controller
const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    accounts: {
        type: Array,
        default: () => []
    },
    parentCategories: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, active: 0, inactive: 0, root: 0 })
    },
});

const page = usePage();

// Permission check helper
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Reactive state
const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || null);
const expandedNodes = ref(new Set());
const isLoading = ref(false);

// Form state for modal
const isEditMode = ref(false);
const editingCategoryId = ref(null);
const form = ref({
    code: '',
    name: '',
    parent_id: null,
    default_account_id: null,
    description: '',
    is_active: true,
});
const formErrors = ref({});

// Filter options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Account options for form dropdown - already formatted by controller
const accountOptions = computed(() => {
    return [
        { value: '', label: 'No Default Account' },
        ...props.accounts
    ];
});

// Parent category options for form dropdown
const parentCategoryOptions = computed(() => {
    const options = [{ value: '', label: 'None (Root Category)' }];

    // Filter out the currently editing category and its children
    const filteredCategories = props.parentCategories.filter(cat => {
        if (!isEditMode.value) return true;
        return cat.id !== editingCategoryId.value;
    });

    filteredCategories.forEach(cat => {
        const indent = '\u00A0\u00A0'.repeat(cat.level || 0);
        options.push({
            value: cat.id,
            label: `${indent}${cat.code} - ${cat.name}`,
        });
    });

    return options;
});

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Tree expand/collapse
const toggleExpand = (categoryId) => {
    if (expandedNodes.value.has(categoryId)) {
        expandedNodes.value.delete(categoryId);
    } else {
        expandedNodes.value.add(categoryId);
    }
};

const isExpanded = (categoryId) => {
    return expandedNodes.value.has(categoryId);
};

const hasChildren = (category) => {
    return category.children && category.children.length > 0;
};

// Expand all nodes
const expandAll = () => {
    const collectIds = (categories) => {
        categories.forEach(category => {
            if (category.children && category.children.length > 0) {
                expandedNodes.value.add(category.id);
                collectIds(category.children);
            }
        });
    };
    collectIds(props.categories || []);
};

const collapseAll = () => {
    expandedNodes.value.clear();
};

// Fetch data with filters
const fetchData = () => {
    router.get('/finance/expense-categories', {
        search: searchQuery.value || undefined,
        status: selectedStatus.value || undefined,
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
    selectedStatus.value = null;
    fetchData();
};

// Reset form
const resetForm = () => {
    form.value = {
        code: '',
        name: '',
        parent_id: null,
        default_account_id: null,
        description: '',
        is_active: true,
    };
    formErrors.value = {};
    isEditMode.value = false;
    editingCategoryId.value = null;
};

// Open create modal - prepare form before modal shows
const prepareCreateModal = () => {
    resetForm();
    isEditMode.value = false;
};

// Open edit modal
const openEditModal = (category) => {
    resetForm();
    isEditMode.value = true;
    editingCategoryId.value = category.id;
    form.value = {
        code: category.code,
        name: category.name,
        parent_id: category.parent_id || '',
        default_account_id: category.default_account_id || '',
        description: category.description || '',
        is_active: category.is_active,
    };
    // Open the modal programmatically
    const modalEl = document.querySelector('#modal_category_form');
    if (modalEl) {
        const modal = KTModal.getInstance(modalEl);
        if (modal) {
            modal.show();
        }
    }
};

// Close modal helper
const closeModal = (modalId) => {
    const modalEl = document.querySelector(`#${modalId}`);
    if (modalEl) {
        const modal = KTModal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }
};

// Submit form (create or update)
const submitForm = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    formErrors.value = {};

    const url = isEditMode.value
        ? `/finance/api/expense-categories/${editingCategoryId.value}`
        : '/finance/api/expense-categories';

    const method = isEditMode.value ? 'put' : 'post';

    try {
        const response = await axios[method](url, {
            ...form.value,
            parent_id: form.value.parent_id || null,
            default_account_id: form.value.default_account_id || null,
        });

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message,
        });

        closeModal('modal_category_form');
        resetForm();
        router.reload({ only: ['categories', 'parentCategories', 'stats'] });
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

// Toggle category status
const toggleStatus = (category) => {
    const action = category.is_active ? 'deactivate' : 'activate';
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Category?`,
        text: `Are you sure you want to ${action} "${category.code} - ${category.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: category.is_active ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Yes, ${action} it!`,
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`/finance/api/expense-categories/${category.id}/toggle-status`)
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
                    router.reload({ only: ['categories', 'stats'] });
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

// Delete category
const deleteCategory = (category) => {
    Swal.fire({
        title: 'Delete Category?',
        text: `Are you sure you want to delete "${category.code} - ${category.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/finance/api/expense-categories/${category.id}`)
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
                    router.reload({ only: ['categories', 'parentCategories', 'stats'] });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        text: error.response?.data?.message || 'Failed to delete category.',
                    });
                });
        }
    });
};

// Calculate indentation style
const getIndentStyle = (level) => {
    return { paddingLeft: `${(level || 0) * 24}px` };
};

// Initialize
onMounted(() => {
    expandAll(); // Expand all nodes by default
    KTModal.init();
});
</script>

<template>
    <AppLayout title="Expense Categories">
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
                <span class="text-gray-900 font-medium">Expense Categories</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Expense Categories</h1>
                    <p class="text-sm text-gray-500">Manage expense category hierarchy</p>
                </div>
                <button
                    v-if="hasPermission('finance.expenses.create')"
                    class="btn btn-primary"
                    data-modal-toggle="#modal_category_form"
                    @click="prepareCreateModal"
                >
                    <i class="ki-filled ki-plus me-2"></i>
                    Add Category
                </button>
            </div>

            <!-- Stats Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Categories -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-category text-gray-600 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.total) }}</span>
                                <p class="text-xs text-gray-500">Total</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Categories -->
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

                <!-- Inactive Categories -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-cross-circle text-danger text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.inactive) }}</span>
                                <p class="text-xs text-gray-500">Inactive</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Root Categories -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-folder text-primary text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.root) }}</span>
                                <p class="text-xs text-gray-500">Root</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <!-- Card Header with Filters -->
                <div class="card-header flex-wrap gap-4">
                    <h3 class="card-title">Category Structure</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                            <input
                                type="text"
                                class="input input-sm pl-9 w-[200px]"
                                placeholder="Search categories..."
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

                        <!-- Status Filter -->
                        <SearchableSelect
                            v-model="selectedStatus"
                            :options="statusOptions"
                            placeholder="All Status"
                            size="sm"
                            class="w-[130px]"
                            @update:model-value="performSearch"
                        />

                        <!-- Clear Filters -->
                        <button
                            v-if="searchQuery || selectedStatus"
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
                    <div v-if="!categories || categories.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-category text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Categories Found</h4>
                        <p class="text-sm text-gray-500 mb-4">Get started by creating your first expense category</p>
                        <button
                            v-if="hasPermission('finance.expenses.create')"
                            class="btn btn-primary"
                            data-modal-toggle="#modal_category_form"
                            @click="prepareCreateModal"
                        >
                            <i class="ki-filled ki-plus me-2"></i>
                            Add Category
                        </button>
                    </div>

                    <!-- Tree Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[350px]">Category</th>
                                    <th class="w-[200px]">Default Account</th>
                                    <th class="w-[100px] text-center">Status</th>
                                    <th class="w-[100px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Recursive Tree Rendering using template recursion -->
                                <template v-for="category in categories" :key="category.id">
                                    <!-- Parent Row -->
                                    <tr class="hover:bg-slate-50">
                                        <td>
                                            <div class="flex items-center gap-2" :style="getIndentStyle(category.level)">
                                                <!-- Expand/Collapse Toggle -->
                                                <button
                                                    v-if="hasChildren(category)"
                                                    @click="toggleExpand(category.id)"
                                                    class="btn btn-xs btn-icon btn-light shrink-0"
                                                >
                                                    <i :class="isExpanded(category.id) ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"></i>
                                                </button>
                                                <span v-else class="w-[26px] shrink-0"></span>

                                                <!-- Category Icon -->
                                                <div
                                                    class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0"
                                                    :class="hasChildren(category) ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600'"
                                                >
                                                    <i :class="hasChildren(category) ? 'ki-filled ki-folder' : 'ki-filled ki-file'"></i>
                                                </div>

                                                <!-- Category Info -->
                                                <div class="flex flex-col min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-mono text-sm font-semibold text-gray-900">
                                                            {{ category.code }}
                                                        </span>
                                                        <span class="text-sm text-gray-700">
                                                            {{ category.name }}
                                                        </span>
                                                    </div>
                                                    <span v-if="category.description" class="text-xs text-gray-400 truncate max-w-[250px]">
                                                        {{ category.description }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="category.default_account" class="text-sm text-gray-600">
                                                {{ category.default_account.account_code }} - {{ category.default_account.account_name }}
                                            </span>
                                            <span v-else class="text-sm text-gray-400">-</span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                v-if="category.is_active"
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
                                                        <!-- Edit Action -->
                                                        <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                            <span
                                                                class="menu-link cursor-pointer"
                                                                @click="openEditModal(category)"
                                                            >
                                                                <span class="menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="menu-title">Edit</span>
                                                            </span>
                                                        </div>

                                                        <!-- Toggle Status Action -->
                                                        <div
                                                            v-if="hasPermission('finance.expenses.edit')"
                                                            class="menu-item"
                                                        >
                                                            <span class="menu-link cursor-pointer" @click="toggleStatus(category)">
                                                                <span class="menu-icon">
                                                                    <i
                                                                        :class="category.is_active
                                                                            ? 'ki-filled ki-cross-circle'
                                                                            : 'ki-filled ki-check-circle'"
                                                                    ></i>
                                                                </span>
                                                                <span class="menu-title">
                                                                    {{ category.is_active ? 'Deactivate' : 'Activate' }}
                                                                </span>
                                                            </span>
                                                        </div>

                                                        <!-- Separator -->
                                                        <div
                                                            v-if="hasPermission('finance.expenses.delete')"
                                                            class="menu-separator"
                                                        ></div>

                                                        <!-- Delete Action -->
                                                        <div
                                                            v-if="hasPermission('finance.expenses.delete')"
                                                            class="menu-item"
                                                        >
                                                            <span class="menu-link cursor-pointer" @click="deleteCategory(category)">
                                                                <span class="menu-icon">
                                                                    <i class="ki-filled ki-trash"></i>
                                                                </span>
                                                                <span class="menu-title text-danger">Delete</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Recursive Children Rows (Level 1) -->
                                    <template v-if="hasChildren(category) && isExpanded(category.id)">
                                        <template v-for="child1 in category.children" :key="child1.id">
                                            <tr class="hover:bg-slate-50">
                                                <td>
                                                    <div class="flex items-center gap-2" :style="getIndentStyle(child1.level)">
                                                        <button
                                                            v-if="hasChildren(child1)"
                                                            @click="toggleExpand(child1.id)"
                                                            class="btn btn-xs btn-icon btn-light shrink-0"
                                                        >
                                                            <i :class="isExpanded(child1.id) ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"></i>
                                                        </button>
                                                        <span v-else class="w-[26px] shrink-0"></span>

                                                        <div
                                                            class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0"
                                                            :class="hasChildren(child1) ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600'"
                                                        >
                                                            <i :class="hasChildren(child1) ? 'ki-filled ki-folder' : 'ki-filled ki-file'"></i>
                                                        </div>

                                                        <div class="flex flex-col min-w-0">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-mono text-sm font-semibold text-gray-900">{{ child1.code }}</span>
                                                                <span class="text-sm text-gray-700">{{ child1.name }}</span>
                                                            </div>
                                                            <span v-if="child1.description" class="text-xs text-gray-400 truncate max-w-[250px]">
                                                                {{ child1.description }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span v-if="child1.default_account" class="text-sm text-gray-600">
                                                        {{ child1.default_account.account_code }} - {{ child1.default_account.account_name }}
                                                    </span>
                                                    <span v-else class="text-sm text-gray-400">-</span>
                                                </td>
                                                <td class="text-center">
                                                    <span v-if="child1.is_active" class="badge badge-sm badge-outline badge-success">Active</span>
                                                    <span v-else class="badge badge-sm badge-outline badge-danger">Inactive</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="menu flex-inline justify-center" data-menu="true">
                                                        <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                                                            <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                                <i class="ki-filled ki-dots-vertical"></i>
                                                            </button>
                                                            <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                                <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                                    <span class="menu-link cursor-pointer" @click="openEditModal(child1)">
                                                                        <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                        <span class="menu-title">Edit</span>
                                                                    </span>
                                                                </div>
                                                                <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                                    <span class="menu-link cursor-pointer" @click="toggleStatus(child1)">
                                                                        <span class="menu-icon">
                                                                            <i :class="child1.is_active ? 'ki-filled ki-cross-circle' : 'ki-filled ki-check-circle'"></i>
                                                                        </span>
                                                                        <span class="menu-title">{{ child1.is_active ? 'Deactivate' : 'Activate' }}</span>
                                                                    </span>
                                                                </div>
                                                                <div v-if="hasPermission('finance.expenses.delete')" class="menu-separator"></div>
                                                                <div v-if="hasPermission('finance.expenses.delete')" class="menu-item">
                                                                    <span class="menu-link cursor-pointer" @click="deleteCategory(child1)">
                                                                        <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                        <span class="menu-title text-danger">Delete</span>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Recursive Children Rows (Level 2) -->
                                            <template v-if="hasChildren(child1) && isExpanded(child1.id)">
                                                <template v-for="child2 in child1.children" :key="child2.id">
                                                    <tr class="hover:bg-slate-50">
                                                        <td>
                                                            <div class="flex items-center gap-2" :style="getIndentStyle(child2.level)">
                                                                <button
                                                                    v-if="hasChildren(child2)"
                                                                    @click="toggleExpand(child2.id)"
                                                                    class="btn btn-xs btn-icon btn-light shrink-0"
                                                                >
                                                                    <i :class="isExpanded(child2.id) ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"></i>
                                                                </button>
                                                                <span v-else class="w-[26px] shrink-0"></span>

                                                                <div
                                                                    class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0"
                                                                    :class="hasChildren(child2) ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600'"
                                                                >
                                                                    <i :class="hasChildren(child2) ? 'ki-filled ki-folder' : 'ki-filled ki-file'"></i>
                                                                </div>

                                                                <div class="flex flex-col min-w-0">
                                                                    <div class="flex items-center gap-2 flex-wrap">
                                                                        <span class="font-mono text-sm font-semibold text-gray-900">{{ child2.code }}</span>
                                                                        <span class="text-sm text-gray-700">{{ child2.name }}</span>
                                                                    </div>
                                                                    <span v-if="child2.description" class="text-xs text-gray-400 truncate max-w-[250px]">
                                                                        {{ child2.description }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span v-if="child2.default_account" class="text-sm text-gray-600">
                                                                {{ child2.default_account.account_code }} - {{ child2.default_account.account_name }}
                                                            </span>
                                                            <span v-else class="text-sm text-gray-400">-</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span v-if="child2.is_active" class="badge badge-sm badge-outline badge-success">Active</span>
                                                            <span v-else class="badge badge-sm badge-outline badge-danger">Inactive</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="menu flex-inline justify-center" data-menu="true">
                                                                <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                                        <i class="ki-filled ki-dots-vertical"></i>
                                                                    </button>
                                                                    <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                                        <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                                            <span class="menu-link cursor-pointer" @click="openEditModal(child2)">
                                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                                <span class="menu-title">Edit</span>
                                                                            </span>
                                                                        </div>
                                                                        <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                                            <span class="menu-link cursor-pointer" @click="toggleStatus(child2)">
                                                                                <span class="menu-icon">
                                                                                    <i :class="child2.is_active ? 'ki-filled ki-cross-circle' : 'ki-filled ki-check-circle'"></i>
                                                                                </span>
                                                                                <span class="menu-title">{{ child2.is_active ? 'Deactivate' : 'Activate' }}</span>
                                                                            </span>
                                                                        </div>
                                                                        <div v-if="hasPermission('finance.expenses.delete')" class="menu-separator"></div>
                                                                        <div v-if="hasPermission('finance.expenses.delete')" class="menu-item">
                                                                            <span class="menu-link cursor-pointer" @click="deleteCategory(child2)">
                                                                                <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                                <span class="menu-title text-danger">Delete</span>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <!-- Level 3 children (if needed) -->
                                                    <template v-if="hasChildren(child2) && isExpanded(child2.id)">
                                                        <tr v-for="child3 in child2.children" :key="child3.id" class="hover:bg-slate-50">
                                                            <td>
                                                                <div class="flex items-center gap-2" :style="getIndentStyle(child3.level)">
                                                                    <span class="w-[26px] shrink-0"></span>
                                                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0 bg-blue-100 text-blue-600">
                                                                        <i class="ki-filled ki-file"></i>
                                                                    </div>
                                                                    <div class="flex flex-col min-w-0">
                                                                        <div class="flex items-center gap-2 flex-wrap">
                                                                            <span class="font-mono text-sm font-semibold text-gray-900">{{ child3.code }}</span>
                                                                            <span class="text-sm text-gray-700">{{ child3.name }}</span>
                                                                        </div>
                                                                        <span v-if="child3.description" class="text-xs text-gray-400 truncate max-w-[250px]">
                                                                            {{ child3.description }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span v-if="child3.default_account" class="text-sm text-gray-600">
                                                                    {{ child3.default_account.account_code }} - {{ child3.default_account.account_name }}
                                                                </span>
                                                                <span v-else class="text-sm text-gray-400">-</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span v-if="child3.is_active" class="badge badge-sm badge-outline badge-success">Active</span>
                                                                <span v-else class="badge badge-sm badge-outline badge-danger">Inactive</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="menu flex-inline justify-center" data-menu="true">
                                                                    <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                                                                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                                            <i class="ki-filled ki-dots-vertical"></i>
                                                                        </button>
                                                                        <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                                            <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                                                <span class="menu-link cursor-pointer" @click="openEditModal(child3)">
                                                                                    <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                                    <span class="menu-title">Edit</span>
                                                                                </span>
                                                                            </div>
                                                                            <div v-if="hasPermission('finance.expenses.edit')" class="menu-item">
                                                                                <span class="menu-link cursor-pointer" @click="toggleStatus(child3)">
                                                                                    <span class="menu-icon">
                                                                                        <i :class="child3.is_active ? 'ki-filled ki-cross-circle' : 'ki-filled ki-check-circle'"></i>
                                                                                    </span>
                                                                                    <span class="menu-title">{{ child3.is_active ? 'Deactivate' : 'Activate' }}</span>
                                                                                </span>
                                                                            </div>
                                                                            <div v-if="hasPermission('finance.expenses.delete')" class="menu-separator"></div>
                                                                            <div v-if="hasPermission('finance.expenses.delete')" class="menu-item">
                                                                                <span class="menu-link cursor-pointer" @click="deleteCategory(child3)">
                                                                                    <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                                    <span class="menu-title text-danger">Delete</span>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </template>
                                            </template>
                                        </template>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Form Modal (Create/Edit) -->
        <div id="modal_category_form" data-modal="true" class="modal">
            <div class="modal-dialog">
                <div class="modal-content max-w-[500px] top-[10%]">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ isEditMode ? 'Edit Category' : 'Create New Category' }}
                        </h5>
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

                            <div class="space-y-4">
                                <!-- Code & Name -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Code <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.code"
                                            placeholder="e.g., EXP001"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.name"
                                            placeholder="e.g., Office Supplies"
                                            required
                                        />
                                    </div>
                                </div>

                                <!-- Parent Category -->
                                <div>
                                    <label class="form-label text-gray-700">Parent Category</label>
                                    <SearchableSelect
                                        v-model="form.parent_id"
                                        :options="parentCategoryOptions"
                                        placeholder="Select parent category (optional)"
                                        :clearable="true"
                                    />
                                    <span class="text-xs text-gray-500 mt-1">
                                        Leave empty for a root category
                                    </span>
                                </div>

                                <!-- Default Account -->
                                <div>
                                    <label class="form-label text-gray-700">Default GL Account</label>
                                    <SearchableSelect
                                        v-model="form.default_account_id"
                                        :options="accountOptions"
                                        placeholder="Select default expense account"
                                        :clearable="true"
                                    />
                                    <span class="text-xs text-gray-500 mt-1">
                                        This account will be pre-selected when creating expenses in this category
                                    </span>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="form-label text-gray-700">Description</label>
                                    <textarea
                                        class="textarea w-full"
                                        v-model="form.description"
                                        placeholder="Optional description for this category"
                                        rows="2"
                                    ></textarea>
                                </div>

                                <!-- Is Active Toggle -->
                                <div class="flex items-center gap-3 pt-2">
                                    <label class="switch switch-sm">
                                        <input
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="order-2"
                                        />
                                        <span class="switch-label order-1">
                                            Active
                                        </span>
                                    </label>
                                    <span class="text-xs text-gray-500">
                                        Inactive categories will not appear in expense forms
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-modal-dismiss="true">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                {{ isLoading ? 'Saving...' : (isEditMode ? 'Update Category' : 'Create Category') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
