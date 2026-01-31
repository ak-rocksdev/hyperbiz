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
    entries: {
        type: Object,
        default: () => ({ data: [], meta: {} })
    },
    fiscalPeriods: {
        type: Array,
        default: () => []
    },
    entryTypes: {
        type: Object,
        default: () => ({})
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
            posted: 0,
            voided: 0,
            total_debit: 0,
            total_credit: 0
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
const selectedEntryType = ref(props.filters?.entry_type || '');
const selectedFiscalPeriodId = ref(props.filters?.fiscal_period_id || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

// Loading state
const isLoading = ref(false);

// Filter options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'draft', label: 'Draft' },
    { value: 'posted', label: 'Posted' },
    { value: 'voided', label: 'Voided' },
];

// Entry type options from props
const entryTypeOptions = computed(() => {
    const options = [{ value: '', label: 'All Types' }];
    if (props.entryTypes) {
        Object.entries(props.entryTypes).forEach(([key, label]) => {
            options.push({ value: key, label: label });
        });
    }
    return options;
});

// Fiscal period options for dropdown
const fiscalPeriodOptions = computed(() => {
    return [
        { value: '', label: 'All Periods' },
        ...props.fiscalPeriods
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
    return searchQuery.value || selectedStatus.value || selectedEntryType.value ||
           selectedFiscalPeriodId.value || startDate.value || endDate.value;
});

// Debounce timer
let searchTimeout = null;

// Fetch data with filters
const fetchData = () => {
    const params = {
        search: searchQuery.value || undefined,
        status: selectedStatus.value || undefined,
        entry_type: selectedEntryType.value || undefined,
        fiscal_period_id: selectedFiscalPeriodId.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    };

    router.get('/finance/journal-entries', params, {
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
watch([selectedStatus, selectedEntryType, selectedFiscalPeriodId, startDate, endDate], () => {
    fetchData();
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = '';
    selectedEntryType.value = '';
    selectedFiscalPeriodId.value = '';
    startDate.value = '';
    endDate.value = '';
    fetchData();
};

// Pagination helpers
const pagination = computed(() => props.entries?.meta || props.entries);

const goToPage = (pageNum) => {
    if (pageNum < 1 || pageNum > pagination.value.last_page) return;

    router.get('/finance/journal-entries', {
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
        light: 'badge-light',
        gray: 'badge-light',
        success: 'badge-success',
        danger: 'badge-danger',
        warning: 'badge-warning',
        info: 'badge-info',
    };
    return colorMap[color] || 'badge-light';
};

// Post journal entry
const postEntry = async (entry) => {
    const result = await Swal.fire({
        title: 'Post Journal Entry?',
        text: `Are you sure you want to post entry "${entry.entry_number}"? This will finalize the entry and cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, post it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.post(`/finance/api/journal-entries/${entry.id}/post`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message || 'Journal entry posted successfully',
            });

            router.reload({ only: ['entries', 'stats'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to post journal entry',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Void journal entry
const voidEntry = async (entry) => {
    const { value: reason } = await Swal.fire({
        title: 'Void Journal Entry?',
        text: `Please provide a reason for voiding entry "${entry.entry_number}":`,
        icon: 'warning',
        input: 'textarea',
        inputLabel: 'Void Reason',
        inputPlaceholder: 'Enter the reason for voiding this entry...',
        inputAttributes: {
            'aria-label': 'Void reason'
        },
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a reason for voiding this entry';
            }
        },
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Void Entry',
    });

    if (reason) {
        isLoading.value = true;
        try {
            const response = await axios.post(`/finance/api/journal-entries/${entry.id}/void`, {
                reason: reason
            });

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Voided!',
                text: response.data.message || 'Journal entry voided successfully',
            });

            router.reload({ only: ['entries', 'stats'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to void journal entry',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Delete journal entry
const deleteEntry = async (entry) => {
    const result = await Swal.fire({
        title: 'Delete Journal Entry?',
        text: `Are you sure you want to delete entry "${entry.entry_number}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            const response = await axios.delete(`/finance/api/journal-entries/${entry.id}`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Deleted!',
                text: response.data.message || 'Journal entry deleted successfully',
            });

            router.reload({ only: ['entries', 'stats'] });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: error.response?.data?.message || 'Failed to delete journal entry',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Get entries data (handle both paginated and direct array)
const entriesList = computed(() => {
    return props.entries?.data || [];
});
</script>

<template>
    <AppLayout title="Journal Entries">
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
                <span class="text-gray-900 font-medium">Journal Entries</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Journal Entries</h1>
                    <p class="text-sm text-gray-500">Manage general ledger journal entries</p>
                </div>
                <Link
                    v-if="hasPermission('finance.journal_entries.create')"
                    href="/finance/journal-entries/create"
                    class="btn btn-primary"
                >
                    <i class="ki-filled ki-plus me-2"></i>
                    Create Entry
                </Link>
            </div>

            <!-- Stats Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <!-- Total Entries -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ki-filled ki-document text-gray-600 text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.total) }}</span>
                                <p class="text-xs text-gray-500">Total Entries</p>
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

                <!-- Voided -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-cross-circle text-danger text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xl font-bold text-gray-900">{{ formatNumber(stats?.voided) }}</span>
                                <p class="text-xs text-gray-500">Voided</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Debit -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-arrow-up-left text-primary text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(stats?.total_debit) }}</span>
                                <p class="text-xs text-gray-500">Total Debit</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Credit -->
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                                <i class="ki-filled ki-arrow-down-right text-info text-lg"></i>
                            </div>
                            <div>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(stats?.total_credit) }}</span>
                                <p class="text-xs text-gray-500">Total Credit</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <!-- Card Header with Filters -->
                <div class="card-header flex-wrap gap-4">
                    <h3 class="card-title">Journal Entries List</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                            <input
                                type="text"
                                class="input input-sm pl-9 w-[200px]"
                                placeholder="Search entries..."
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

                        <!-- Entry Type Filter -->
                        <SearchableSelect
                            v-model="selectedEntryType"
                            :options="entryTypeOptions"
                            placeholder="All Types"
                            size="sm"
                            class="w-[150px]"
                        />

                        <!-- Fiscal Period Filter -->
                        <SearchableSelect
                            v-model="selectedFiscalPeriodId"
                            :options="fiscalPeriodOptions"
                            placeholder="All Periods"
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
                    <div v-if="!entriesList || entriesList.length === 0" class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-document text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Journal Entries Found</h4>
                        <p class="text-sm text-gray-500 mb-4">Get started by creating your first journal entry</p>
                        <Link
                            v-if="hasPermission('finance.journal_entries.create')"
                            href="/finance/journal-entries/create"
                            class="btn btn-primary"
                        >
                            <i class="ki-filled ki-plus me-2"></i>
                            Create Entry
                        </Link>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[140px]">Entry Number</th>
                                    <th class="w-[110px]">Date</th>
                                    <th class="min-w-[120px]">Type</th>
                                    <th class="min-w-[200px]">Memo</th>
                                    <th class="w-[130px] text-end">Debit</th>
                                    <th class="w-[130px] text-end">Credit</th>
                                    <th class="w-[110px] text-center">Status</th>
                                    <th class="w-[80px] text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in entriesList" :key="entry.id" class="hover:bg-slate-50">
                                    <!-- Entry Number -->
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <Link
                                                :href="`/finance/journal-entries/${entry.id}`"
                                                class="text-primary hover:underline font-medium"
                                            >
                                                {{ entry.entry_number }}
                                            </Link>
                                            <!-- Balanced Indicator -->
                                            <span
                                                v-if="entry.is_balanced"
                                                class="text-success"
                                                title="Entry is balanced"
                                            >
                                                <i class="ki-filled ki-check-circle text-sm"></i>
                                            </span>
                                            <span
                                                v-else
                                                class="text-warning"
                                                title="Entry is not balanced"
                                            >
                                                <i class="ki-filled ki-information-2 text-sm"></i>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Date -->
                                    <td class="text-sm text-gray-600">
                                        {{ entry.entry_date_formatted }}
                                    </td>

                                    <!-- Type -->
                                    <td>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ entry.entry_type_label }}
                                        </span>
                                    </td>

                                    <!-- Memo -->
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-600 line-clamp-2" :title="entry.memo">
                                                {{ entry.memo || '-' }}
                                            </span>
                                            <span v-if="entry.fiscal_period?.name" class="text-xs text-gray-400">
                                                {{ entry.fiscal_period.name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Debit -->
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ formatCurrency(entry.total_debit, entry.currency_code) }}
                                        </span>
                                    </td>

                                    <!-- Credit -->
                                    <td class="text-end">
                                        <span class="font-semibold text-gray-900">
                                            {{ formatCurrency(entry.total_credit, entry.currency_code) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="getStatusBadgeClass(entry.status_color)"
                                        >
                                            {{ entry.status_label }}
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
                                                            :href="`/finance/journal-entries/${entry.id}`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-eye"></i>
                                                            </span>
                                                            <span class="menu-title">View</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Edit Action -->
                                                    <div v-if="entry.can_edit" class="menu-item">
                                                        <Link
                                                            :href="`/finance/journal-entries/${entry.id}/edit`"
                                                            class="menu-link"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="menu-title">Edit</span>
                                                        </Link>
                                                    </div>

                                                    <!-- Post Action -->
                                                    <div v-if="entry.can_post" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="postEntry(entry)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-verify text-success"></i>
                                                            </span>
                                                            <span class="menu-title">Post</span>
                                                        </span>
                                                    </div>

                                                    <!-- Void Action -->
                                                    <div v-if="entry.can_void" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="voidEntry(entry)"
                                                        >
                                                            <span class="menu-icon">
                                                                <i class="ki-filled ki-cross-circle text-warning"></i>
                                                            </span>
                                                            <span class="menu-title">Void</span>
                                                        </span>
                                                    </div>

                                                    <!-- Separator -->
                                                    <div
                                                        v-if="entry.can_delete"
                                                        class="menu-separator"
                                                    ></div>

                                                    <!-- Delete Action -->
                                                    <div v-if="entry.can_delete" class="menu-item">
                                                        <span
                                                            class="menu-link cursor-pointer"
                                                            @click="deleteEntry(entry)"
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
                    v-if="entriesList && entriesList.length > 0 && pagination?.total > 0"
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
