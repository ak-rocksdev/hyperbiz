<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    brands: Array,
    pagination: Object,
    filters: Object,
    stats: Object,
});

// Reactive filters and pagination state
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
const form = ref({});
const selectedBrand = ref(null);

// Format helpers
const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Fetch data with filters (server-side)
const fetchData = () => {
    router.get(route('brand.list'), {
        search: searchQuery.value || undefined,
        per_page: selectedPerPage.value,
        page: currentPage.value,
    }, { preserveScroll: true, preserveState: true });
};

// Watch for pagination changes
watch([currentPage, selectedPerPage], () => fetchData());

// Perform search (reset to page 1)
const performSearch = () => {
    currentPage.value = 1;
    fetchData();
};

// Pagination helpers - visible pages computation
const visiblePages = computed(() => {
    const total = props.pagination?.last_page || 1;
    const current = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    if (current <= 4) return [1, 2, 3, 4, '...', total];
    if (current >= total - 3) return [1, '...', total - 3, total - 2, total - 1, total];
    return [1, '...', current - 1, current, current + 1, '...', total];
});

const goToPage = (page) => {
    if (page !== '...') currentPage.value = page;
};

// View brand detail (for quick view modal)
const viewBrandDetail = async (id) => {
    selectedBrand.value = null;
    try {
        const response = await axios.get(`/brand/api/detail/${id}`);
        selectedBrand.value = response.data.brand;
    } catch (error) {
        console.error('Error fetching brand details:', error);
    }
};

// Create brand
const submitForm = () => {
    axios.post('/brand/api/store', form.value)
        .then((response) => {
            // Reset form
            form.value = {};

            // Close modal
            const modalEl = document.querySelector('#modal_create_new_brand');
            const modal = KTModal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }

            // Remove backdrop if exists
            if (document.querySelector('.modal-backdrop')) {
                document.querySelector('.modal-backdrop').remove();
            }

            // Show success toast
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message
            });

            // Refresh data
            fetchData();
        })
        .catch((error) => {
            console.error('Error:', error);
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Something went wrong'
            });
        });
};
</script>

<template>
    <AppLayout title="Brands">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Brands
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <!-- Stats Summary Card -->
            <div class="py-5">
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-center gap-5">
                            <!-- Total Brands -->
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100">
                                    <i class="ki-filled ki-tag text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_brands) }}</div>
                                    <div class="text-xs text-gray-500">Total Brands</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header">
                        <h3 class="card-title">Brands</h3>
                        <div class="card-toolbar">
                            <div class="flex gap-3 items-center">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                    <input
                                        v-model="searchQuery"
                                        class="input input-sm ps-8 w-[200px]"
                                        placeholder="Search brand..."
                                        @keyup.enter="performSearch"
                                    />
                                </div>
                                <!-- Add Button -->
                                <button
                                    class="btn btn-sm btn-primary whitespace-nowrap"
                                    data-modal-toggle="#modal_create_new_brand">
                                    <i class="ki-filled ki-plus-squared me-1"></i>
                                    Add Brand
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[50px] text-center">#</th>
                                        <th class="min-w-[250px]">Name</th>
                                        <th class="w-[120px] text-center">Products</th>
                                        <th class="w-[150px] text-center">Created At</th>
                                        <th class="w-[80px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(brand, index) in brands" :key="brand.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                        <td class="text-center text-gray-500">
                                            {{ (pagination?.from || 0) + index }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <!-- Avatar -->
                                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                                    {{ brand.name?.charAt(0)?.toUpperCase() || 'B' }}
                                                </div>
                                                <!-- Name -->
                                                <div class="flex flex-col">
                                                    <Link
                                                        :href="'/brand/detail/' + brand.id"
                                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary">
                                                        {{ brand.name }}
                                                    </Link>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm badge-outline badge-secondary">
                                                {{ formatNumber(brand.products_count) }} products
                                            </span>
                                        </td>
                                        <td class="text-center text-gray-600">
                                            {{ formatDate(brand.created_at) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="menu flex-inline justify-center" data-menu="true">
                                                <div class="menu-item"
                                                    data-menu-item-offset="0, 10px"
                                                    data-menu-item-placement="bottom-end"
                                                    data-menu-item-toggle="dropdown"
                                                    data-menu-item-trigger="click|lg:click">
                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                        <i class="ki-filled ki-dots-vertical"></i>
                                                    </button>
                                                    <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="'/brand/detail/' + brand.id">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="'/brand/edit/' + brand.id">
                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Edit</span>
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Empty State -->
                                    <tr v-if="!brands || brands.length === 0">
                                        <td colspan="5" class="text-center">
                                            <div class="flex items-center justify-center h-40">
                                                <div class="flex flex-col items-center">
                                                    <i class="ki-filled ki-tag text-6xl text-gray-300 mb-3"></i>
                                                    <span class="text-gray-500">No brands found</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="card-footer justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                            <div class="flex items-center gap-2">
                                Show
                                <SearchableSelect
                                    v-model="selectedPerPage"
                                    :options="perPageOptions"
                                    :searchable="false"
                                    size="sm"
                                    class="w-[70px]"
                                />
                                per page
                            </div>
                            <span v-if="pagination" class="text-gray-500">
                                Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} results
                            </span>
                            <div class="pagination flex items-center gap-1" v-if="pagination?.last_page > 1">
                                <button
                                    class="btn btn-sm btn-icon btn-light"
                                    :disabled="currentPage <= 1"
                                    @click="currentPage--">
                                    <i class="ki-outline ki-black-left"></i>
                                </button>
                                <button
                                    v-for="page in visiblePages"
                                    :key="page"
                                    class="btn btn-sm btn-icon"
                                    :class="page === currentPage ? 'btn-primary' : 'btn-light'"
                                    @click="goToPage(page)"
                                    :disabled="page === '...'">
                                    {{ page }}
                                </button>
                                <button
                                    class="btn btn-sm btn-icon btn-light"
                                    :disabled="currentPage >= pagination?.last_page"
                                    @click="currentPage++">
                                    <i class="ki-outline ki-black-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create New Brand Modal -->
            <div id="modal_create_new_brand" data-modal="true" class="modal" aria-hidden="true">
                <div class="modal-content max-w-[600px] top-[10%]">
                    <form @submit.prevent="submitForm">
                        <div class="modal-header">
                            <h3 class="modal-title">Create New Brand</h3>
                            <button type="button" class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>
                        <div class="modal-body p-6">
                            <div class="mb-4">
                                <label for="brand_name" class="form-label mb-2">Brand Name <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    id="brand_name"
                                    v-model="form.name"
                                    class="input"
                                    placeholder="Enter brand name"
                                    required
                                />
                            </div>
                        </div>
                        <div class="modal-footer justify-between px-6 py-4">
                            <button type="button" class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ki-filled ki-check me-1"></i>
                                Create Brand
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Brand Details Modal -->
            <div class="modal" data-modal="true" id="modal_view_brand">
                <div class="modal-content max-w-[600px] top-[10%]">
                    <div class="modal-header">
                        <h3 class="modal-title">Brand Details</h3>
                        <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- If data is loaded -->
                        <div v-if="selectedBrand" class="p-5">
                            <!-- Brand Header -->
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl mb-5">
                                <div class="flex items-center justify-center w-16 h-16 rounded-xl bg-primary-light text-primary font-bold text-2xl shrink-0">
                                    {{ selectedBrand.name?.charAt(0)?.toUpperCase() || 'B' }}
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedBrand.name }}</h4>
                                </div>
                            </div>

                            <!-- Brand Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Products Count</label>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ formatNumber(selectedBrand.products_count) }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Created At</label>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ formatDate(selectedBrand.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Loading skeleton -->
                        <div v-else class="p-10">
                            <div class="animate-pulse">
                                <div class="flex gap-4 mb-6">
                                    <div class="w-16 h-16 bg-gray-200 rounded-xl"></div>
                                    <div class="flex-1">
                                        <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
                                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div class="h-3 bg-gray-200 rounded"></div>
                                    <div class="h-3 bg-gray-200 rounded w-5/6"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-between px-6 py-4">
                        <button class="btn btn-light" data-modal-dismiss="true">Close</button>
                        <Link v-if="selectedBrand" :href="'/brand/edit/' + selectedBrand.id" class="btn btn-primary">
                            <i class="ki-filled ki-pencil me-1"></i>
                            Edit Brand
                        </Link>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </AppLayout>
</template>
