<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PlanModal from '@/Components/Admin/PlanModal.vue';
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';
import draggable from 'vuedraggable';
import Swal from 'sweetalert2';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
        }),
    },
});

// View mode toggle (table or card)
const viewMode = ref('table');

// Filters
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Modal state
const showModal = ref(false);
const editingPlan = ref(null);

// Open modal for create
const openCreateModal = () => {
    editingPlan.value = null;
    showModal.value = true;
};

// Open modal for edit
const openEditModal = (plan) => {
    editingPlan.value = plan;
    showModal.value = true;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    editingPlan.value = null;
};

// Handle plan saved
const handlePlanSaved = () => {
    router.reload({ only: ['plans', 'stats'] });
};

// Debounced filter search
const performSearch = debounce(() => {
    router.get(route('admin.plans.index'), {
        search: search.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

// Watch for filter changes
watch([search, statusFilter], performSearch);

// Toggle plan status
const toggleStatus = async (plan) => {
    const action = plan.is_active ? 'deactivate' : 'activate';
    const result = await Swal.fire({
        title: `${plan.is_active ? 'Deactivate' : 'Activate'} Plan?`,
        text: `Are you sure you want to ${action} "${plan.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: plan.is_active ? '#F59E0B' : '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Yes, ${action} it!`,
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await axios.patch(route('admin.plans.toggle-status', plan.id));

        if (response.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message
            });
            router.reload({ only: ['plans', 'stats'] });
        }
    } catch (error) {
        console.error('Error toggling plan status:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to toggle plan status'
        });
    }
};

// Delete plan
const deletePlan = async (plan) => {
    const result = await Swal.fire({
        title: 'Delete Plan?',
        text: `Are you sure you want to delete "${plan.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await axios.delete(route('admin.plans.destroy', plan.id));

        if (response.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Deleted!',
                text: response.data.message
            });
            router.reload({ only: ['plans', 'stats'] });
        }
    } catch (error) {
        console.error('Error deleting plan:', error);
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete',
            text: error.response?.data?.message || 'Failed to delete plan'
        });
    }
};

// Format currency (IDR)
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Get limit display
const getLimitDisplay = (value) => {
    return value === 0 ? 'Unlimited' : value.toLocaleString();
};

// Check if plan is the "Most Popular" (Professional plan)
const isMostPopular = (plan) => {
    return plan.name.toLowerCase() === 'professional';
};

// Get features array from plan (handles both array and JSON string)
const getPlanFeatures = (plan) => {
    if (!plan.features) return [];
    if (Array.isArray(plan.features)) return plan.features;
    try {
        return JSON.parse(plan.features);
    } catch {
        return [];
    }
};

// Draggable list for reordering
const plansList = ref([...props.plans]);
const isDragging = ref(false);

// Watch for plans prop changes
watch(() => props.plans, (newPlans) => {
    plansList.value = [...newPlans];
}, { deep: true });

// Handle drag end - update sort order
const onDragEnd = async (event) => {
    isDragging.value = false;

    // Only proceed if the order actually changed
    if (event.oldIndex === event.newIndex) return;

    // Build the new order array
    const orderedPlans = plansList.value.map((plan, index) => ({
        id: plan.id,
        sort_order: index + 1,
    }));

    try {
        const response = await axios.post(route('admin.plans.update-order'), {
            plans: orderedPlans,
        });

        if (response.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                icon: 'success',
                title: 'Order updated'
            });
        }
    } catch (error) {
        console.error('Error updating order:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to update plan order'
        });
        // Revert to original order on error
        plansList.value = [...props.plans];
    }
};
</script>

<template>
    <AppLayout title="Subscription Plans">
        <div class="container-fixed">
            <!-- Header -->
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-semibold leading-none text-gray-900">
                        Subscription Plans
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        Manage subscription plans for your SaaS platform
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" @click="openCreateModal" class="btn btn-sm btn-primary">
                        <i class="ki-filled ki-plus-squared me-1.5"></i>
                        Create Plan
                    </button>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="card mb-7.5">
                <div class="card-body py-4">
                    <div class="flex flex-wrap items-center gap-5 lg:gap-10">
                        <!-- Total Plans -->
                        <div class="flex items-center gap-3 min-w-[120px]">
                            <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-primary-light">
                                <i class="ki-filled ki-price-tag text-primary text-xl"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats.total }}</div>
                                <div class="text-xs text-gray-500">Total Plans</div>
                            </div>
                        </div>

                        <div class="hidden sm:block h-10 border-l border-gray-200"></div>

                        <!-- Active Plans -->
                        <div class="flex items-center gap-3 min-w-[120px]">
                            <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-success-light">
                                <i class="ki-filled ki-check-circle text-success text-xl"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats.active }}</div>
                                <div class="text-xs text-gray-500">Active</div>
                            </div>
                        </div>

                        <div class="hidden sm:block h-10 border-l border-gray-200"></div>

                        <!-- Inactive Plans -->
                        <div class="flex items-center gap-3 min-w-[120px]">
                            <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-gray-100">
                                <i class="ki-filled ki-cross-circle text-gray-500 text-xl"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats.inactive }}</div>
                                <div class="text-xs text-gray-500">Inactive</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plans Section -->
            <div class="card shadow-sm">
                <div class="card-header border-b border-gray-200">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                                <i class="ki-filled ki-price-tag text-lg text-primary"></i>
                            </div>
                            <div>
                                <h3 class="card-title text-base font-semibold text-gray-900">Subscription Plans</h3>
                                <span class="text-xs text-gray-500">Manage and configure your subscription tiers</span>
                            </div>
                        </div>
                        <!-- View Toggle Buttons -->
                        <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                            <button
                                type="button"
                                @click="viewMode = 'table'"
                                :class="[
                                    'btn btn-sm btn-icon transition-all duration-200',
                                    viewMode === 'table'
                                        ? 'btn-primary shadow-sm'
                                        : 'btn-clear hover:bg-gray-200'
                                ]"
                                title="Table View"
                            >
                                <i class="ki-filled ki-row-horizontal"></i>
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'card'"
                                :class="[
                                    'btn btn-sm btn-icon transition-all duration-200',
                                    viewMode === 'card'
                                        ? 'btn-primary shadow-sm'
                                        : 'btn-clear hover:bg-gray-200'
                                ]"
                                title="Card View"
                            >
                                <i class="ki-filled ki-element-11"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body border-b border-gray-200 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="form-label text-sm font-medium text-gray-700 mb-2">Search</label>
                            <div class="relative">
                                <i class="ki-filled ki-magnifier absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>
                                <input
                                    v-model="search"
                                    type="text"
                                    class="input"
                                    style="padding-left: 2.25rem;"
                                    placeholder="Search plans..."
                                />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="form-label text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select v-model="statusFilter" class="select">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div v-if="viewMode === 'table'" class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[200px]">Plan</th>
                                    <th class="text-right px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px]">Monthly</th>
                                    <th class="text-right px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px]">Yearly</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[100px]">Users</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[100px]">Products</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px]">Companies</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[100px]">Status</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider w-[140px]">Actions</th>
                                </tr>
                            </thead>
                            <draggable
                                v-if="plansList.length > 0"
                                v-model="plansList"
                                tag="tbody"
                                item-key="id"
                                ghost-class="opacity-50"
                                :animation="200"
                                :force-fallback="true"
                                @start="isDragging = true"
                                @end="onDragEnd"
                            >
                                <template #item="{ element: plan }">
                                    <tr class="hover:bg-gray-50/50 transition-colors bg-white border-b border-gray-100 cursor-default">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="drag-handle cursor-move p-2 -ml-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded select-none">
                                                    <i class="ki-filled ki-dots-vertical text-lg pointer-events-none"></i>
                                                </div>
                                                <div class="flex flex-col">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-semibold text-gray-900">{{ plan.name }}</span>
                                                        <span v-if="plan.yearly_discount > 0" class="badge badge-sm badge-success">
                                                            -{{ plan.yearly_discount }}% yearly
                                                        </span>
                                                    </div>
                                                    <span v-if="plan.description" class="text-xs text-gray-500 line-clamp-1">
                                                        {{ plan.description }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ formatCurrency(plan.price_monthly) }}
                                            </span>
                                            <span class="text-xs text-gray-500">/mo</span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ formatCurrency(plan.price_yearly) }}
                                            </span>
                                            <span class="text-xs text-gray-500">/yr</span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="text-sm text-gray-700">{{ getLimitDisplay(plan.max_users) }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="text-sm text-gray-700">{{ getLimitDisplay(plan.max_products) }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[32px] h-7 px-2 rounded-md bg-gray-100 text-sm font-semibold text-gray-700">
                                                {{ plan.companies_count }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span :class="['badge badge-sm', plan.is_active ? 'badge-success' : 'badge-light']">
                                                <i :class="['text-[10px]', plan.is_active ? 'ki-filled ki-check-circle' : 'ki-filled ki-cross-circle']"></i>
                                                {{ plan.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="openEditModal(plan)"
                                                    class="btn btn-sm btn-icon btn-light hover:bg-primary hover:border-primary hover:text-white transition-colors"
                                                    title="Edit"
                                                >
                                                    <i class="ki-filled ki-pencil"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="toggleStatus(plan)"
                                                    :class="[
                                                        'btn btn-sm btn-icon btn-light transition-colors',
                                                        plan.is_active
                                                            ? 'hover:bg-warning hover:border-warning hover:text-white'
                                                            : 'hover:bg-success hover:border-success hover:text-white'
                                                    ]"
                                                    :title="plan.is_active ? 'Deactivate' : 'Activate'"
                                                >
                                                    <i :class="plan.is_active ? 'ki-filled ki-cross-circle' : 'ki-filled ki-check-circle'"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deletePlan(plan)"
                                                    class="btn btn-sm btn-icon btn-light hover:bg-danger hover:border-danger hover:text-white transition-colors"
                                                    title="Delete"
                                                >
                                                    <i class="ki-filled ki-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </draggable>
                            <tbody v-else>
                                <tr>
                                    <td colspan="8" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                                <i class="ki-filled ki-price-tag text-3xl text-gray-400"></i>
                                            </div>
                                            <p class="text-base font-medium text-gray-600 mb-1">No subscription plans found</p>
                                            <p class="text-sm text-gray-400">Create your first plan to get started.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Card View - Pricing Cards Grid -->
                <div v-else class="card-body p-5">
                    <!-- Empty State -->
                    <div v-if="plansList.length === 0" class="text-center py-12">
                        <div class="flex flex-col items-center justify-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                <i class="ki-filled ki-price-tag text-3xl text-gray-400"></i>
                            </div>
                            <p class="text-base font-medium text-gray-600 mb-1">No subscription plans found</p>
                            <p class="text-sm text-gray-400">Create your first plan to get started.</p>
                        </div>
                    </div>

                    <!-- Pricing Cards Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div
                            v-for="plan in plansList"
                            :key="plan.id"
                            class="relative flex flex-col rounded-xl border-2 bg-white transition-all duration-300 hover:shadow-lg"
                            :class="[
                                isMostPopular(plan)
                                    ? 'border-primary shadow-md'
                                    : plan.is_active
                                        ? 'border-gray-200 hover:border-primary/50'
                                        : 'border-gray-200 opacity-75'
                            ]"
                        >
                            <!-- Most Popular Badge -->
                            <div
                                v-if="isMostPopular(plan)"
                                class="absolute -top-3 left-1/2 -translate-x-1/2 z-10"
                            >
                                <span class="badge badge-primary shadow-sm px-4 py-1.5 text-xs font-semibold">
                                    <i class="ki-filled ki-crown text-[10px] me-1.5"></i>
                                    Most Popular
                                </span>
                            </div>

                            <!-- Inactive Badge -->
                            <div
                                v-if="!plan.is_active"
                                class="absolute -top-3 right-4 z-10"
                            >
                                <span class="badge badge-light shadow-sm px-3 py-1 text-xs font-medium">
                                    <i class="ki-filled ki-cross-circle text-[10px] me-1"></i>
                                    Inactive
                                </span>
                            </div>

                            <!-- Card Header -->
                            <div class="p-6 pb-4 text-center border-b border-gray-100">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
                                <p v-if="plan.description" class="text-sm text-gray-500 line-clamp-2 min-h-[40px]">
                                    {{ plan.description }}
                                </p>
                            </div>

                            <!-- Pricing Section -->
                            <div class="p-6 pt-5 text-center">
                                <!-- Monthly Price -->
                                <div class="mb-3">
                                    <span class="text-3xl font-bold text-gray-900">{{ formatCurrency(plan.price_monthly) }}</span>
                                    <span class="text-sm text-gray-500 font-medium">/mo</span>
                                </div>

                                <!-- Yearly Price with Discount -->
                                <div class="flex items-center justify-center gap-2">
                                    <span class="text-sm text-gray-600">
                                        {{ formatCurrency(plan.price_yearly) }}/yr
                                    </span>
                                    <span v-if="plan.yearly_discount > 0" class="badge badge-sm badge-success">
                                        Save {{ plan.yearly_discount }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Limits Section -->
                            <div class="px-6 py-4 bg-gray-50/50 border-y border-gray-100">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-7 h-7 rounded-md bg-primary/10">
                                            <i class="ki-filled ki-people text-primary text-sm"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Users</span>
                                            <span class="text-sm font-semibold text-gray-800">{{ getLimitDisplay(plan.max_users) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-7 h-7 rounded-md bg-info/10">
                                            <i class="ki-filled ki-package text-info text-sm"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Products</span>
                                            <span class="text-sm font-semibold text-gray-800">{{ getLimitDisplay(plan.max_products) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-7 h-7 rounded-md bg-success/10">
                                            <i class="ki-filled ki-profile-user text-success text-sm"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Customers</span>
                                            <span class="text-sm font-semibold text-gray-800">{{ getLimitDisplay(plan.max_customers) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-7 h-7 rounded-md bg-warning/10">
                                            <i class="ki-filled ki-cart text-warning text-sm"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Orders/mo</span>
                                            <span class="text-sm font-semibold text-gray-800">{{ getLimitDisplay(plan.max_monthly_orders) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Features List -->
                            <div class="p-6 flex-grow">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Features</h4>
                                <ul v-if="getPlanFeatures(plan).length > 0" class="space-y-2.5">
                                    <li
                                        v-for="(feature, index) in getPlanFeatures(plan).slice(0, 6)"
                                        :key="index"
                                        class="flex items-start gap-2.5"
                                    >
                                        <i class="ki-filled ki-check-circle text-success text-sm mt-0.5 shrink-0"></i>
                                        <span class="text-sm text-gray-700">{{ feature }}</span>
                                    </li>
                                    <li v-if="getPlanFeatures(plan).length > 6" class="text-sm text-gray-500 pl-6">
                                        +{{ getPlanFeatures(plan).length - 6 }} more features
                                    </li>
                                </ul>
                                <p v-else class="text-sm text-gray-400 italic">No features specified</p>
                            </div>

                            <!-- Companies Count -->
                            <div class="px-6 pb-4">
                                <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-gray-50 border border-gray-100">
                                    <i class="ki-filled ki-abstract-26 text-gray-400 text-sm"></i>
                                    <span class="text-sm font-medium text-gray-600">
                                        {{ plan.companies_count }} {{ plan.companies_count === 1 ? 'company' : 'companies' }} using this plan
                                    </span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="p-4 pt-0 flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openEditModal(plan)"
                                    class="btn btn-sm btn-light flex-1 hover:bg-primary hover:border-primary hover:text-white transition-colors"
                                >
                                    <i class="ki-filled ki-pencil me-1.5"></i>
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="toggleStatus(plan)"
                                    :class="[
                                        'btn btn-sm btn-light flex-1 transition-colors',
                                        plan.is_active
                                            ? 'hover:bg-warning hover:border-warning hover:text-white'
                                            : 'hover:bg-success hover:border-success hover:text-white'
                                    ]"
                                >
                                    <i :class="[
                                        'me-1.5',
                                        plan.is_active ? 'ki-filled ki-cross-circle' : 'ki-filled ki-check-circle'
                                    ]"></i>
                                    {{ plan.is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button
                                    type="button"
                                    @click="deletePlan(plan)"
                                    class="btn btn-sm btn-icon btn-light hover:bg-danger hover:border-danger hover:text-white transition-colors"
                                    title="Delete"
                                >
                                    <i class="ki-filled ki-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Modal -->
        <PlanModal
            :show="showModal"
            :plan="editingPlan"
            @close="closeModal"
            @saved="handlePlanSaved"
        />
    </AppLayout>
</template>
