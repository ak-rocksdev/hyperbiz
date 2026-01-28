<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Get props from controller
const { props } = usePage();

// Form state - initialize with brand data from props
const form = ref({
    id: props.brand.id,
    name: props.brand.name,
});

// Loading state for save button
const isLoading = ref(false);

/**
 * Update brand via API
 * Uses PUT method to /brand/api/update/{id}
 */
const updateBrand = () => {
    isLoading.value = true;

    axios.put(`/brand/api/update/${form.value.id}`, {
        name: form.value.name,
    })
        .then(response => {
            // Show success toast notification
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message || 'Brand updated successfully'
            });
        })
        .catch(error => {
            // Show error alert
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'An error occurred while updating the brand.',
            });
            console.error('Error updating brand:', error);
        })
        .finally(() => {
            isLoading.value = false;
        });
};
</script>

<template>
    <AppLayout title="Edit Brand">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/brand/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Brand
                </h2>
            </div>
        </template>

        <!-- Main Content Container -->
        <div class="container-fixed py-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Left Column: Brand Form (2/3 width on large screens) -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-tag text-gray-500 me-2"></i>
                                Brand Information
                            </h3>
                        </div>
                        <form class="card-body" @submit.prevent="updateBrand">
                            <!-- Brand Name Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700 dark:text-gray-300">
                                    Brand Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    class="input w-full"
                                    placeholder="Enter Brand Name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    The name will be displayed in product listings and filters.
                                </span>
                            </div>

                            <!-- Form Footer with Save Button -->
                            <div class="flex justify-between pt-5 border-t border-gray-200 dark:border-gray-700">
                                <Link href="/brand/list" class="btn btn-light">
                                    <i class="ki-filled ki-arrow-left me-1"></i> Back
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <i v-if="!isLoading" class="ki-filled ki-check me-1"></i>
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                                    {{ isLoading ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Info & Actions (1/3 width on large screens) -->
                <div class="space-y-5">

                    <!-- Info Card: Products Count -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-information-2 text-gray-500 me-2"></i>
                                Brand Statistics
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    Products using this brand
                                </span>
                                <span class="badge badge-sm badge-primary">
                                    {{ props.brand.products_count || 0 }}
                                </span>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500">
                                    <i class="ki-filled ki-information-3 me-1"></i>
                                    This brand is used by <strong>{{ props.brand.products_count || 0 }}</strong> product(s) in your inventory.
                                </p>
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
                            <!-- Cancel / Back to List -->
                            <Link href="/brand/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to Brand List
                            </Link>

                            <!-- View Detail Link -->
                            <Link :href="`/brand/detail/${form.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-search-list me-2"></i>
                                View Brand Detail
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Spinner animation for loading state */
.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    vertical-align: -0.125em;
    border: 0.15em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

.spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
    border-width: 0.125em;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}
</style>
