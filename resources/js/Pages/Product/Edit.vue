<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const product = ref({ ...props.product });
const customers = ref({ ...props.customers });
const brands = ref({ ...props.brands });
const productCategories = ref({ ...props.productCategories });
const isLoading = ref(false);

// Stock status helpers
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

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

const updateProduct = () => {
    isLoading.value = true;
    axios.put(`/products/api/update/${product.value.id}`, product.value)
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
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'An error occurred while updating the product.',
            });
            console.error(error);
        })
        .finally(() => {
            isLoading.value = false;
        });
};
</script>

<template>
    <AppLayout title="Edit Product">
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/products/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Product
                </h2>
            </div>
        </template>

        <div class="container-fixed py-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Product Form -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Product Information</h3>
                        </div>
                        <form class="card-body" @submit.prevent="updateProduct">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <!-- Product Name -->
                                <div class="md:col-span-2">
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Product Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        class="input w-full"
                                        placeholder="Enter Product Name"
                                        type="text"
                                        v-model="product.name"
                                        required
                                    />
                                </div>

                                <!-- Product Description -->
                                <div class="md:col-span-2">
                                    <label class="form-label text-gray-700 dark:text-gray-300">Description</label>
                                    <textarea
                                        class="textarea w-full"
                                        placeholder="Enter Product Description"
                                        rows="3"
                                        v-model="product.description"
                                    ></textarea>
                                </div>

                                <!-- Category -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Category <span class="text-danger">*</span>
                                    </label>
                                    <select class="select w-full" v-model="product.mst_product_category_id" required>
                                        <option value="" disabled>Select Category</option>
                                        <option v-for="(category, id) in productCategories" :key="id" :value="id">
                                            {{ category }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Brand -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">Brand</label>
                                    <select class="select w-full" v-model="product.mst_brand_id">
                                        <option value="">Select Brand (Optional)</option>
                                        <option v-for="(brand, id) in brands" :key="id" :value="id">
                                            {{ brand }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Customer (Optional) -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Supplier/Customer <span class="text-gray-400 text-xs">(Optional)</span>
                                    </label>
                                    <select class="select w-full" v-model="product.mst_client_id">
                                        <option value="">Select Supplier (Optional)</option>
                                        <option v-for="(customer_name, id) in customers" :key="id" :value="id">
                                            {{ customer_name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">Status</label>
                                    <select class="select w-full" v-model="product.is_active">
                                        <option :value="true">Active</option>
                                        <option :value="false">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200 dark:border-gray-700 my-5"></div>

                            <!-- Identifiers & Pricing -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <!-- SKU -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">SKU</label>
                                    <input
                                        class="input w-full"
                                        placeholder="Stock Keeping Unit"
                                        type="text"
                                        v-model="product.sku"
                                    />
                                </div>

                                <!-- Barcode -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">Barcode</label>
                                    <input
                                        class="input w-full"
                                        placeholder="Product Barcode"
                                        type="text"
                                        v-model="product.barcode"
                                    />
                                </div>

                                <!-- Selling Price -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Selling Price <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="btn btn-input">IDR</span>
                                        <input
                                            class="input w-full"
                                            placeholder="Selling Price"
                                            type="number"
                                            step="0.01"
                                            v-model="product.price"
                                            required
                                        />
                                    </div>
                                </div>

                                <!-- Cost Price (for reference) -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Base Cost Price <span class="text-gray-400 text-xs">(Reference)</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="btn btn-input">IDR</span>
                                        <input
                                            class="input w-full"
                                            placeholder="Base Cost"
                                            type="number"
                                            step="0.01"
                                            v-model="product.cost_price"
                                        />
                                    </div>
                                </div>

                                <!-- Weight -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">Weight</label>
                                    <div class="input-group">
                                        <input
                                            class="input w-full"
                                            placeholder="Product Weight"
                                            type="number"
                                            step="0.01"
                                            v-model="product.weight"
                                        />
                                        <span class="btn btn-input">Kg</span>
                                    </div>
                                </div>

                                <!-- Reorder Level -->
                                <div>
                                    <label class="form-label text-gray-700 dark:text-gray-300">
                                        Reorder Level
                                        <i class="ki-filled ki-information-3 text-gray-400 ms-1" title="Alert when stock falls below this level"></i>
                                    </label>
                                    <input
                                        class="input w-full"
                                        placeholder="Minimum stock before reorder"
                                        type="number"
                                        v-model="product.reorder_level"
                                    />
                                </div>
                            </div>

                            <!-- Footer Buttons -->
                            <div class="flex justify-between pt-5 border-t border-gray-200 dark:border-gray-700">
                                <Link href="/products/list" class="btn btn-light">
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

                <!-- Right Column: Stock Information (Read-only) -->
                <div class="space-y-5">
                    <!-- Stock Status Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-parcel text-gray-500 me-2"></i>
                                Inventory Status
                            </h3>
                            <span :class="['badge badge-sm whitespace-nowrap', stockStatusColors[product.stock_status] || 'badge-secondary']">
                                {{ stockStatusLabels[product.stock_status] || 'Unknown' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                <!-- Quantity On Hand -->
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Quantity On Hand</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ formatNumber(product.quantity_on_hand) }}
                                    </span>
                                </div>

                                <!-- Available -->
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Available</span>
                                    <span class="text-lg font-semibold text-success">
                                        {{ formatNumber(product.quantity_available) }}
                                    </span>
                                </div>

                                <!-- Reserved -->
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Reserved (SO)</span>
                                    <span class="text-lg font-semibold text-warning">
                                        {{ formatNumber(product.quantity_reserved) }}
                                    </span>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <!-- Average Cost -->
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Average Cost</span>
                                        <span class="text-md font-medium">
                                            {{ formatCurrency(product.average_cost) }}
                                        </span>
                                    </div>

                                    <!-- Reorder Level -->
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Reorder Level</span>
                                        <span class="text-md font-medium">
                                            {{ formatNumber(product.reorder_level) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500">
                                    <i class="ki-filled ki-information-2 me-1"></i>
                                    Stock quantities are managed through Purchase Orders and Sales Orders.
                                    Use inventory adjustments for manual corrections.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body space-y-3">
                            <Link :href="`/inventory/adjustments/create?product_id=${product.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-setting-2 me-2"></i>
                                Adjust Stock
                            </Link>
                            <Link :href="`/inventory/movements?product_id=${product.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-chart-line me-2"></i>
                                View Movements
                            </Link>
                        </div>
                    </div>
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
