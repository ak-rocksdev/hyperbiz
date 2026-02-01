<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_companies: 0,
            active_companies: 0,
            trial_companies: 0,
            expired_companies: 0,
            total_users: 0,
            total_platform_admins: 0,
        }),
    },
    recentCompanies: {
        type: Array,
        default: () => [],
    },
    subscriptionChart: {
        type: Object,
        default: () => null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Get subscription status badge class
const getStatusClass = (status) => {
    const classes = {
        'trial': 'badge-info',
        'active': 'badge-success',
        'past_due': 'badge-warning',
        'expired': 'badge-danger',
        'cancelled': 'badge-light',
    };
    return classes[status] || 'badge-light';
};

// Get status icon
const getStatusIcon = (status) => {
    const icons = {
        'trial': 'ki-filled ki-time',
        'active': 'ki-filled ki-check-circle',
        'past_due': 'ki-filled ki-notification-bing',
        'expired': 'ki-filled ki-cross-circle',
        'cancelled': 'ki-filled ki-minus-circle',
    };
    return icons[status] || 'ki-filled ki-information-2';
};
</script>

<template>
    <AppLayout title="Platform Admin Dashboard">
        <div class="container-fixed">
            <!-- Header -->
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-semibold leading-none text-gray-900">
                        Platform Admin Dashboard
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        Welcome back, {{ user?.name }}
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link :href="route('admin.companies')" class="btn btn-sm btn-primary">
                        <i class="ki-filled ki-abstract-41 me-1.5"></i>
                        Manage Companies
                    </Link>
                </div>
            </div>

            <!-- Stats Cards - Modern Metronic Style with Gradients -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 lg:gap-7.5 mb-7.5">
                <!-- Total Companies -->
                <div class="card bg-gradient-to-br from-primary/10 via-primary/5 to-transparent border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-primary shadow-lg shadow-primary/30">
                                <i class="ki-filled ki-abstract-41 text-2xl text-white"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ stats?.total_companies ?? 0 }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">Total Companies</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Companies -->
                <div class="card bg-gradient-to-br from-success/10 via-success/5 to-transparent border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-success shadow-lg shadow-success/30">
                                <i class="ki-filled ki-check-circle text-2xl text-white"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ stats?.active_companies ?? 0 }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trial Companies -->
                <div class="card bg-gradient-to-br from-info/10 via-info/5 to-transparent border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-info shadow-lg shadow-info/30">
                                <i class="ki-filled ki-time text-2xl text-white"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ stats?.trial_companies ?? 0 }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">On Trial</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expired Companies -->
                <div class="card bg-gradient-to-br from-danger/10 via-danger/5 to-transparent border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-danger shadow-lg shadow-danger/30">
                                <i class="ki-filled ki-cross-circle text-2xl text-white"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ stats?.expired_companies ?? 0 }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">Expired</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="card bg-gradient-to-br from-gray-100 via-gray-50 to-transparent border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-gray-700 shadow-lg shadow-gray-700/30">
                                <i class="ki-filled ki-people text-2xl text-white"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ stats?.total_users ?? 0 }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">Total Users</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Platform Admins -->
                <div class="card bg-gradient-to-br from-purple-100 via-purple-50 to-transparent border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-purple-600 shadow-lg shadow-purple-600/30">
                                <i class="ki-filled ki-shield-tick text-2xl text-white"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ stats?.total_platform_admins ?? 0 }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">Platform Admins</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Companies Table -->
            <div class="card shadow-sm">
                <div class="card-header border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                            <i class="ki-filled ki-abstract-41 text-lg text-primary"></i>
                        </div>
                        <div>
                            <h3 class="card-title text-base font-semibold text-gray-900">Recent Companies</h3>
                            <span class="text-xs text-gray-500">Latest registered companies on the platform</span>
                        </div>
                    </div>
                    <Link :href="route('admin.companies')" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-arrow-right me-1"></i>
                        View All
                    </Link>
                </div>
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[220px]">Company</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px]">Status</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[80px]">Users</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Trial Ends</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Created</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider w-[100px]">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="company in recentCompanies" :key="company.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                                {{ company.name?.charAt(0)?.toUpperCase() || 'C' }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-gray-900">{{ company.name }}</span>
                                                <span class="text-xs text-gray-500">{{ company.email || 'No email' }}</span>
                                            </div>
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
                                        <span class="text-sm text-gray-700">{{ company.created_at }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <Link :href="route('admin.companies.detail', company.id)" class="btn btn-sm btn-icon btn-light hover:btn-primary group" title="View Details">
                                            <i class="ki-filled ki-eye text-gray-500 group-hover:text-white"></i>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="recentCompanies.length === 0">
                                    <td colspan="6" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                                <i class="ki-filled ki-abstract-41 text-3xl text-gray-400"></i>
                                            </div>
                                            <p class="text-base font-medium text-gray-600 mb-1">No companies registered yet</p>
                                            <p class="text-sm text-gray-400">Companies will appear here once users register.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
