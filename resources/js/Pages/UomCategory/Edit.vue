<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Get props from controller
const props = defineProps({
    category: Object,
});

// Form state - initialize with category data
const form = ref({
    id: props.category.id,
    code: props.category.code,
    name: props.category.name,
    description: props.category.description || '',
    is_active: props.category.is_active,
});

const isLoading = ref(false);

// Update category via API
const updateCategory = () => {
    isLoading.value = true;

    axios.put(`/uom-category/api/update/${form.value.id}`, {
        code: form.value.code,
        name: form.value.name,
        description: form.value.description || null,
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
            text: response.data.message || 'UoM Category updated successfully'
        });

        // Navigate back to list after success
        window.location.href = '/uom-category/list';
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
    <AppLayout title="Edit UoM Category">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/uom-category/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit UoM Category
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
                                Category Information
                            </h3>
                        </div>
                        <form class="card-body" @submit.prevent="updateCategory">
                            <!-- Code Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Code <span class="text-danger">*</span>
                                </label>
                                <input
                                    class="input w-full"
                                    type="text"
                                    v-model="form.code"
                                    placeholder="Enter category code"
                                    required
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    A unique code for this UoM category (e.g., WEIGHT, LENGTH, VOLUME).
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
                                    placeholder="Enter category name"
                                    required
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    The display name for this UoM category.
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
                                    placeholder="Enter category description (optional)"
                                    rows="3"
                                ></textarea>
                                <span class="text-xs text-gray-500 mt-1">
                                    An optional description explaining this category's purpose.
                                </span>
                            </div>

                            <!-- Is Active Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 mb-2">
                                    Status
                                </label>
                                <div class="flex items-center gap-3">
                                    <label class="switch switch-sm">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            :true-value="1"
                                            :false-value="0"
                                        />
                                        <span class="switch-label">
                                            {{ form.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </label>
                                </div>
                                <span class="text-xs text-gray-500 mt-1">
                                    Inactive categories will not be available for selection.
                                </span>
                            </div>

                            <!-- Form Footer -->
                            <div class="flex justify-between pt-5 border-t border-gray-200">
                                <Link href="/uom-category/list" class="btn btn-light">
                                    <i class="ki-filled ki-arrow-left me-1"></i> Cancel
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
                                Category Statistics
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-light">
                                    <i class="ki-filled ki-element-11 text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ category.uoms_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Units of Measure</div>
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
                            <!-- Back to Category List -->
                            <Link href="/uom-category/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to Category List
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
                                <p><strong>Code:</strong> A unique identifier for the category (e.g., WEIGHT, LENGTH).</p>
                                <p><strong>Name:</strong> The display name shown throughout the application.</p>
                                <p><strong>Description:</strong> Optional text to describe the category's purpose.</p>
                                <p><strong>Status:</strong> Inactive categories cannot be used when creating new UoMs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
