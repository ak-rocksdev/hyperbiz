<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { KTModal } from '../../../../metronic/core/components/modal';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            users_count: 0,
            products_count: 0,
            customers_count: 0,
            sales_orders_count: 0,
            purchase_orders_count: 0,
        }),
    },
});

// Form for updating subscription
const isLoading = ref(false);
const form = ref({
    subscription_status: props.company.subscription_status,
});

const saveSubscription = () => {
    isLoading.value = true;

    axios.put(route('admin.companies.subscription', props.company.id), form.value)
        .then((response) => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: 'Subscription status updated successfully'
            });
            closeModal('modal_edit_subscription');
            router.reload();
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.response?.data?.message || 'An error occurred',
            });
        })
        .finally(() => {
            isLoading.value = false;
        });
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

onMounted(() => {
    KTModal.init();
});

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

// Get status background class
const getStatusBgClass = (status) => {
    const classes = {
        'trial': 'bg-info/10 border-info/20',
        'active': 'bg-success/10 border-success/20',
        'past_due': 'bg-warning/10 border-warning/20',
        'expired': 'bg-danger/10 border-danger/20',
        'cancelled': 'bg-gray-100 border-gray-200',
    };
    return classes[status] || 'bg-gray-100 border-gray-200';
};

// Status options for SearchableSelect
const statusOptions = [
    { value: 'trial', label: 'Trial' },
    { value: 'active', label: 'Active' },
    { value: 'past_due', label: 'Past Due' },
    { value: 'expired', label: 'Expired' },
    { value: 'cancelled', label: 'Cancelled' },
];
</script>

<template>
    <AppLayout :title="company.name">
        <div class="container-fixed">
            <!-- Header with Breadcrumb -->
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-light text-primary font-bold text-lg shrink-0">
                            {{ company.name?.charAt(0)?.toUpperCase() || 'C' }}
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold leading-none text-gray-900">
                                {{ company.name }}
                            </h1>
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600 mt-1">
                                <Link :href="route('admin.dashboard')" class="hover:text-primary transition-colors">
                                    <i class="ki-filled ki-home text-gray-500 me-1"></i>
                                    Dashboard
                                </Link>
                                <span class="text-gray-400">/</span>
                                <Link :href="route('admin.companies')" class="hover:text-primary transition-colors">Companies</Link>
                                <span class="text-gray-400">/</span>
                                <span class="text-gray-700">{{ company.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link :href="route('admin.companies')" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-arrow-left me-1"></i>
                        Back to List
                    </Link>
                    <button class="btn btn-sm btn-primary" data-modal-toggle="#modal_edit_subscription">
                        <i class="ki-filled ki-pencil me-1.5"></i>
                        Edit Subscription
                    </button>
                </div>
            </div>

            <!-- Stats Cards Row -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                <div class="card bg-gradient-to-br from-primary/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                                <i class="ki-filled ki-people text-primary"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats?.users_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500">Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-success/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success-light">
                                <i class="ki-filled ki-handcart text-success"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats?.products_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500">Products</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-info/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info-light">
                                <i class="ki-filled ki-user text-info"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats?.customers_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500">Customers</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-warning/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-warning-light">
                                <i class="ki-filled ki-basket text-warning"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats?.sales_orders_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500">Sales Orders</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-purple-50 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-100">
                                <i class="ki-filled ki-delivery text-purple-600"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats?.purchase_orders_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500">Purchase Orders</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
                <!-- Left Column - Company Info & Users -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Company Details Card -->
                    <div class="card shadow-sm">
                        <div class="card-header border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                                    <i class="ki-filled ki-abstract-41 text-lg text-primary"></i>
                                </div>
                                <div>
                                    <h3 class="card-title text-base font-semibold text-gray-900">Company Information</h3>
                                    <span class="text-xs text-gray-500">Basic company details and contact information</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Company Name</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ company.name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</label>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <a v-if="company.email" :href="'mailto:' + company.email" class="text-primary hover:underline">
                                            {{ company.email }}
                                        </a>
                                        <span v-else class="text-gray-400">-</span>
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Phone</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ company.phone || '-' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Website</label>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <a v-if="company.website" :href="company.website" target="_blank" class="text-primary hover:underline inline-flex items-center gap-1">
                                            {{ company.website }}
                                            <i class="ki-filled ki-exit-right-corner text-xs"></i>
                                        </a>
                                        <span v-else class="text-gray-400">-</span>
                                    </p>
                                </div>
                                <div class="md:col-span-2 space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Address</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ company.address || '-' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created At</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ company.created_at }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Updated</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ company.updated_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div class="card shadow-sm">
                        <div class="card-header border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info-light">
                                    <i class="ki-filled ki-people text-lg text-info"></i>
                                </div>
                                <div>
                                    <h3 class="card-title text-base font-semibold text-gray-900">Users</h3>
                                    <span class="text-xs text-gray-500">{{ users.length }} users in this company</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table table-auto w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Roles</th>
                                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-gray-200 text-gray-600 font-semibold text-sm shrink-0">
                                                        {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-900">{{ user.name }}</span>
                                                        <span class="text-xs text-gray-500">{{ user.email }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="role in user.roles" :key="role" class="badge badge-sm badge-light">
                                                        {{ role }}
                                                    </span>
                                                    <span v-if="!user.roles?.length" class="text-xs text-gray-400">No roles</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <span :class="['badge badge-sm', user.is_active ? 'badge-success' : 'badge-danger']">
                                                    <i :class="['text-[10px] me-1', user.is_active ? 'ki-filled ki-check-circle' : 'ki-filled ki-cross-circle']"></i>
                                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="text-sm text-gray-700">{{ user.created_at }}</span>
                                            </td>
                                        </tr>
                                        <tr v-if="users.length === 0">
                                            <td colspan="4" class="text-center py-12">
                                                <div class="flex flex-col items-center justify-center">
                                                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 mb-3">
                                                        <i class="ki-filled ki-people text-2xl text-gray-400"></i>
                                                    </div>
                                                    <p class="text-sm font-medium text-gray-600">No users in this company</p>
                                                    <p class="text-xs text-gray-400 mt-1">Users will appear here once they join.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Subscription Card -->
                <div class="space-y-5">
                    <!-- Subscription Status Card -->
                    <div class="card shadow-sm overflow-hidden">
                        <div :class="['border-b-4', getStatusBgClass(company.subscription_status)]">
                            <div class="card-header border-b border-gray-200 bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success-light">
                                        <i class="ki-filled ki-wallet text-lg text-success"></i>
                                    </div>
                                    <div>
                                        <h3 class="card-title text-base font-semibold text-gray-900">Subscription</h3>
                                        <span class="text-xs text-gray-500">Current subscription status</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-6">
                            <!-- Status Banner -->
                            <div :class="['rounded-xl p-4 mb-6 border', getStatusBgClass(company.subscription_status)]">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div :class="['flex items-center justify-center w-12 h-12 rounded-full',
                                            company.subscription_status === 'active' ? 'bg-success text-white' :
                                            company.subscription_status === 'trial' ? 'bg-info text-white' :
                                            company.subscription_status === 'past_due' ? 'bg-warning text-white' :
                                            company.subscription_status === 'expired' ? 'bg-danger text-white' :
                                            'bg-gray-400 text-white']">
                                            <i :class="[getStatusIcon(company.subscription_status), 'text-xl']"></i>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500 uppercase">Status</span>
                                            <div class="text-lg font-bold text-gray-900">{{ company.subscription_status_label }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscription Details -->
                            <div class="space-y-4">
                                <div v-if="company.trial_ends_at" class="flex items-center justify-between py-3 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <i class="ki-filled ki-time text-gray-400"></i>
                                        <span class="text-sm text-gray-600">Trial Ends</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ company.trial_ends_at }}</span>
                                </div>
                                <div v-if="company.subscription_starts_at" class="flex items-center justify-between py-3 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <i class="ki-filled ki-calendar-tick text-gray-400"></i>
                                        <span class="text-sm text-gray-600">Started</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ company.subscription_starts_at }}</span>
                                </div>
                                <div v-if="company.subscription_ends_at" class="flex items-center justify-between py-3 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <i class="ki-filled ki-calendar-remove text-gray-400"></i>
                                        <span class="text-sm text-gray-600">Ends</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ company.subscription_ends_at }}</span>
                                </div>
                                <div v-if="!company.trial_ends_at && !company.subscription_starts_at && !company.subscription_ends_at" class="text-center py-4 text-gray-400 text-sm">
                                    No subscription dates configured
                                </div>
                            </div>

                            <!-- Edit Button -->
                            <button class="btn btn-primary w-full mt-6" data-modal-toggle="#modal_edit_subscription">
                                <i class="ki-filled ki-pencil me-2"></i>
                                Edit Subscription
                            </button>
                        </div>
                    </div>

                    <!-- Quick Stats Card -->
                    <div class="card shadow-sm">
                        <div class="card-header border-b border-gray-200">
                            <div class="flex items-center gap-2">
                                <i class="ki-filled ki-chart-pie-4 text-gray-500"></i>
                                <h3 class="card-title text-sm font-semibold text-gray-700">Quick Overview</h3>
                            </div>
                        </div>
                        <div class="card-body p-5">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-primary"></div>
                                        <span class="text-sm text-gray-600">Users</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">{{ stats?.users_count ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-success"></div>
                                        <span class="text-sm text-gray-600">Products</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">{{ stats?.products_count ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-info"></div>
                                        <span class="text-sm text-gray-600">Customers</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">{{ stats?.customers_count ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-warning"></div>
                                        <span class="text-sm text-gray-600">Sales Orders</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">{{ stats?.sales_orders_count ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-purple-600"></div>
                                        <span class="text-sm text-gray-600">Purchase Orders</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">{{ stats?.purchase_orders_count ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Subscription Modal -->
        <div id="modal_edit_subscription" data-modal="true" class="modal">
            <div class="modal-dialog">
                <div class="modal-content max-w-[500px] top-[15%]">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Subscription Status</h5>
                        <button type="button" class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveSubscription">
                        <div class="modal-body py-5">
                            <div class="mb-5">
                                <label class="form-label text-gray-700">
                                    <i class="ki-filled ki-wallet text-gray-500 me-1"></i>
                                    Subscription Status
                                </label>
                                <SearchableSelect
                                    v-model="form.subscription_status"
                                    :options="statusOptions"
                                    placeholder="Select status"
                                    :clearable="false"
                                />
                                <p class="text-xs text-gray-500 mt-1.5">Select the new subscription status for this company.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-modal-dismiss="true">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                {{ isLoading ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
