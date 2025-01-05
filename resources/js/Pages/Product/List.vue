<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import axios from 'axios';
    import { ref } from 'vue';
    import Swal from 'sweetalert2';

    const form = ref({});
    const errors = ref({});
    const selectedProduct = ref(null);

    const props = defineProps({
        products: Array,
        categories: Object,
        brands: Object,
        clients: Object,
        totalCategoriesCount: Number,
        totalProducts: Number,
    });

    const products = ref(props.products ?? []);
    const categories = ref(props.categories ?? {});
    const brands = ref(props.brands ?? {});
    const clients = ref(props.clients ?? {});
    const totalCategoriesCount = ref(props.totalCategoriesCount ?? 0);
    const totalProducts = ref(props.totalProducts ?? 0);

    const fetchProductDetail = async (id) => {
        selectedProduct.value = null; // Clear previous selection
        try {
            const response = await axios.get(`/products/api/detail/${id}`);
            selectedProduct.value = response.data.product;
        } catch (error) {
            console.error("Error fetching product details:", error);
        }
    };

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

                    router.get(route('product.list'));
                }).catch(error => {
                    // Swal toast
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.response.data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                    if (error.response && error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    } else {
                        console.error('An unexpected error occurred:', error);
                    }
                });
        } catch (error) {
            console.error("Error creating product:", error);
        }
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
            <!-- Summary Cards -->
            <div class="py-5">
                <div class="grid grid-cols-2 gap-5 lg:gap-7.5 w-full items-stretch">
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ totalProducts }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Total Products
                            </span>
                        </div>
                    </div>
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ totalCategoriesCount }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Categories
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Table Section -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Products
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input data-datatable-search="#data_container" class="input input-sm ps-8" placeholder="Search Product" />
                                </div>
                                <button
                                    class="btn btn-sm btn-primary min-w-[150px] justify-center"
                                    data-modal-toggle="#modal_create_product">
                                    Add New Product
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="data_container">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px]">
                                                <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox" />
                                            </th>
                                            <th class="min-w-[250px] lg:w-[300px]" data-datatable-column="name">
                                                <span class="sort">
                                                    <span class="sort-label">Product Name</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="min-w-[200px] lg:w-[200px]" data-datatable-column="name">
                                                <span class="sort">
                                                    <span class="sort-label">Client</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="min-w-[150px] lg:w-[150px]">
                                                <span class="sort">
                                                    <span class="sort-label">Category</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="min-w-[100px] lg:w-[100px] text-end">
                                                <span class="sort">
                                                    <span class="sort-label">Stock</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="min-w-[150px] lg:w-[150px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">Status</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="min-w-[150px] lg:w-[150px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">Created At</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="w-[85px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">Action</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="product in products" :key="product.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="product.id" />
                                            </td>
                                            <td class="flex items-center gap-4">
                                                <!-- Circle Avatar -->
                                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-teal-100 text-teal-700 font-bold border border-teal-400 shrink-0">
                                                    <!-- Display initials -->
                                                    {{ product.name.split(' ').length > 1 
                                                        ? product.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('') 
                                                        : product.name[0].toUpperCase() 
                                                    }}
                                                </div>
                                                <!-- Product Name and Brand -->
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-md font-medium text-gray-900 hover:text-primary-active mb-px hover:cursor-pointer"
                                                        @click="fetchProductDetail(product.id)"
                                                        data-modal-toggle="#modal_view_product">
                                                        {{ product.name }}
                                                    </span>
                                                    <div class="text-xs text-gray-500">
                                                        {{ product.brand }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{ product.client }}
                                            </td>
                                            <td>
                                                {{ product.category }}
                                            </td>
                                            <td class="text-end">
                                                {{ product.stock_quantity }}
                                            </td>
                                            <td class="text-center">
                                                <span v-if="product.is_active" class="badge badge-outline badge-success">Active</span>
                                                <span v-else class="badge badge-outline badge-danger">Inactive</span>
                                            </td>
                                            <td class="text-center">
                                                {{ product.created_at }}
                                            </td>
                                            <td class="text-center">
                                                <div class="menu flex-inline justify-center" data-menu="true">
                                                    <div class="menu-item" data-menu-item-offset="0, 10px"
                                                        data-menu-item-placement="bottom-end"
                                                        data-menu-item-placement-rtl="bottom-start"
                                                        data-menu-item-toggle="dropdown"
                                                        data-menu-item-trigger="click|lg:click">
                                                        <button
                                                            class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                            <i class="ki-filled ki-dots-vertical">
                                                            </i>
                                                        </button>
                                                        <div class="menu-dropdown menu-default w-full max-w-[175px]"
                                                            data-menu-dismiss="true">
                                                            <div class="menu-item">
                                                                <Link class="menu-link" :href="'/product/detail/' + product.id">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-search-list">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        View Detail
                                                                    </span>
                                                                </Link>
                                                            </div>
                                                            <div class="menu-separator">
                                                            </div>
                                                            <div class="menu-item">
                                                                <Link class="menu-link" :href="'/products/edit/' + product.id">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-pencil">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Edit
                                                                    </span>
                                                                </Link>
                                                            </div>
                                                            <div class="menu-separator">
                                                            </div>
                                                            <div class="menu-item">
                                                                <a class="menu-link" href="#">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-trash">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title !text-red-500 hover:!text-red-600">
                                                                        Remove
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!products || products.length === 0">
                                            <td colspan="6" class="text-center text-gray-500">No products found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                                <div class="flex items-center gap-2 order-2 md:order-1">
                                    Show
                                    <select class="select select-sm w-16" data-datatable-size="true" name="perpage">
                                    </select>
                                    per page
                                </div>
                                <div class="flex items-center gap-4 order-1 md:order-2">
                                    <span data-datatable-info="true">
                                    </span>
                                    <div class="pagination" data-datatable-pagination="true">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Product Modal -->
        <div class="modal" data-modal="true" id="modal_create_product">
            <div class="modal-content max-w-[600px] top-[10%]">
                <form @submit.prevent="createProduct(form)">
                    <div class="modal-header">
                        <h3 class="modal-title">Add New Product</h3>
                        <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>
                    <div class="modal-body p-10">
                        <div v-if="Object.keys(errors).length" class="bg-red-100 border-l-4 border-red-300 text-red-700 p-4 mb-5" role="alert">
                            <p class="font-bold mb-3">Error!</p>
                            <ul class="list-disc pl-5 text-sm">
                                <li v-for="(messages, field) in errors" :key="field">
                                    <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-4">
                            <label class="form-label mb-1">Category <span class="text-red-500 ms-1">*</span></label>
                            <select 
                                class="select" 
                                v-model="form.mst_product_category_id">
                                <option value="" disabled>Select Category</option>
                                <option 
                                    v-for="(category, id) in categories" 
                                    :key="id" 
                                    :value="id">
                                    {{ category }}
                                </option>
                            </select>
                        </div>

                        <!-- Brand -->
                        <div class="mb-4">
                            <label class="form-label mb-1">Brand (Optional)</label>
                            <select 
                                class="select" 
                                v-model="form.mst_brand_id">
                                <option value="" disabled>Select Brand</option>
                                <option 
                                    v-for="(brand, id) in brands" 
                                    :key="id" 
                                    :value="id">
                                    {{ brand }}
                                </option>
                            </select>
                        </div>

                        <!-- clients -->
                        <div class="mb-4">
                            <label class="form-label mb-1">Client <span class="text-red-500 ms-1">*</span></label>
                            <select 
                                class="select" 
                                v-model="form.mst_client_id">
                                <option value="" disabled>Select Client</option>
                                <option 
                                    v-for="(client_name, id) in clients" 
                                    :key="id" 
                                    :value="id">
                                    {{ client_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Product Name -->
                        <div class="mb-4">
                            <label class="form-label mb-1">Product Name <span class="text-red-500 ms-1">*</span></label>
                            <input 
                                class="input" 
                                v-model="form.name" 
                                type="text" 
                                placeholder="Enter product name" 
                            />
                        </div>

                        <!-- Product Description -->
                        <div class="mb-4">
                            <label class="form-label mb-1">Product Description</label>
                            <textarea 
                                class="textarea" 
                                v-model="form.description" 
                                placeholder="Enter product description"></textarea>
                        </div>

                        <!-- SKU -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="form-label mb-1">SKU</label>
                                <input 
                                    class="input" 
                                    v-model="form.sku" 
                                    type="text" 
                                    placeholder="Enter Stock Keeping Unit" 
                                />
                            </div>

                            <!-- barcode -->
                            <div class="mb-4">
                                <label class="form-label mb-1">Barcode</label>
                                <input 
                                    class="input" 
                                    v-model="form.barcode" 
                                    type="text" 
                                    placeholder="Enter product barcode (Optional)" />
                            </div>

                            <!-- min_stock_level -->
                            <div class="mb-4">
                                <label class="form-label mb-1">Minimum Stock Level</label>
                                <input 
                                    class="input" 
                                    v-model="form.min_stock_level" 
                                    type="number" 
                                    placeholder="Enter minimum stock level (Optional)" />
                            </div>

                            <!-- cost_price -->
                            <div class="mb-4">
                                <label class="form-label mb-1">Cost Price / Base Price <span class="text-red-500 ms-1">*</span></label>
                                <div class="input-group">
                                    <span class="btn btn-input" style="border-color: #d8d8d8;">
                                        IDR
                                    </span>
                                    <input 
                                        class="input" 
                                        v-model="form.cost_price" 
                                        type="number" 
                                        step="0.01" 
                                        placeholder="Enter product cost price" 
                                    />
                                </div>
                            </div>

                            <!-- Price -->
                            <div class="mb-4">
                                <label class="form-label mb-1">Price <span class="text-red-500 ms-1">*</span></label>
                                <div class="input-group">
                                    <span class="btn btn-input" style="border-color: #d8d8d8;">
                                        IDR
                                    </span>
                                    <input 
                                        class="input" 
                                        v-model="form.price" 
                                        type="number" 
                                        step="0.01" 
                                        placeholder="Enter product price" 
                                    />
                                </div>
                            </div>

                            <!-- Stock -->
                            <div class="mb-4">
                                <label class="form-label mb-1">Stock</label>
                                <input 
                                    class="input" 
                                    v-model="form.stock_quantity" 
                                    type="number" 
                                    placeholder="Enter available stock" 
                                />
                            </div>

                            <!-- Weight -->
                            <div>
                                <label class="form-label mb-1">Weight</label>
                                <div class="input-group">
                                    <input 
                                        class="input" 
                                        v-model="form.weight" 
                                        type="number" 
                                        placeholder="Enter product weight"
                                    />
                                    <span class="btn btn-input" style="border-color: #d8d8d8;">
                                        Kg
                                    </span>
                                </div>
                            </div>

                            <!-- is_active -->
                            <div class="mb-4">
                                <label class="form-label mb-1">Status</label>
                                <select 
                                    class="select" 
                                    v-model="form.is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-between p-5">
                        <button class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                        <button class="btn btn-primary" type="submit">Create</button>
                    </div>
                </form>
            </div>
        </div>


        <!-- View Product Modal -->
        <div class="modal pb-10" data-modal="true" id="modal_view_product">
            <div class="modal-content max-w-[600px] top-[10%]">
                <div class="modal-header">
                    <h3 class="modal-title">View Product</h3>
                    <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="selectedProduct">
                        <div class="p-5">
                            <div class="flex flex-row gap-3 mb-4">
                                <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Status</label>
                                <p class="!text-gray-500">
                                    <span v-if="selectedProduct.is_active" class="badge badge-outline badge-success">Active</span>
                                    <span v-else class="badge badge-outline badge-danger">Inactive</span>
                                </p>
                            </div>
                            <div class="p-5 mb-10 bg-white border-gray-300 border rounded-xl shadow-lg space-y-2 sm:py-4 sm:flex sm:items-start sm:space-y-0 sm:space-x-6">
                                <div class="mb-5">
                                    <!-- <img :src="'https://picsum.photos/500'" alt="Product Image" class="sm:w-full lg:max-w-[200px] h-auto rounded shadow-lg" /> -->
                                    <!-- <p v-else class="!text-gray-500">No image available</p> -->
                                    <div class="flex items-center justify-center text-4xl w-28 h-28 rounded-lg bg-teal-100 text-teal-700 font-bold border border-teal-400 shrink-0">
                                        <!-- Display initials -->
                                        {{ selectedProduct.name.split(' ').length > 1 
                                            ? selectedProduct.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('') 
                                            : selectedProduct.name[0].toUpperCase() 
                                        }}
                                    </div>
                                </div>
                                <div class="flex-grow w-full">
                                    <div class="mb-5">
                                        <label class="form-label mb-1 !font-extrabold text-md !text-gray-600">Product Name</label>
                                        <p class="!text-gray-500">{{ selectedProduct.name }}</p>
                                    </div>
                                    <div class="w-full">
                                        <label class="form-label mb-1 !font-extrabold text-md !text-gray-600">Description</label>
                                        <p class="!text-gray-500 text-sm">{{ selectedProduct.description }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-3 justify-between">
                                <div>
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Supplier (Client)</label>
                                    <p class="!text-gray-500">{{ selectedProduct.client }}</p>
                                </div>
                                <div>
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Category</label>
                                    <p class="badge badge-outline badge-secondary">{{ selectedProduct.category }}</p>
                                </div>
                                <div>
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Brand</label>
                                    <p class="!text-gray-500">{{ selectedProduct.brand }}</p>
                                </div>
                            </div>
                            <!-- horizontal line -->
                            <hr class="my-5 border-gray-300" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">SKU</label>
                                    <p class="!text-gray-500">{{ selectedProduct.sku }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Barcode</label>
                                    <p class="!text-gray-500">{{ selectedProduct.barcode }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Price</label>
                                    <p class="!text-gray-500">{{ selectedProduct.price }} {{ selectedProduct.currency }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Cost Price / Base Price</label>
                                    <p class="!text-gray-500">{{ selectedProduct.cost_price }} {{ selectedProduct.currency }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Stock Quantity</label>
                                    <p class="!text-gray-500">{{ selectedProduct.stock_quantity }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Minimum Stock Level</label>
                                    <p class="!text-gray-500">{{ selectedProduct.min_stock_level }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Weight</label>
                                    <p class="!text-gray-500">{{ selectedProduct.weight }} Kg</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Dimensions</label>
                                    <p class="!text-gray-500">{{ selectedProduct.dimensions }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="h-[350px]">
                        <div role="status" class="max-w-sm animate-pulse p-10">
                            <div class="mb-10">
                                <div class="h-5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px] mb-2.5"></div>
                                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 mb-2.5"></div>
                            </div>
                            <div class="mb-10">
                                <div class="h-5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px] mb-2.5"></div>
                                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 mb-2.5"></div>
                            </div>
                            <div class="mb-10">
                                <div class="h-5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px] mb-2.5"></div>
                                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 mb-2.5"></div>
                            </div>
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-end">
                    <div class="flex gap-4">
                        <button class="btn btn-light" data-modal-dismiss="true">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
