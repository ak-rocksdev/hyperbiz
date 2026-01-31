<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    products: Array,
    pagination: Object,
    filters: Object,
    categories: Array,
    brands: Array,
    customers: Array,
    stockStatusOptions: Array,
    stats: Object,
});

// Reactive filters
const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category_id || null);
const selectedBrand = ref(props.filters?.brand_id || null);
const selectedStockStatus = ref(props.filters?.stock_status || null);
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [10, 25, 50, 100];
const selectedPerPage = ref(props.pagination?.per_page || 10);

// Form and modal state
const form = ref({});
const errors = ref({});
const selectedProduct = ref(null);

// Computed options for SearchableSelect (with "All" option)
const categoryOptions = computed(() => [
    { value: '', label: 'All Categories' },
    ...(props.categories || [])
]);

const brandOptions = computed(() => [
    { value: '', label: 'All Brands' },
    ...(props.brands || [])
]);

const stockStatusSelectOptions = computed(() => [
    { value: '', label: 'All Status' },
    ...(props.stockStatusOptions || [])
]);

// Customer options for create modal (without "All" option)
const customerOptions = computed(() => [
    { value: '', label: 'Select Customer (Optional)' },
    ...(props.customers || [])
]);

// Stock status badge classes
const stockStatusColors = {
    in_stock: 'badge-success',
    low_stock: 'badge-warning',
    out_of_stock: 'badge-danger',
    no_stock: 'badge-secondary',
};

const stockStatusLabels = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
    no_stock: 'No Stock',
};

// Format helpers
const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0
    }).format(Math.floor(value || 0));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

// Fetch data with filters
const fetchData = () => {
    router.get(route('product.list'), {
        search: searchQuery.value || undefined,
        category_id: selectedCategory.value || undefined,
        brand_id: selectedBrand.value || undefined,
        stock_status: selectedStockStatus.value || undefined,
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

// Pagination helpers
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

// Product detail fetch
const fetchProductDetail = async (id) => {
    selectedProduct.value = null;
    try {
        const response = await axios.get(`/products/api/detail/${id}`);
        selectedProduct.value = response.data.product;
    } catch (error) {
        console.error("Error fetching product details:", error);
    }
};

// Create product
const createProduct = async (formData) => {
    errors.value = {};

    try {
        await axios.post('/products/api/store', formData)
            .then(response => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    icon: 'success',
                    title: 'Success',
                    text: response.data.message
                });

                KTModal.init();
                const modalEl = document.querySelector('#modal_create_product');
                const modal = KTModal.getInstance(modalEl);
                modal.hide();

                if (document.querySelector('.modal-backdrop')) {
                    document.querySelector('.modal-backdrop').remove();
                }

                // Reset form
                form.value = {};
                fetchData();
            }).catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.response?.data?.message || 'Something went wrong',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                });
                if (error.response && error.response.status === 422) {
                    errors.value = error.response.data.errors;
                }
            });
    } catch (error) {
        console.error("Error creating product:", error);
    }
};

// Delete product
const deleteProduct = (id, name) => {
    Swal.fire({
        title: 'Delete Product?',
        text: `Are you sure you want to delete "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/products/api/delete/${id}`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    fetchData();
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.response?.data?.message || 'Something went wrong!',
                    });
                });
        }
    });
};
</script>

<template>
    <AppLayout title="Products">
        <!-- Header Section -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Products
            </h2>
        </template>

        <!-- Container Section -->
        <div class="container-fixed">
            <!-- Stats Summary Cards -->
            <div class="py-5">
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-center justify-between gap-5">
                            <!-- Total Products -->
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100">
                                    <i class="ki-filled ki-handcart text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_products) }}</div>
                                    <div class="text-xs text-gray-500">Total Products</div>
                                </div>
                            </div>
                            <!-- Total Categories -->
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100">
                                    <i class="ki-filled ki-folder text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_categories) }}</div>
                                    <div class="text-xs text-gray-500">Categories</div>
                                </div>
                            </div>
                            <!-- Low Stock -->
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-orange-100">
                                    <i class="ki-filled ki-notification-bing text-orange-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-orange-600">{{ formatNumber(stats?.low_stock) }}</div>
                                    <div class="text-xs text-gray-500">Low Stock</div>
                                </div>
                            </div>
                            <!-- Out of Stock -->
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-red-100">
                                    <i class="ki-filled ki-cross-circle text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-red-600">{{ formatNumber(stats?.out_of_stock) }}</div>
                                    <div class="text-xs text-gray-500">Out of Stock</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Table Section -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header">
                        <h3 class="card-title">Products</h3>
                        <div class="card-toolbar">
                            <div class="flex gap-3 items-center">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                    <input
                                        v-model="searchQuery"
                                        class="input input-sm ps-8 w-[180px]"
                                        placeholder="Search products..."
                                        @keyup.enter="performSearch"
                                    />
                                </div>

                                <!-- Category Filter -->
                                <SearchableSelect
                                    v-model="selectedCategory"
                                    :options="categoryOptions"
                                    placeholder="All Categories"
                                    size="sm"
                                    class="w-[160px]"
                                    @update:model-value="performSearch"
                                />

                                <!-- Brand Filter -->
                                <SearchableSelect
                                    v-model="selectedBrand"
                                    :options="brandOptions"
                                    placeholder="All Brands"
                                    size="sm"
                                    class="w-[140px]"
                                    @update:model-value="performSearch"
                                />

                                <!-- Stock Status Filter -->
                                <SearchableSelect
                                    v-model="selectedStockStatus"
                                    :options="stockStatusSelectOptions"
                                    placeholder="All Status"
                                    size="sm"
                                    class="w-[130px]"
                                    @update:model-value="performSearch"
                                />

                                <!-- Add Button -->
                                <button
                                    class="btn btn-sm btn-primary whitespace-nowrap"
                                    data-modal-toggle="#modal_create_product">
                                    <i class="ki-filled ki-plus-squared me-1"></i>
                                    Add Product
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
                                        <th class="min-w-[250px]">Product Name</th>
                                        <th class="min-w-[120px]">Category</th>
                                        <th class="w-[100px] text-center">Stock</th>
                                        <th class="w-[110px] text-center">Status</th>
                                        <th class="min-w-[130px] text-end">Price</th>
                                        <th class="w-[100px] text-center">Active</th>
                                        <th class="w-[80px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(product, index) in products" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                        <td class="text-center text-gray-500">
                                            {{ (pagination?.from || 0) + index }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <!-- Avatar -->
                                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-teal-100 text-teal-700 font-bold text-sm shrink-0">
                                                    {{ product.name.split(' ').length > 1
                                                        ? product.name.split(' ').map(word => word[0]?.toUpperCase()).slice(0, 2).join('')
                                                        : product.name[0]?.toUpperCase()
                                                    }}
                                                </div>
                                                <!-- Name & Brand -->
                                                <div class="flex flex-col">
                                                    <Link
                                                        :href="'/products/detail/' + product.id"
                                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary">
                                                        {{ product.name }}
                                                    </Link>
                                                    <span class="text-xs text-gray-500">{{ product.brand }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ product.category }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ formatNumber(product.quantity_on_hand) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge badge-sm whitespace-nowrap"
                                                :class="stockStatusColors[product.stock_status] || 'badge-secondary'">
                                                {{ stockStatusLabels[product.stock_status] || product.stock_status }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(product.price) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="product.is_active" class="badge badge-outline badge-success badge-sm">Active</span>
                                            <span v-else class="badge badge-outline badge-danger badge-sm">Inactive</span>
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
                                                            <Link class="menu-link" :href="'/products/detail/' + product.id">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="'/products/edit/' + product.id">
                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Edit</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-separator"></div>
                                                        <div class="menu-item">
                                                            <a class="menu-link" href="#" @click.prevent="deleteProduct(product.id, product.name)">
                                                                <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                <span class="menu-title text-danger">Delete</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!products || products.length === 0">
                                        <td colspan="8" class="text-center">
                                            <div class="flex items-center justify-center h-40">
                                                <div class="flex flex-col items-center">
                                                    <i class="ki-filled ki-handcart text-6xl text-gray-300 mb-3"></i>
                                                    <span class="text-gray-500">No products found</span>
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
                                <select v-model="selectedPerPage" class="select select-sm w-16">
                                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                per page
                            </div>
                            <span v-if="pagination" class="text-gray-500">
                                Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} products
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
        </div>

        <!-- Create Product Modal -->
        <div class="modal" data-modal="true" id="modal_create_product">
            <div class="modal-content max-w-[600px] top-[5%]">
                <form @submit.prevent="createProduct(form)">
                    <div class="modal-header">
                        <h3 class="modal-title">Add New Product</h3>
                        <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true" type="button">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>
                    <div class="modal-body p-6 max-h-[70vh] overflow-y-auto">
                        <!-- Validation Errors -->
                        <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded" role="alert">
                            <p class="font-bold mb-2">Please fix the following errors:</p>
                            <ul class="list-disc pl-5 text-sm">
                                <li v-for="(messages, field) in errors" :key="field">
                                    <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Category -->
                        <div class="mb-4">
                            <label class="form-label mb-2">Category <span class="text-red-500">*</span></label>
                            <SearchableSelect
                                v-model="form.mst_product_category_id"
                                :options="categories"
                                placeholder="Select Category"
                                :searchable="true"
                            />
                        </div>

                        <!-- Brand -->
                        <div class="mb-4">
                            <label class="form-label mb-2">Brand <span class="text-gray-400 text-xs">(Optional)</span></label>
                            <SearchableSelect
                                v-model="form.mst_brand_id"
                                :options="brands"
                                placeholder="Select Brand"
                                :searchable="true"
                                :clearable="true"
                            />
                        </div>

                        <!-- Customer/Supplier -->
                        <div class="mb-4">
                            <label class="form-label mb-2">Supplier <span class="text-gray-400 text-xs">(Optional)</span></label>
                            <SearchableSelect
                                v-model="form.mst_client_id"
                                :options="customerOptions"
                                placeholder="Select Supplier"
                                :searchable="true"
                                :clearable="true"
                            />
                        </div>

                        <!-- Product Name -->
                        <div class="mb-4">
                            <label class="form-label mb-2">Product Name <span class="text-red-500">*</span></label>
                            <input class="input" v-model="form.name" type="text" placeholder="Enter product name" />
                        </div>

                        <!-- Product Description -->
                        <div class="mb-4">
                            <label class="form-label mb-2">Description <span class="text-gray-400 text-xs">(Optional)</span></label>
                            <textarea class="textarea" v-model="form.description" placeholder="Enter product description" rows="2"></textarea>
                        </div>

                        <!-- SKU & Barcode -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label mb-2">SKU</label>
                                <input class="input" v-model="form.sku" type="text" placeholder="Stock Keeping Unit" />
                            </div>
                            <div>
                                <label class="form-label mb-2">Barcode</label>
                                <input class="input" v-model="form.barcode" type="text" placeholder="Barcode" />
                            </div>
                        </div>

                        <!-- Price & Cost -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label mb-2">Selling Price <span class="text-red-500">*</span></label>
                                <div class="input-group">
                                    <span class="btn btn-input">IDR</span>
                                    <input class="input" v-model="form.price" type="number" step="0.01" placeholder="0" />
                                </div>
                            </div>
                            <div>
                                <label class="form-label mb-2">Cost Price</label>
                                <div class="input-group">
                                    <span class="btn btn-input">IDR</span>
                                    <input class="input" v-model="form.cost_price" type="number" step="0.01" placeholder="0" />
                                </div>
                            </div>
                        </div>

                        <!-- Initial Stock & Reorder Level -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label mb-2">Initial Stock</label>
                                <input class="input" v-model="form.initial_stock" type="number" placeholder="0" />
                            </div>
                            <div>
                                <label class="form-label mb-2">Reorder Level</label>
                                <input class="input" v-model="form.reorder_level" type="number" placeholder="0" />
                            </div>
                        </div>

                        <!-- Weight & Status -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label mb-2">Weight</label>
                                <div class="input-group">
                                    <input class="input" v-model="form.weight" type="number" step="0.01" placeholder="0" />
                                    <span class="btn btn-input">Kg</span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label mb-2">Status</label>
                                <select class="select" v-model="form.is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-between px-6 py-4">
                        <button class="btn btn-light" data-modal-dismiss="true" type="button">Cancel</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="ki-filled ki-check me-1"></i>
                            Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Product Modal -->
        <div class="modal" data-modal="true" id="modal_view_product">
            <div class="modal-content max-w-[600px] top-[5%]">
                <div class="modal-header">
                    <h3 class="modal-title">Product Details</h3>
                    <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body max-h-[70vh] overflow-y-auto">
                    <div v-if="selectedProduct" class="p-5">
                        <!-- Status Badges -->
                        <div class="flex flex-wrap gap-2 mb-5">
                            <span v-if="selectedProduct.is_active" class="badge badge-outline badge-success">Active</span>
                            <span v-else class="badge badge-outline badge-danger">Inactive</span>
                            <span
                                class="badge whitespace-nowrap"
                                :class="stockStatusColors[selectedProduct.stock_status] || 'badge-secondary'">
                                {{ stockStatusLabels[selectedProduct.stock_status] || selectedProduct.stock_status }}
                            </span>
                        </div>

                        <!-- Product Header -->
                        <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl mb-5">
                            <div class="flex items-center justify-center w-20 h-20 rounded-xl bg-teal-100 text-teal-700 font-bold text-2xl shrink-0">
                                {{ selectedProduct.name.split(' ').length > 1
                                    ? selectedProduct.name.split(' ').map(word => word[0]?.toUpperCase()).slice(0, 2).join('')
                                    : selectedProduct.name[0]?.toUpperCase()
                                }}
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ selectedProduct.name }}</h4>
                                <p class="text-sm text-gray-500">{{ selectedProduct.description || 'No description' }}</p>
                            </div>
                        </div>

                        <!-- Meta Info -->
                        <div class="grid grid-cols-3 gap-4 mb-5">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Supplier</label>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedProduct.customer || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Category</label>
                                <span class="badge badge-outline badge-secondary badge-sm">{{ selectedProduct.category }}</span>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Brand</label>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedProduct.brand }}</p>
                            </div>
                        </div>

                        <!-- SKU & Barcode -->
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">SKU</label>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedProduct.sku }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Barcode</label>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedProduct.barcode }}</p>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Selling Price</label>
                                <p class="text-lg font-semibold text-primary">{{ formatCurrency(selectedProduct.price) }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Average Cost</label>
                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ formatCurrency(selectedProduct.average_cost) }}</p>
                            </div>
                        </div>

                        <!-- Stock Information -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-5">
                            <h5 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-parcel"></i>
                                Stock Information
                            </h5>
                            <div class="grid grid-cols-4 gap-3">
                                <div class="text-center">
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(selectedProduct.quantity_on_hand) }}</p>
                                    <label class="text-xs text-gray-500">On Hand</label>
                                </div>
                                <div class="text-center">
                                    <p class="text-xl font-bold text-green-600">{{ formatNumber(selectedProduct.quantity_available) }}</p>
                                    <label class="text-xs text-gray-500">Available</label>
                                </div>
                                <div class="text-center">
                                    <p class="text-xl font-bold text-orange-600">{{ formatNumber(selectedProduct.quantity_reserved) }}</p>
                                    <label class="text-xs text-gray-500">Reserved</label>
                                </div>
                                <div class="text-center">
                                    <p class="text-xl font-bold text-gray-600 dark:text-gray-400">{{ formatNumber(selectedProduct.reorder_level) }}</p>
                                    <label class="text-xs text-gray-500">Reorder Level</label>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Weight</label>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedProduct.weight || 0 }} Kg</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Dimensions</label>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedProduct.dimensions || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-else class="p-10">
                        <div class="animate-pulse">
                            <div class="flex gap-4 mb-6">
                                <div class="w-20 h-20 bg-gray-200 rounded-xl"></div>
                                <div class="flex-1">
                                    <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="h-3 bg-gray-200 rounded"></div>
                                <div class="h-3 bg-gray-200 rounded w-5/6"></div>
                                <div class="h-3 bg-gray-200 rounded w-4/6"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-between px-6 py-4">
                    <button class="btn btn-light" data-modal-dismiss="true">Close</button>
                    <Link v-if="selectedProduct" :href="'/products/edit/' + selectedProduct.id" class="btn btn-primary">
                        <i class="ki-filled ki-pencil me-1"></i>
                        Edit Product
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.input-group .btn-input {
    border-color: #d8d8d8;
    background-color: #f9fafb;
}
</style>
