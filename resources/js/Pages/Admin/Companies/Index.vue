<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    companies: {
        type: Object,
        default: () => ({
            data: [],
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
            links: [],
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

// Debounced search
const performSearch = debounce(() => {
    router.get(route('admin.companies'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status], () => {
    performSearch();
});

// Clear filters
const clearFilters = () => {
    search.value = '';
    status.value = '';
};

// Check if filters are active
const hasActiveFilters = computed(() => search.value || status.value);

// Get subscription status badge class
const getStatusClass = (statusValue) => {
    const classes = {
        'trial': 'badge-info',
        'active': 'badge-success',
        'past_due': 'badge-warning',
        'expired': 'badge-danger',
        'cancelled': 'badge-light',
    };
    return classes[statusValue] || 'badge-light';
};

// Get status icon
const getStatusIcon = (statusValue) => {
    const icons = {
        'trial': 'ki-filled ki-time',
        'active': 'ki-filled ki-check-circle',
        'past_due': 'ki-filled ki-notification-bing',
        'expired': 'ki-filled ki-cross-circle',
        'cancelled': 'ki-filled ki-minus-circle',
    };
    return icons[statusValue] || 'ki-filled ki-information-2';
};

// Status options for filter
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'trial', label: 'Trial' },
    { value: 'active', label: 'Active' },
    { value: 'past_due', label: 'Past Due' },
    { value: 'expired', label: 'Expired' },
    { value: 'cancelled', label: 'Cancelled' },
];
</script>

<template>
    <AppLayout title="Manage Companies">
        <div class="container-fixed">
            <!-- Header with Breadcrumb -->
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-semibold leading-none text-gray-900">
                        Manage Companies
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <Link :href="route('admin.dashboard')" class="hover:text-primary transition-colors">
                            <i class="ki-filled ki-home text-gray-500 me-1"></i>
                            Dashboard
                        </Link>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-700">Companies</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link :href="route('admin.dashboard')" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-arrow-left me-1"></i>
                        Back to Dashboard
                    </Link>
                </div>
            </div>

            <!-- Stats Summary Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="card bg-gradient-to-br from-primary/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                                <i class="ki-filled ki-abstract-41 text-primary"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ companies.total }}</div>
                                <div class="text-xs text-gray-500">Total Companies</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-success/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success-light">
                                <i class="ki-filled ki-check-circle text-success"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ companies.data.filter(c => c.subscription_status === 'active').length }}</div>
                                <div class="text-xs text-gray-500">Active (This Page)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-info/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info-light">
                                <i class="ki-filled ki-time text-info"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ companies.data.filter(c => c.subscription_status === 'trial').length }}</div>
                                <div class="text-xs text-gray-500">On Trial (This Page)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-danger/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-danger-light">
                                <i class="ki-filled ki-cross-circle text-danger"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ companies.data.filter(c => c.subscription_status === 'expired').length }}</div>
                                <div class="text-xs text-gray-500">Expired (This Page)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-header border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <i class="ki-filled ki-filter text-gray-500"></i>
                        <h3 class="card-title text-sm font-semibold text-gray-700">Filters</h3>
                    </div>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="btn btn-sm btn-light-danger"
                    >
                        <i class="ki-filled ki-cross text-xs me-1"></i>
                        Clear Filters
                    </button>
                </div>
                <div class="card-body p-5">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Search Input -->
                        <div class="flex-1 min-w-[280px]">
                            <label class="form-label text-xs text-gray-500 mb-1.5">Search</label>
                            <div class="relative">
                                <i class="ki-filled ki-magnifier absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input
                                    v-model="search"
                                    type="text"
                                    class="input ps-10"
                                    placeholder="Search by company name or email..."
                                />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="w-[200px]">
                            <label class="form-label text-xs text-gray-500 mb-1.5">Status</label>
                            <select v-model="status" class="select">
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Companies Table -->
            <div class="card shadow-sm">
                <div class="card-header border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                            <i class="ki-filled ki-abstract-41 text-lg text-primary"></i>
                        </div>
                        <div>
                            <h3 class="card-title text-base font-semibold text-gray-900">Companies List</h3>
                            <span class="text-xs text-gray-500">
                                Showing {{ companies.from || 0 }} to {{ companies.to || 0 }} of {{ companies.total }} companies
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[220px]">Company</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[180px]">Contact</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px]">Status</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[80px]">Users</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Trial Ends</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Subscription Ends</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Created</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider w-[100px]">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="company in companies.data" :key="company.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                                {{ company.name?.charAt(0)?.toUpperCase() || 'C' }}
                                            </div>
                                            <div class="flex flex-col">
                                                <Link :href="route('admin.companies.detail', company.id)" class="font-semibold text-gray-900 hover:text-primary transition-colors">
                                                    {{ company.name }}
                                                </Link>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-700">{{ company.email || '-' }}</span>
                                            <span class="text-xs text-gray-500">{{ company.phone || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span :class="['badge badge-sm inline-flex items-center gap-1', getStatusClass(company.subscription_status)]">
                                            <i :class="[getStatusIcon(company.subscription_status), 'text-[10px]']"></i>
                                            {{ company.subscription_status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[32px] h-7 px-2 rounded-md bg-gray-100 text-sm font-semibold text-gray-700">
                                            {{ company.users_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-700">{{ company.trial_ends_at || '-' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-700">{{ company.subscription_ends_at || '-' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-700">{{ company.created_at }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
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
                                                    <div class="menu-item">
                                                        <Link class="menu-link" :href="route('admin.companies.detail', company.id)">
                                                            <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                            <span class="menu-title">View Details</span>
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="companies.data.length === 0">
                                    <td colspan="8" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                                <i class="ki-filled ki-abstract-41 text-3xl text-gray-400"></i>
                                            </div>
                                            <p class="text-base font-medium text-gray-600 mb-1">No companies found</p>
                                            <p class="text-sm text-gray-400" v-if="hasActiveFilters">Try adjusting your filters</p>
                                            <p class="text-sm text-gray-400" v-else>Companies will appear here once users register.</p>
                                            <button
                                                v-if="hasActiveFilters"
                                                @click="clearFilters"
                                                class="btn btn-sm btn-light mt-4"
                                            >
                                                <i class="ki-filled ki-arrow-left me-1"></i>
                                                Clear Filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="companies.last_page > 1" class="card-footer border-t border-gray-200 flex flex-wrap justify-between items-center gap-4 py-4 px-5">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold text-gray-900">{{ companies.from }}</span> to <span class="font-semibold text-gray-900">{{ companies.to }}</span> of <span class="font-semibold text-gray-900">{{ companies.total }}</span> companies
                    </div>
                    <div class="flex gap-1.5">
                        <Link
                            v-for="link in companies.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'btn btn-sm min-w-[36px]',
                                link.active ? 'btn-primary' : 'btn-light',
                                !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
