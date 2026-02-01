<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, watch, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Get props from controller
const props = defineProps({
    uom: Object,
    categories: Array,
    baseUomOptions: Array,
});

// Form state - initialize with uom data
const form = ref({
    id: props.uom.id,
    code: props.uom.code,
    name: props.uom.name,
    description: props.uom.description || '',
    category_id: props.uom.category_id,
    base_uom_id: props.uom.base_uom_id || '',
    conversion_factor: props.uom.conversion_factor || '',
    is_active: props.uom.is_active,
});

const isLoading = ref(false);
const availableBaseUoms = ref(props.baseUomOptions || []);
const loadingBaseUoms = ref(false);

// Computed property to check if conversion factor should be shown
const showConversionFactor = computed(() => {
    return form.value.base_uom_id !== '' && form.value.base_uom_id !== null;
});

// Watch for category changes to fetch base UoMs
watch(() => form.value.category_id, async (newCategoryId, oldCategoryId) => {
    if (newCategoryId && newCategoryId !== oldCategoryId) {
        // Reset base_uom_id when category changes
        form.value.base_uom_id = '';
        form.value.conversion_factor = '';

        await fetchBaseUoms(newCategoryId);
    } else if (!newCategoryId) {
        availableBaseUoms.value = [];
        form.value.base_uom_id = '';
        form.value.conversion_factor = '';
    }
});

// Fetch base UoMs for a category
const fetchBaseUoms = async (categoryId) => {
    loadingBaseUoms.value = true;
    try {
        const response = await axios.get(`/uom/api/base-uoms/${categoryId}`);
        // Filter out current UoM from base options
        availableBaseUoms.value = response.data.baseUoms.filter(
            uom => uom.value !== form.value.id
        );
    } catch (error) {
        console.error('Failed to fetch base UoMs:', error);
        availableBaseUoms.value = [];
    } finally {
        loadingBaseUoms.value = false;
    }
};

// Update UoM via API
const updateUom = () => {
    isLoading.value = true;

    axios.put(`/uom/api/update/${form.value.id}`, {
        code: form.value.code,
        name: form.value.name,
        description: form.value.description || null,
        category_id: form.value.category_id,
        base_uom_id: form.value.base_uom_id || null,
        conversion_factor: form.value.base_uom_id ? (form.value.conversion_factor || 1) : null,
        is_active: form.value.is_active,
    })
    .then(response => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'UoM updated successfully'
        });

        // Navigate back to list
        window.location.href = '/uom/list';
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'An error occurred.',
        });
    })
    .finally(() => {
        isLoading.value = false;
    });
};
</script>

<template>
    <AppLayout title="Edit UoM">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/uom/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit UoM
                </h2>
            </div>
        </template>

        <!-- Main Content Container -->
        <div class="container-fixed py-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Left Column: Form (2/3 width) -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-abstract-26 text-gray-500 me-2"></i>
                                UoM Information
                            </h3>
                        </div>
                        <form class="card-body" @submit.prevent="updateUom">
                            <!-- Code Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Code <span class="text-danger">*</span>
                                </label>
                                <input
                                    class="input w-full"
                                    type="text"
                                    v-model="form.code"
                                    placeholder="Enter UoM code (e.g., KG, PCS)"
                                    required
                                    maxlength="10"
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    A short unique code for this unit of measure (max 10 characters).
                                </span>
                            </div>

                            <!-- Name Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    class="input w-full"
                                    type="text"
                                    v-model="form.name"
                                    placeholder="Enter UoM name (e.g., Kilogram, Pieces)"
                                    required
                                    maxlength="50"
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    The full name for this unit of measure.
                                </span>
                            </div>

                            <!-- Category Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="form.category_id"
                                    :options="categories"
                                    placeholder="Select category"
                                    :clearable="false"
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    The category this UoM belongs to (e.g., Weight, Length, Volume).
                                </span>
                            </div>

                            <!-- Base UoM Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Base UoM
                                </label>
                                <SearchableSelect
                                    v-model="form.base_uom_id"
                                    :options="availableBaseUoms"
                                    placeholder="Select base UoM (optional)"
                                    :clearable="true"
                                    :disabled="loadingBaseUoms || !form.category_id"
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    <template v-if="loadingBaseUoms">Loading base UoMs...</template>
                                    <template v-else-if="!form.category_id">Select a category first to see available base UoMs.</template>
                                    <template v-else>Leave empty if this is a base unit. Select a base UoM if this is a derived unit.</template>
                                </span>
                            </div>

                            <!-- Conversion Factor Field (shown only if Base UoM is selected) -->
                            <div v-if="showConversionFactor" class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Conversion Factor <span class="text-danger">*</span>
                                </label>
                                <input
                                    class="input w-full"
                                    type="number"
                                    v-model="form.conversion_factor"
                                    placeholder="Enter conversion factor"
                                    step="any"
                                    min="0"
                                    required
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    How many base units equal 1 of this unit? (e.g., if base is KG and this is Gram, enter 0.001)
                                </span>
                            </div>

                            <!-- Description Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Description
                                </label>
                                <textarea
                                    class="textarea w-full"
                                    v-model="form.description"
                                    placeholder="Enter description (optional)"
                                    rows="3"
                                    maxlength="255"
                                ></textarea>
                                <span class="text-xs text-gray-500 mt-1">
                                    Optional description for this unit of measure.
                                </span>
                            </div>

                            <!-- Is Active Checkbox -->
                            <div class="mb-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="checkbox"
                                        v-model="form.is_active"
                                    />
                                    <span class="text-gray-700">Active</span>
                                </label>
                                <span class="text-xs text-gray-500 mt-1 block">
                                    Inactive UoMs cannot be used in new transactions.
                                </span>
                            </div>

                            <!-- Form Footer -->
                            <div class="flex justify-between pt-5 border-t border-gray-200">
                                <Link href="/uom/list" class="btn btn-light">
                                    <i class="ki-filled ki-arrow-left me-1"></i> Back
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <i v-if="!isLoading" class="ki-filled ki-check me-1"></i>
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ isLoading ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Info (1/3 width) -->
                <div class="space-y-5">
                    <!-- Statistics Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-chart-simple text-gray-500 me-2"></i>
                                Usage Statistics
                            </h3>
                        </div>
                        <div class="card-body space-y-4">
                            <!-- Products Count -->
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-light">
                                    <i class="ki-filled ki-package text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ uom.products_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Products (as base UoM)</div>
                                </div>
                            </div>

                            <!-- Product UoMs Count -->
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-success-light">
                                    <i class="ki-filled ki-element-11 text-success text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ uom.product_uoms_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Product UoM Configs</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-setting-2 text-gray-500 me-2"></i>
                                Actions
                            </h3>
                        </div>
                        <div class="card-body space-y-3">
                            <!-- Back to UoM List -->
                            <Link href="/uom/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to UoM List
                            </Link>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-information-2 text-gray-500 me-2"></i>
                                Help
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="text-sm text-gray-600 space-y-2">
                                <p><strong>Code:</strong> A short unique identifier (e.g., KG, PCS, L).</p>
                                <p><strong>Name:</strong> The full name of the unit (e.g., Kilogram, Pieces).</p>
                                <p><strong>Category:</strong> Groups related UoMs together (e.g., Weight, Volume).</p>
                                <p><strong>Base UoM:</strong> If set, this becomes a derived unit with conversion factor.</p>
                                <p><strong>Conversion Factor:</strong> The multiplier to convert to the base unit.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
