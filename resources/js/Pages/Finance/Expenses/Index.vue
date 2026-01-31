<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    expenses: {
        type: Object,
        default: () => ({ data: [], meta: {} })
    },
    categories: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            draft: 0,
            approved: 0,
            posted: 0,
            total_amount: 0,
            unpaid_amount: 0
        })
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
const selectedStatus = ref(props.filters?.status || '');
const selectedPaymentStatus = ref(props.filters?.payment_status || '');
const selectedCategoryId = ref(props.filters?.category_id || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

// Loading state
const isLoading = ref(false);

// Filter options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'posted', label: 'Posted' },
    { value: 'cancelled', label: 'Cancelled' },
];

const paymentStatusOptions = [
    { value: '', label: 'All Payment' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partial', label: 'Partial' },
    { value: 'paid', label: 'Paid' },
];

// Category options for dropdown
const categoryOptions = computed(() => {
    return [
        { value: '', label: 'All Categories' },
        ...props.categories
    ];
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

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Check if filters are active
const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedStatus.value || selectedPaymentStatus.value ||
           selectedCategoryId.value || startDate.value || endDate.value;
});

// Debounce timer
let searchTimeout = null;

// Fetch data with filters
const fetchData = () => {
    const params = {
        search: searchQuery.value || undefined,
        status: selectedStatus.value || undefined,
        payment_status: selectedPaymentStatus.value || undefined,
        category_id: selectedCategoryId.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    };

    router.get('/finance/expenses', params, {
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
watch([selectedStatus, selectedPaymentStatus, selectedCategoryId, startDate, endDate], () => {
    fetchData();
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = '';
    selectedPaymentStatus.value = '';
    selectedCategoryId.value = '';
    startDate.value = '';
    endDate.value = '';
    fetchData();
};

// Pagination helpers
const pagination = computed(() => props.expenses?.meta || props.expenses);

const goToPage = (pageNum) => {
    if (pageNum < 1 || pageNum > pagination.value.last_page) return;

    router.get('/finance/expenses', {
        ...props.filters,
        page: pageNum,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Get status badge class
const getStatusBadgeClass = (color) => {
    const colorMap = {
        gray: 'badge-outline badge-gray',
        info: 'badge-outline badge-info',
        success: 'badge-outline badge-success',
        danger: 'badge-outline badge-danger',
        warning: 'badge-outline badge-warning',
    };
    return colorMap[color] || 'badge-outline badge-gray';
};

// Get payment status badge class
const getPaymentBadgeClass = (color) => {
    const colorMap = {
        danger: 'badge-danger',
        warning: 'badge-warning',
        success: 'badge-success',
    };
    return colorMap[color] || 'badge-gray';
};

// Approve expense
const approveExpense = async (expense) => {
    const result = await Swal.fire({
        title: 'Approve Expense?',
        text: `Are you sure you want to approve expense "${expense.expense_number}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, approve it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.post(`/finance/api/expenses/${expense.id}/approve`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message || 'Expense approved successfully',
            });

            router.reload({ only: ['expenses', 'stats'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to approve expense',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Post expense
const postExpense = async (expense) => {
    const result = await Swal.fire({
        title: 'Post Expense?',
        text: `Are you sure you want to post expense "${expense.expense_number}"? This will create journal entries and cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, post it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.post(`/finance/api/expenses/${expense.id}/post`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message || 'Expense posted successfully',
            });

            router.reload({ only: ['expenses', 'stats'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to post expense',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Delete expense
const deleteExpense = async (expense) => {
    const result = await Swal.fire({
        title: 'Delete Expense?',
        text: `Are you sure you want to delete expense "${expense.expense_number}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.delete(`/finance/api/expenses/${expense.id}`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Deleted!',
                text: response.data.message || 'Expense deleted successfully',
            });

            router.reload({ only: ['expenses', 'stats'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: error.response?.data?.message || 'Failed to delete expense',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Get expenses data (handle both paginated and direct array)
const expensesList = computed(() => {
    return props.expenses?.data || [];
});
</script>

<template>
    <AppLayout title="Expenses">
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
                <span class="text-gray-900 font-medium">Expenses</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Expenses</h1>
                    <p class="text-sm text-gray-500">Manage company expenses and payments</p>
                </div>
                <Link
                    v-if="hasPermission('finance.expenses.create')"
                    href="/finance/expenses/create"
                    class="btn btn-primary"
                >
                    <i class="ki-filled ki-plus me-2"></i>
                    Add Expense
                </Link>
            </div>

            <!-- Stats Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <!-- Total Expenses -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-bill text-gray-600 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.total) }}</span>
                                <p class="text-xs text-gray-500">Total</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Draft -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-notepad text-gray-500 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.draft) }}</span>
                                <p class="text-xs text-gray-500">Draft</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                                <i class="ki-filled ki-check-circle text-info text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.approved) }}</span>
                                <p class="text-xs text-gray-500">Approved</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posted -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-verify text-success text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.posted) }}</span>
                                <p class="text-xs text-gray-500">Posted</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-dollar text-primary text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(stats?.total_amount) }}</span>
                                <p class="text-xs text-gray-500">Total Amount</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unpaid Amount -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-cheque text-danger text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-danger">{{ formatCurrency(stats?.unpaid_amount) }}</span>
                                <p class="text-xs text-gray-500">Unpaid</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <!-- Card Header with Filters -->
                <div class="card-header flex-wrap gap-4">
                    <h3 class="card-title">Expense List</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                            <input
                                type="text"
                                class="input input-sm pl-9 w-[200px]"
                                placeholder="Search expenses..."
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

                        <!-- Status Filter -->
                        <SearchableSelect
                            v-model="selectedStatus"
                            :options="statusOptions"
                            placeholder="All Status"
                            size="sm"
                            class="w-[130px]"
                        />

                        <!-- Payment Status Filter -->
                        <SearchableSelect
                            v-model="selectedPaymentStatus"
                            :options="paymentStatusOptions"
                            placeholder="All Payment"
                            size="sm"
                            class="w-[130px]"
                        />

                        <!-- Category Filter -->
                        <SearchableSelect
                            v-model="selectedCategoryId"
                            :options="categoryOptions"
                            placeholder="All Categories"
                            size="sm"
                            class="w-[160px]"
                        />

                        <!-- Date Range -->
                        <DatePicker
                            v-model="startDate"
                            placeholder="Start Date"
                            size="sm"
                            class="w-[140px]"
                        />

                        <DatePicker
                            v-model="endDate"
                            placeholder="End Date"
                            size="sm"
                            class="w-[140px]"
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
                    <div v-if="!expensesList || expensesList.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-bill text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Expenses Found</h4>
                        <p class="text-sm text-gray-500 mb-4">Get started by creating your first expense</p>
                        <Link
                            v-if="hasPermission('finance.expenses.create')"
                            href="/finance/expenses/create"
                            class="btn btn-primary"
                        >
                            <i class="ki-filled ki-plus me-2"></i>
                            Add Expense
                        </Link>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[130px]">Expense #</th>
                                    <th class="w-[110px]">Date</th>
                                    <th class="min-w-[150px]">Category</th>
                                    <th class="min-w-[150px]">Payee</th>
                                    <th class="min-w-[200px]">Description</th>
                                    <th class="w-[130px] text-end">Amount</th>
                                    <th class="w-[100px] text-center">Status</th>
                                    <th class="w-[100px] text-center">Payment</th>
                                    <th class="w-[80px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="expense in expensesList" :key="expense.id" class="hover:bg-slate-50">
                                    <!-- Expense Number -->
                                    <td>
                                        <Link
                                            :href="`/finance/expenses/${expense.id}`"
                                            class="text-primary hover:underline font-medium"
                                        >
                                            {{ expense.expense_number }}
                                        </Link>
                                    </td>

                                    <!-- Date -->
                                    <td class="text-sm text-gray-600">
                                        {{ expense.expense_date_formatted }}
                                    </td>

                                    <!-- Category -->
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900">{{ expense.category?.name || '-' }}</span>
                                            <span v-if="expense.category?.code" class="text-xs text-gray-400">{{ expense.category.code }}</span>
                                        </div>
                                    </td>

                                    <!-- Payee -->
                                    <td class="text-sm text-gray-700">
                                        {{ expense.payee || '-' }}
                                    </td>

                                    <!-- Description -->
                                    <td>
                                        <span class="text-sm text-gray-600 line-clamp-2" :title="expense.description">
                                            {{ expense.description || '-' }}
                                        </span>
                                    </td>

                                    <!-- Amount -->
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ formatCurrency(expense.total_amount, expense.currency_code) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="getStatusBadgeClass(expense.status_color)"
                                        >
                                            {{ expense.status_label }}
                                        </span>
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="getPaymentBadgeClass(expense.payment_status_color)"
                                        >
                                            {{ expense.payment_status_label }}
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
                                                    <!-- View Action -->
                                                    <div class="menu-item">
                                                        <Link
                                                            :href="`/finance/expenses/${expense.id}`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-eye"></i>
                                                            </span>
                                                            <span class="menu-title">View</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Edit Action -->
                                                    <div v-if="expense.can_edit" class="menu-item">
                                                        <Link
                                                            :href="`/finance/expenses/${expense.id}/edit`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="menu-title">Edit</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Approve Action -->
                                                    <div v-if="expense.can_approve" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="approveExpense(expense)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-check-circle text-info"></i>
                                                            </span>
                                                            <span class="menu-title">Approve</span>
                                                        </span>
                                                    </div>

                                                    <!-- Post Action -->
                                                    <div v-if="expense.can_post" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="postExpense(expense)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-verify text-success"></i>
                                                            </span>
                                                            <span class="menu-title">Post</span>
                                                        </span>
                                                    </div>

                                                    <!-- Separator -->
                                                    <div
                                                        v-if="expense.can_delete"
                                                        class="menu-separator"
                                                    ></div>

                                                    <!-- Delete Action -->
                                                    <div v-if="expense.can_delete" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="deleteExpense(expense)"
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
                    v-if="expensesList && expensesList.length > 0 && pagination?.total > 0"
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
