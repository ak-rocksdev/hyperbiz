<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Get props from controller
const props = defineProps({
    category: Object,
    parentOptions: Array,
});

// Form state - initialize with category data
const form = ref({
    id: props.category.id,
    name: props.category.name,
    parent_id: props.category.parent_id || '',
});

const isLoading = ref(false);

// Update category via API
const updateCategory = () => {
    isLoading.value = true;

    axios.put(`/product-category/api/update/${form.value.id}`, {
        name: form.value.name,
        parent_id: form.value.parent_id || null,
    })
    .then(response => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Category updated successfully'
        });
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
    <AppLayout title="Edit Category">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/product-category/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Category
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
                                <i class="ki-filled ki-folder text-gray-500 me-2"></i>
                                Category Information
                            </h3>
                        </div>
                        <form class="card-body" @submit.prevent="updateCategory">
                            <!-- Category Name Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700">
                                    Category Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    class="input w-full"
                                    type="text"
                                    v-model="form.name"
                                    placeholder="Enter category name"
                                    required
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    The unique name for this product category.
                                </span>
                            </div>

                            <!-- Parent Category Field -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700">
                                    Parent Category
                                </label>
                                <SearchableSelect
                                    v-model="form.parent_id"
                                    :options="parentOptions"
                                    placeholder="Select parent category (optional)"
                                    :clearable="true"
                                />
                                <span class="text-xs text-gray-500 mt-1">
                                    Leave empty for a top-level category. Select a parent to create a subcategory.
                                </span>
                            </div>

                            <!-- Form Footer -->
                            <div class="flex justify-between pt-5 border-t border-gray-200">
                                <Link href="/product-category/list" class="btn btn-light">
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
                                    <i class="ki-filled ki-package text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ category.products_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Products in Category</div>
                                </div>
                            </div>
                            <div v-if="category.parent_name" class="mt-4 p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="ki-filled ki-folder text-blue-600"></i>
                                    <span class="text-sm text-blue-800">
                                        Current Parent: <strong>{{ category.parent_name }}</strong>
                                    </span>
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
                            <!-- View Category Detail -->
                            <Link :href="`/product-category/detail/${form.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-eye me-2"></i>
                                View Category Detail
                            </Link>

                            <!-- Back to Category List -->
                            <Link href="/product-category/list" class="btn btn-light w-full">
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
                                <p><strong>Category Name:</strong> Must be unique across all categories.</p>
                                <p><strong>Parent Category:</strong> Optional. Selecting a parent creates a hierarchical structure.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
