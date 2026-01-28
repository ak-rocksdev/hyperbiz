<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, watch, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import { KTModal } from '../../../metronic/core/components/modal';

// Props from controller
const props = defineProps({
    productCategories: Array,
    parentOptions: Array,
    pagination: Object,
    filters: Object,
    stats: Object,
});

// Search and pagination state
const searchQuery = ref(props.filters?.search || '');
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [
    { value: 10, label: '10' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
];
const selectedPerPage = ref(props.pagination?.per_page || 10);

// Form state for create modal
const form = ref({
    name: '',
    parent_id: '',
});
const isLoading = ref(false);

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Fetch data with filters
const fetchData = () => {
    router.get(route('product-category.list'), {
        search: searchQuery.value || undefined,
        per_page: selectedPerPage.value,
        page: currentPage.value,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Watch for pagination changes
watch([currentPage, selectedPerPage], () => {
    fetchData();
});

// Perform search
const performSearch = () => {
    currentPage.value = 1;
    fetchData();
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
    currentPage.value = 1;
    fetchData();
};

// Create new category
const submitForm = () => {
    isLoading.value = true;

    axios.post('/product-category/api/store', {
        name: form.value.name,
        parent_id: form.value.parent_id || null,
    })
    .then((response) => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message
        });

        form.value = { name: '', parent_id: '' };
        closeModal('modal_create_new_category');
        fetchData();
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

// Delete category
const deleteCategory = (category) => {
    Swal.fire({
        title: 'Delete Category?',
        text: `Are you sure you want to delete "${category.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/product-category/api/delete/${category.id}`)
                .then(response => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.data.message
                    });
                    fetchData();
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        text: error.response?.data?.message || 'Failed to delete category.'
                    });
                });
        }
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
    if (document.querySelector('.modal-backdrop')) {
        document.querySelector('.modal-backdrop').remove();
    }
};

onMounted(() => {
    KTModal.init();
});
</script>

<template>
    <AppLayout title="Product Categories">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Product Categories
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <!-- Stats Summary Card -->
            <div class="py-5">
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-center gap-5">
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-light">
                                    <i class="ki-filled ki-folder text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_categories) }}</div>
                                    <div class="text-xs text-gray-500">Total Categories</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Data Card -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-4">
                        <h3 class="card-title">Product Categories</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Search Input -->
                            <div class="relative">
                                <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                                <input
                                    type="text"
                                    class="input input-sm pl-9 w-[200px]"
                                    placeholder="Search categories..."
                                    v-model="searchQuery"
                                    @keyup.enter="performSearch"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="clearSearch"
                                    class="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <i class="ki-filled ki-cross text-sm"></i>
                                </button>
                            </div>

                            <!-- Add New Button -->
                            <button class="btn btn-sm btn-primary" data-modal-toggle="#modal_create_new_category">
                                <i class="ki-filled ki-plus-squared me-1"></i>
                                New Category
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[50px] text-center">#</th>
                                        <th class="min-w-[250px]">Name</th>
                                        <th class="w-[180px]">Parent Category</th>
                                        <th class="w-[120px] text-center">Products</th>
                                        <th class="w-[150px] text-center">Created At</th>
                                        <th class="w-[80px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!productCategories || productCategories.length === 0">
                                        <td colspan="6">
                                            <div class="flex flex-col items-center justify-center py-10">
                                                <i class="ki-filled ki-folder text-6xl text-gray-300 mb-3"></i>
                                                <span class="text-gray-500">No categories found</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else v-for="(category, index) in productCategories" :key="category.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                        <td class="text-center text-gray-600">
                                            {{ (pagination?.from || 0) + index }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                                    {{ category.name?.charAt(0)?.toUpperCase() || 'C' }}
                                                </div>
                                                <Link :href="`/product-category/detail/${category.id}`" class="text-sm font-medium text-gray-900 hover:text-primary">
                                                    {{ category.name }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="category.parent_name !== '-'" class="badge badge-sm badge-outline badge-secondary">
                                                {{ category.parent_name }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm badge-outline" :class="category.products_count > 0 ? 'badge-primary' : 'badge-secondary'">
                                                {{ formatNumber(category.products_count) }} products
                                            </span>
                                        </td>
                                        <td class="text-center text-gray-600">
                                            {{ category.created_at }}
                                        </td>
                                        <td class="text-center">
                                            <div class="menu flex-inline justify-center" data-menu="true">
                                                <div class="menu-item" data-menu-item-offset="0, 10px"
                                                    data-menu-item-placement="bottom-end"
                                                    data-menu-item-toggle="dropdown"
                                                    data-menu-item-trigger="click|lg:click">
                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                        <i class="ki-filled ki-dots-vertical"></i>
                                                    </button>
                                                    <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/product-category/detail/${category.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/product-category/edit/${category.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Edit</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-separator"></div>
                                                        <div class="menu-item">
                                                            <span class="menu-link cursor-pointer" @click="deleteCategory(category)">
                                                                <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
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

                        <!-- Pagination Footer -->
                        <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Show
                                <SearchableSelect
                                    v-model="selectedPerPage"
                                    :options="perPageOptions"
                                    placeholder="10"
                                    class="w-20"
                                    :clearable="false"
                                />
                                per page
                            </div>
                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span>
                                    Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} results
                                </span>
                                <div class="pagination flex items-center gap-1">
                                    <button
                                        class="btn btn-sm btn-icon btn-light"
                                        :disabled="currentPage <= 1"
                                        @click="currentPage--"
                                    >
                                        <i class="ki-filled ki-arrow-left"></i>
                                    </button>
                                    <template v-for="page in pagination?.last_page" :key="page">
                                        <button
                                            v-if="page === 1 || page === pagination?.last_page || (page >= currentPage - 1 && page <= currentPage + 1)"
                                            class="btn btn-sm btn-icon"
                                            :class="page === currentPage ? 'btn-primary' : 'btn-light'"
                                            @click="currentPage = page"
                                        >
                                            {{ page }}
                                        </button>
                                        <span v-else-if="page === currentPage - 2 || page === currentPage + 2" class="text-gray-400">...</span>
                                    </template>
                                    <button
                                        class="btn btn-sm btn-icon btn-light"
                                        :disabled="currentPage >= pagination?.last_page"
                                        @click="currentPage++"
                                    >
                                        <i class="ki-filled ki-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create New Category Modal -->
            <div id="modal_create_new_category" data-modal="true" class="modal">
                <div class="modal-dialog">
                    <div class="modal-content max-w-[500px] top-[15%]">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Category</h5>
                            <button type="button" class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>
                        <form @submit.prevent="submitForm">
                            <div class="modal-body py-5">
                                <!-- Category Name -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">
                                        Category Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="input w-full"
                                        v-model="form.name"
                                        placeholder="Enter category name"
                                        required
                                    />
                                </div>

                                <!-- Parent Category -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">Parent Category</label>
                                    <SearchableSelect
                                        v-model="form.parent_id"
                                        :options="parentOptions"
                                        placeholder="Select parent category (optional)"
                                        :clearable="true"
                                    />
                                    <span class="text-xs text-gray-500 mt-1">
                                        Leave empty for a top-level category.
                                    </span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ isLoading ? 'Creating...' : 'Create Category' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
