<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import DatePicker from '@/Components/Metronic/DatePicker.vue';

const props = defineProps({
    fiscalYears: {
        type: Array,
        default: () => []
    },
    currentPeriod: {
        type: Object,
        default: null
    },
});

const page = usePage();

// Permission check helper
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Track expanded fiscal years (accordion state)
const expandedYears = ref([]);

// Toggle accordion expansion
const toggleYear = (yearId) => {
    const index = expandedYears.value.indexOf(yearId);
    if (index === -1) {
        expandedYears.value.push(yearId);
    } else {
        expandedYears.value.splice(index, 1);
    }
};

const isYearExpanded = (yearId) => {
    return expandedYears.value.includes(yearId);
};

// Modal states
const showCreateModal = ref(false);
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmData = ref(null);

// Form data for creating fiscal year
const form = ref({
    name: '',
    start_date: '',
    end_date: '',
    set_as_current: false,
});

const errors = ref({});
const isSubmitting = ref(false);

// Open create modal
const openCreateModal = () => {
    form.value = {
        name: '',
        start_date: '',
        end_date: '',
        set_as_current: false,
    };
    errors.value = {};
    showCreateModal.value = true;
    nextTick(() => {
        KTComponents.init();
    });
};

// Close create modal
const closeCreateModal = () => {
    showCreateModal.value = false;
    form.value = {
        name: '',
        start_date: '',
        end_date: '',
        set_as_current: false,
    };
    errors.value = {};
};

// Submit create fiscal year form
const submitCreateForm = async () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/finance/fiscal-periods/api/store', form.value);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Fiscal year created successfully',
        });

        closeCreateModal();
        router.reload({ only: ['fiscalYears', 'currentPeriod'] });
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || 'Failed to create fiscal year',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Confirmation modal actions
const openConfirmModal = (action, data) => {
    confirmAction.value = action;
    confirmData.value = data;
    showConfirmModal.value = true;
};

const closeConfirmModal = () => {
    showConfirmModal.value = false;
    confirmAction.value = null;
    confirmData.value = null;
};

// Get confirmation modal title
const confirmModalTitle = computed(() => {
    switch (confirmAction.value) {
        case 'set_current_year':
            return 'Set as Current Fiscal Year';
        case 'close_year':
            return 'Close Fiscal Year';
        case 'close_period':
            return 'Close Period';
        case 'reopen_period':
            return 'Reopen Period';
        default:
            return 'Confirm Action';
    }
});

// Get confirmation modal message
const confirmModalMessage = computed(() => {
    switch (confirmAction.value) {
        case 'set_current_year':
            return `Are you sure you want to set "${confirmData.value?.name}" as the current fiscal year? This will change the active fiscal year for all transactions.`;
        case 'close_year':
            return `Are you sure you want to close "${confirmData.value?.name}"? All periods within this fiscal year will be closed and no further transactions can be posted.`;
        case 'close_period':
            return `Are you sure you want to close "${confirmData.value?.name}"? No further transactions can be posted to this period.`;
        case 'reopen_period':
            return `Are you sure you want to reopen "${confirmData.value?.name}"? This will allow transactions to be posted to this period again.`;
        default:
            return 'Are you sure you want to proceed with this action?';
    }
});

// Get confirmation button text
const confirmButtonText = computed(() => {
    switch (confirmAction.value) {
        case 'set_current_year':
            return 'Set as Current';
        case 'close_year':
        case 'close_period':
            return 'Close';
        case 'reopen_period':
            return 'Reopen';
        default:
            return 'Confirm';
    }
});

// Get confirmation button class
const confirmButtonClass = computed(() => {
    switch (confirmAction.value) {
        case 'close_year':
        case 'close_period':
            return 'btn-warning';
        case 'reopen_period':
            return 'btn-success';
        default:
            return 'btn-primary';
    }
});

// Execute confirmed action
const executeConfirmedAction = async () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;

    try {
        let endpoint = '';
        let method = 'patch';

        switch (confirmAction.value) {
            case 'set_current_year':
                endpoint = `/finance/fiscal-periods/api/set-current-year/${confirmData.value.id}`;
                break;
            case 'close_year':
                endpoint = `/finance/fiscal-periods/api/close-year/${confirmData.value.id}`;
                break;
            case 'close_period':
                endpoint = `/finance/fiscal-periods/api/close-period/${confirmData.value.id}`;
                break;
            case 'reopen_period':
                endpoint = `/finance/fiscal-periods/api/reopen-period/${confirmData.value.id}`;
                break;
        }

        const response = await axios[method](endpoint);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Action completed successfully',
        });

        closeConfirmModal();
        router.reload({ only: ['fiscalYears', 'currentPeriod'] });
    } catch (err) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || 'Failed to complete action',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Get status badge class based on status color
const getStatusBadgeClass = (statusColor) => {
    const colorMap = {
        'success': 'badge-success',
        'warning': 'badge-warning',
        'danger': 'badge-danger',
        'info': 'badge-info',
        'primary': 'badge-primary',
    };
    return colorMap[statusColor] || 'badge-light';
};

// Check if period can be reopened
const canReopenPeriod = (period, fiscalYear) => {
    // Can only reopen if the period is closed and the fiscal year is still open
    return period.status === 'closed' && fiscalYear.status === 'open';
};

// Check if period can be closed
const canClosePeriod = (period) => {
    return period.status === 'open';
};

// Check if fiscal year can be closed
const canCloseYear = (year) => {
    return year.status === 'open';
};
</script>

<template>
    <AppLayout title="Fiscal Periods">
        <div class="container-fixed py-5">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                        <a href="/" class="hover:text-primary">Home</a>
                        <i class="ki-filled ki-right text-xs text-gray-400"></i>
                        <span>Finance</span>
                        <i class="ki-filled ki-right text-xs text-gray-400"></i>
                        <span class="text-gray-900">Fiscal Periods</span>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900">Fiscal Periods</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage fiscal years and accounting periods</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="btn btn-primary"
                >
                    <i class="ki-filled ki-plus me-2"></i>
                    Create Fiscal Year
                </button>
            </div>

            <!-- Current Period Info Card -->
            <div v-if="currentPeriod" class="card mb-5 border-l-4 border-l-success">
                <div class="card-body p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
                            <i class="ki-filled ki-calendar text-success text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-gray-500">Current Posting Period</span>
                                <span class="badge badge-sm badge-success">Active</span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ currentPeriod.name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ currentPeriod.fiscal_year }} | {{ currentPeriod.date_range }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-sm" :class="getStatusBadgeClass(currentPeriod.status_color || 'success')">
                                {{ currentPeriod.status_label || currentPeriod.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Current Period Warning -->
            <div v-else class="card mb-5 border-l-4 border-l-warning">
                <div class="card-body p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-warning/10 flex items-center justify-center">
                            <i class="ki-filled ki-information-2 text-warning text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">No Active Posting Period</h3>
                            <p class="text-sm text-gray-500">Please create a fiscal year and set a current period to enable transaction posting.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fiscal Years Accordion -->
            <div class="space-y-4">
                <div v-for="year in fiscalYears" :key="year.id" class="card">
                    <!-- Fiscal Year Header (Accordion Toggle) -->
                    <div
                        class="card-header cursor-pointer hover:bg-gray-50 transition-colors"
                        @click="toggleYear(year.id)"
                    >
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-calendar text-primary text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-semibold text-gray-900">{{ year.name }}</h3>
                                    <span
                                        v-if="year.is_current"
                                        class="badge badge-sm badge-primary"
                                    >
                                        Current
                                    </span>
                                    <span
                                        class="badge badge-sm"
                                        :class="getStatusBadgeClass(year.status_color)"
                                    >
                                        {{ year.status_label || year.status }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500">{{ year.date_range }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Year Actions -->
                            <div class="flex items-center gap-2" @click.stop>
                                <button
                                    v-if="!year.is_current && year.status === 'open'"
                                    @click="openConfirmModal('set_current_year', year)"
                                    class="btn btn-sm btn-light"
                                    title="Set as Current"
                                >
                                    <i class="ki-filled ki-check-circle me-1"></i>
                                    Set Current
                                </button>
                                <button
                                    v-if="canCloseYear(year)"
                                    @click="openConfirmModal('close_year', year)"
                                    class="btn btn-sm btn-light-warning"
                                    title="Close Fiscal Year"
                                >
                                    <i class="ki-filled ki-lock me-1"></i>
                                    Close Year
                                </button>
                            </div>
                            <!-- Expand/Collapse Icon -->
                            <i
                                class="ki-filled text-gray-400 transition-transform"
                                :class="isYearExpanded(year.id) ? 'ki-up rotate-0' : 'ki-down'"
                            ></i>
                        </div>
                    </div>

                    <!-- Fiscal Year Periods Table (Accordion Content) -->
                    <div
                        v-show="isYearExpanded(year.id)"
                        class="card-body p-0 border-t border-gray-200"
                    >
                        <div class="scrollable-x-auto">
                            <table class="table table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[50px] text-center">#</th>
                                        <th class="min-w-[200px]">Period Name</th>
                                        <th class="min-w-[200px]">Date Range</th>
                                        <th class="w-[120px] text-center">Status</th>
                                        <th class="w-[100px] text-center">Postable</th>
                                        <th class="w-[120px] text-center">Type</th>
                                        <th class="w-[150px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="period in year.periods" :key="period.id">
                                        <td class="text-center text-sm text-gray-500">
                                            {{ period.period_number }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <i class="ki-filled ki-calendar text-gray-400"></i>
                                                <span class="font-medium text-gray-900">{{ period.name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-sm text-gray-600">
                                            {{ period.date_range }}
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge badge-sm"
                                                :class="getStatusBadgeClass(period.status_color)"
                                            >
                                                {{ period.status_label || period.status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="period.is_postable" class="text-success">
                                                <i class="ki-filled ki-check-circle"></i>
                                            </span>
                                            <span v-else class="text-gray-400">
                                                <i class="ki-filled ki-cross-circle"></i>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                v-if="period.is_adjusting_period"
                                                class="badge badge-sm badge-info"
                                            >
                                                Adjusting
                                            </span>
                                            <span v-else class="text-sm text-gray-500">Regular</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button
                                                    v-if="canClosePeriod(period)"
                                                    @click="openConfirmModal('close_period', period)"
                                                    class="btn btn-xs btn-icon btn-light-warning"
                                                    title="Close Period"
                                                >
                                                    <i class="ki-filled ki-lock"></i>
                                                </button>
                                                <button
                                                    v-if="canReopenPeriod(period, year)"
                                                    @click="openConfirmModal('reopen_period', period)"
                                                    class="btn btn-xs btn-icon btn-light-success"
                                                    title="Reopen Period"
                                                >
                                                    <i class="ki-filled ki-lock-3"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!year.periods || year.periods.length === 0">
                                        <td colspan="7" class="text-center py-8">
                                            <div class="flex flex-col items-center">
                                                <i class="ki-filled ki-calendar text-4xl text-gray-300 mb-3"></i>
                                                <p class="text-gray-500">No periods found for this fiscal year</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!fiscalYears || fiscalYears.length === 0" class="card">
                    <div class="card-body p-12">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <i class="ki-filled ki-calendar text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Fiscal Years</h3>
                            <p class="text-gray-500 mb-4 max-w-md">
                                Get started by creating your first fiscal year. This will automatically generate monthly accounting periods.
                            </p>
                            <button
                                @click="openCreateModal"
                                class="btn btn-primary"
                            >
                                <i class="ki-filled ki-plus me-2"></i>
                                Create Fiscal Year
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Fiscal Year Modal -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-[100] flex items-start justify-center pt-[10%] px-4"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50"
                    @click="closeCreateModal"
                ></div>

                <!-- Modal Content -->
                <div class="modal-content max-w-[500px] w-full relative z-10 bg-white rounded-lg shadow-xl">
                    <form @submit.prevent="submitCreateForm">
                        <div class="modal-header flex items-center justify-between p-5 border-b border-gray-200">
                            <h3 class="modal-title text-lg font-semibold text-gray-900">
                                Create Fiscal Year
                            </h3>
                            <button
                                type="button"
                                class="btn btn-xs btn-icon btn-light"
                                @click="closeCreateModal"
                            >
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>

                        <div class="modal-body p-5">
                            <!-- Error Alert -->
                            <div
                                v-if="Object.keys(errors).length"
                                class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded"
                            >
                                <p class="font-medium mb-2">Please fix the following errors:</p>
                                <ul class="list-disc pl-5 text-sm">
                                    <li v-for="(messages, field) in errors" :key="field">
                                        <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="form-label mb-2">
                                        Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="input"
                                        placeholder="e.g., FY 2026"
                                        :class="{ 'border-red-400': errors.name }"
                                    />
                                    <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <label class="form-label mb-2">
                                        Start Date
                                        <span class="text-danger">*</span>
                                    </label>
                                    <DatePicker
                                        v-model="form.start_date"
                                        placeholder="Select start date"
                                        format="YYYY-MM-DD"
                                        displayFormat="DD MMM YYYY"
                                    />
                                    <p v-if="errors.start_date" class="text-red-500 text-xs mt-1">{{ errors.start_date[0] }}</p>
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label class="form-label mb-2">
                                        End Date
                                        <span class="text-danger">*</span>
                                    </label>
                                    <DatePicker
                                        v-model="form.end_date"
                                        placeholder="Select end date"
                                        format="YYYY-MM-DD"
                                        displayFormat="DD MMM YYYY"
                                        :minDate="form.start_date"
                                    />
                                    <p v-if="errors.end_date" class="text-red-500 text-xs mt-1">{{ errors.end_date[0] }}</p>
                                </div>

                                <!-- Set as Current -->
                                <div class="flex items-center gap-3 pt-2">
                                    <label class="switch switch-sm">
                                        <input
                                            v-model="form.set_as_current"
                                            type="checkbox"
                                            class="order-2"
                                        />
                                        <span class="switch-label order-1">
                                            Set as current fiscal year
                                        </span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    If enabled, this fiscal year will be set as the active year for transaction posting.
                                </p>
                            </div>
                        </div>

                        <div class="modal-footer flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                            <button
                                type="button"
                                class="btn btn-light"
                                @click="closeCreateModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="isSubmitting"
                            >
                                <span v-if="isSubmitting" class="flex items-center gap-2">
                                    <i class="ki-filled ki-loading animate-spin"></i>
                                    Creating...
                                </span>
                                <span v-else>
                                    <i class="ki-filled ki-plus me-1"></i>
                                    Create Fiscal Year
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showConfirmModal"
                class="fixed inset-0 z-[100] flex items-start justify-center pt-[15%] px-4"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50"
                    @click="closeConfirmModal"
                ></div>

                <!-- Modal Content -->
                <div class="modal-content max-w-[400px] w-full relative z-10 bg-white rounded-lg shadow-xl">
                    <div class="modal-header flex items-center justify-between p-5 border-b border-gray-200">
                        <h3 class="modal-title text-lg font-semibold text-gray-900">
                            {{ confirmModalTitle }}
                        </h3>
                        <button
                            type="button"
                            class="btn btn-xs btn-icon btn-light"
                            @click="closeConfirmModal"
                        >
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>

                    <div class="modal-body p-5">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="{
                                    'bg-warning/10': confirmAction === 'close_year' || confirmAction === 'close_period',
                                    'bg-success/10': confirmAction === 'reopen_period',
                                    'bg-primary/10': confirmAction === 'set_current_year',
                                }"
                            >
                                <i
                                    class="ki-filled text-xl"
                                    :class="{
                                        'ki-lock text-warning': confirmAction === 'close_year' || confirmAction === 'close_period',
                                        'ki-lock-3 text-success': confirmAction === 'reopen_period',
                                        'ki-check-circle text-primary': confirmAction === 'set_current_year',
                                    }"
                                ></i>
                            </div>
                            <div>
                                <p class="text-gray-600">{{ confirmModalMessage }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                        <button
                            type="button"
                            class="btn btn-light"
                            @click="closeConfirmModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="confirmButtonClass"
                            :disabled="isSubmitting"
                            @click="executeConfirmedAction"
                        >
                            <span v-if="isSubmitting" class="flex items-center gap-2">
                                <i class="ki-filled ki-loading animate-spin"></i>
                                Processing...
                            </span>
                            <span v-else>{{ confirmButtonText }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
