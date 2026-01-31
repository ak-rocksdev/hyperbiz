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
    uoms: Array,
    categories: Array,
    pagination: Object,
    filters: Object,
    stats: Object,
});

// Search and filter state
const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category_id || '');
const selectedStatus = ref(props.filters?.status || '');
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [
    { value: 10, label: '10' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
];
const selectedPerPage = ref(props.pagination?.per_page || 10);

// Status filter options
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Form state for create modal
const form = ref({
    code: '',
    name: '',
    category_id: '',
    base_uom_id: '',
    conversion_factor: 1,
    description: '',
    is_active: true,
});
const isLoading = ref(false);
const baseUomOptions = ref([]);
const isLoadingBaseUoms = ref(false);

// Format number helper
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Fetch data with filters
const fetchData = () => {
    router.get(route('uom.list'), {
        search: searchQuery.value || undefined,
        category_id: selectedCategory.value || undefined,
        status: selectedStatus.value || undefined,
        per_page: selectedPerPage.value,
        page: currentPage.value,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Watch for pagination and filter changes
watch([currentPage, selectedPerPage], () => {
    fetchData();
});

watch([selectedCategory, selectedStatus], () => {
    currentPage.value = 1;
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

// Fetch base UoMs by category
const fetchBaseUoms = async (categoryId) => {
    if (!categoryId) {
        baseUomOptions.value = [];
        form.value.base_uom_id = '';
        return;
    }

    isLoadingBaseUoms.value = true;
    try {
        const response = await axios.get(`/uom/api/base-uoms/${categoryId}`);
        baseUomOptions.value = response.data.data || [];
        form.value.base_uom_id = '';
    } catch (error) {
        console.error('Failed to fetch base UoMs:', error);
        baseUomOptions.value = [];
    } finally {
        isLoadingBaseUoms.value = false;
    }
};

// Watch form category changes
watch(() => form.value.category_id, (newCategoryId) => {
    fetchBaseUoms(newCategoryId);
});

// Create new UoM
const submitForm = () => {
    isLoading.value = true;

    axios.post('/uom/api/store', {
        code: form.value.code,
        name: form.value.name,
        category_id: form.value.category_id || null,
        base_uom_id: form.value.base_uom_id || null,
        conversion_factor: form.value.base_uom_id ? form.value.conversion_factor : 1,
        description: form.value.description || null,
        is_active: form.value.is_active,
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

        resetForm();
        closeModal('modal_create_new_uom');
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

// Reset form
const resetForm = () => {
    form.value = {
        code: '',
        name: '',
        category_id: '',
        base_uom_id: '',
        conversion_factor: 1,
        description: '',
        is_active: true,
    };
    baseUomOptions.value = [];
};

// Toggle status
const toggleStatus = (uom) => {
    const newStatus = uom.is_active ? 'inactive' : 'active';
    Swal.fire({
        title: 'Change Status?',
        text: `Are you sure you want to set "${uom.name}" to ${newStatus}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`/uom/api/toggle-status/${uom.id}`)
                .then(response => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Updated!',
                        text: response.data.message
                    });
                    fetchData();
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data?.message || 'Failed to update status.'
                    });
                });
        }
    });
};

// Delete UoM
const deleteUom = (uom) => {
    Swal.fire({
        title: 'Delete UoM?',
        text: `Are you sure you want to delete "${uom.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/uom/api/delete/${uom.id}`)
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
                        text: error.response?.data?.message || 'Failed to delete UoM.'
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
    <AppLayout title="Units of Measure">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Units of Measure
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
                                    <i class="ki-filled ki-scale text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_uoms) }}</div>
                                    <div class="text-xs text-gray-500">Total UoMs</div>
                                </div>
                            </div>
                            <div class="h-10 border-l border-gray-200 dark:border-gray-700"></div>
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-success-light">
                                    <i class="ki-filled ki-check-circle text-success text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.active_uoms) }}</div>
                                    <div class="text-xs text-gray-500">Active UoMs</div>
                                </div>
                            </div>
                            <div class="h-10 border-l border-gray-200 dark:border-gray-700"></div>
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-info-light">
                                    <i class="ki-filled ki-crown text-info text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.base_uoms) }}</div>
                                    <div class="text-xs text-gray-500">Base UoMs</div>
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
                        <h3 class="card-title">Units of Measure</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Search Input -->
                            <div class="relative">
                                <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                                <input
                                    type="text"
                                    class="input input-sm pl-9 w-[200px]"
                                    placeholder="Search UoMs..."
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

                            <!-- Category Filter -->
                            <SearchableSelect
                                v-model="selectedCategory"
                                :options="categories"
                                placeholder="All Categories"
                                class="w-[180px]"
                                :clearable="true"
                                size="sm"
                            />

                            <!-- Status Filter -->
                            <SearchableSelect
                                v-model="selectedStatus"
                                :options="statusOptions"
                                placeholder="All Status"
                                class="w-[140px]"
                                :clearable="false"
                                size="sm"
                            />

                            <!-- Add New Button -->
                            <button class="btn btn-sm btn-primary" data-modal-toggle="#modal_create_new_uom">
                                <i class="ki-filled ki-plus-squared me-1"></i>
                                New UoM
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[50px] text-center">#</th>
                                        <th class="w-[100px]">Code</th>
                                        <th class="min-w-[180px]">Name</th>
                                        <th class="w-[150px]">Category</th>
                                        <th class="w-[120px]">Base UoM</th>
                                        <th class="w-[120px] text-center">Conv. Factor</th>
                                        <th class="w-[100px] text-center">Products</th>
                                        <th class="w-[100px] text-center">Status</th>
                                        <th class="w-[80px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!uoms || uoms.length === 0">
                                        <td colspan="9">
                                            <div class="flex flex-col items-center justify-center py-10">
                                                <i class="ki-filled ki-scale text-6xl text-gray-300 mb-3"></i>
                                                <span class="text-gray-500">No UoMs found</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else v-for="(uom, index) in uoms" :key="uom.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                        <td class="text-center text-gray-600">
                                            {{ (pagination?.from || 0) + index }}
                                        </td>
                                        <td>
                                            <span class="font-medium text-gray-900">{{ uom.code }}</span>
                                            <span v-if="uom.is_base" class="badge badge-sm badge-outline badge-primary ms-2">Base</span>
                                        </td>
                                        <td>
                                            <Link :href="`/uom/detail/${uom.id}`" class="text-sm font-medium text-gray-900 hover:text-primary">
                                                {{ uom.name }}
                                            </Link>
                                        </td>
                                        <td>
                                            <span v-if="uom.category_name" class="badge badge-sm badge-outline badge-secondary">
                                                {{ uom.category_name }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td>
                                            <span v-if="uom.base_uom_code" class="text-gray-600">
                                                {{ uom.base_uom_code }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="uom.base_uom_id" class="text-gray-600">
                                                {{ formatNumber(uom.conversion_factor) }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm badge-outline" :class="uom.products_count > 0 ? 'badge-primary' : 'badge-secondary'">
                                                {{ formatNumber(uom.products_count) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm" :class="uom.is_active ? 'badge-success' : 'badge-danger'">
                                                {{ uom.is_active ? 'Active' : 'Inactive' }}
                                            </span>
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
                                                            <Link class="menu-link" :href="`/uom/detail/${uom.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/uom/edit/${uom.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Edit</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-separator"></div>
                                                        <div class="menu-item">
                                                            <span class="menu-link cursor-pointer" @click="toggleStatus(uom)">
                                                                <span class="menu-icon">
                                                                    <i :class="uom.is_active ? 'ki-filled ki-cross-circle' : 'ki-filled ki-check-circle'"></i>
                                                                </span>
                                                                <span class="menu-title">{{ uom.is_active ? 'Deactivate' : 'Activate' }}</span>
                                                            </span>
                                                        </div>
                                                        <div class="menu-separator"></div>
                                                        <div class="menu-item">
                                                            <span class="menu-link cursor-pointer" @click="deleteUom(uom)">
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

            <!-- Create New UoM Modal -->
            <div id="modal_create_new_uom" data-modal="true" class="modal">
                <div class="modal-dialog">
                    <div class="modal-content max-w-[500px] top-[10%]">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New UoM</h5>
                            <button type="button" class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true" @click="resetForm">
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>
                        <form @submit.prevent="submitForm">
                            <div class="modal-body py-5">
                                <!-- Code -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">
                                        Code <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="input w-full"
                                        v-model="form.code"
                                        placeholder="e.g., PCS, KG, M"
                                        required
                                    />
                                </div>

                                <!-- Name -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">
                                        Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="input w-full"
                                        v-model="form.name"
                                        placeholder="e.g., Pieces, Kilogram, Meter"
                                        required
                                    />
                                </div>

                                <!-- Category -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">
                                        Category <span class="text-danger">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.category_id"
                                        :options="categories"
                                        placeholder="Select category"
                                        :clearable="true"
                                    />
                                </div>

                                <!-- Base UoM -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">Base UoM</label>
                                    <SearchableSelect
                                        v-model="form.base_uom_id"
                                        :options="baseUomOptions"
                                        placeholder="Select base UoM (optional)"
                                        :clearable="true"
                                        :disabled="!form.category_id || isLoadingBaseUoms"
                                    />
                                    <span class="text-xs text-gray-500 mt-1">
                                        Leave empty if this is a base UoM.
                                    </span>
                                </div>

                                <!-- Conversion Factor -->
                                <div v-if="form.base_uom_id" class="mb-5">
                                    <label class="form-label text-gray-700">
                                        Conversion Factor <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        class="input w-full"
                                        v-model.number="form.conversion_factor"
                                        placeholder="e.g., 12 (1 dozen = 12 pieces)"
                                        min="0.0001"
                                        step="any"
                                        required
                                    />
                                    <span class="text-xs text-gray-500 mt-1">
                                        How many base units equal 1 of this unit.
                                    </span>
                                </div>

                                <!-- Description -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">Description</label>
                                    <textarea
                                        class="input w-full"
                                        v-model="form.description"
                                        placeholder="Enter description (optional)"
                                        rows="3"
                                    ></textarea>
                                </div>

                                <!-- Is Active -->
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        class="checkbox"
                                        v-model="form.is_active"
                                    />
                                    <label for="is_active" class="form-label text-gray-700 mb-0 cursor-pointer">
                                        Active
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-modal-dismiss="true" @click="resetForm">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ isLoading ? 'Creating...' : 'Create UoM' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
