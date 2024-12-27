<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const product = ref({ ...props.product });
const clients = ref({ ...props.clients });
const brands = ref({ ...props.brands });
const productCategories = ref({ ...props.productCategories });

const updateProduct = () => {
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
                text: error.response.data.message || 'An error occurred while updating the product.',
            });
            console.error(error);
        });
};
</script>

<template>
    <AppLayout title="Edit Client">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Client
            </h2>
        </template>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Edit Product
                        </h3>
                    </div>
                    <form class="card-body" @submit.prevent="updateProduct">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- First Column -->
                            <div class="grid grid-cols-1 gap-5 p-5">
                                <!-- Product Name -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Product Name</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Product Name"
                                        type="text"
                                        v-model="product.name"
                                        required
                                    />
                                </div>
                                
                                <!-- Product Description -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Product Description</label>
                                    <textarea
                                        class="textarea"
                                        placeholder="Enter Product Description"
                                        rows="4"
                                        v-model="product.description"
                                    ></textarea>
                                </div>
                                
                                <!-- Category -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Category</label>
                                    <select
                                        class="select"
                                        v-model="product.mst_product_category_id"
                                        required
                                    >
                                        <option value="" disabled>Select Category</option>
                                        <option v-for="(category, id) in productCategories" :key="id" :value="id">
                                            {{ category }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Brand -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Brand</label>
                                    <select
                                        class="select"
                                        v-model="product.mst_brand_id"
                                        required
                                    >
                                        <option value="" disabled>Select Brand</option>
                                        <option v-for="(brand, id) in brands" :key="id" :value="id">
                                            {{ brand }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Client -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Client</label>
                                    <select
                                        class="select"
                                        v-model="product.mst_client_id"
                                        required
                                    >
                                        <option value="" disabled>Select Client</option>
                                        <option v-for="(client_name, id) in clients" :key="id" :value="id">
                                            {{ client_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Second Column -->
                            <div class="grid grid-cols-2 gap-5 p-5">
                                <!-- SKU -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">SKU</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Stock Keeping Unit"
                                        type="text"
                                        v-model="product.sku"
                                    />
                                </div>
                                
                                <!-- Barcode -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Barcode</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Barcode"
                                        type="text"
                                        v-model="product.barcode"
                                    />
                                </div>

                                <!-- Stock -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Stock</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Stock Quantity"
                                        type="number"
                                        v-model="product.stock_quantity"
                                    />
                                </div>
                                
                                <!-- Price -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Price</label>
                                    <div class="input-group">
                                        <span class="btn btn-input" style="border-color: #d8d8d8;">
                                            IDR
                                        </span>
                                        <input
                                            class="input"
                                            placeholder="Enter Product Price"
                                            type="number"
                                            step="0.01"
                                            v-model="product.price"
                                            required
                                        />
                                    </div>
                                </div>
                                
                                <!-- Cost Price -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Cost Price / Base Price</label>
                                    <div class="input-group">
                                        <span class="btn btn-input" style="border-color: #d8d8d8;">
                                            IDR
                                        </span>
                                        <input
                                            class="input"
                                            placeholder="Enter Cost Price"
                                            type="number"
                                            step="0.01"
                                            v-model="product.cost_price"
                                            required
                                        />
                                    </div>
                                </div>
                                
                                <!-- Minimum Stock Level -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Minimum Stock Level</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Minimum Stock Level"
                                        type="number"
                                        v-model="product.min_stock_level"
                                    />
                                </div>
                                
                                <!-- Weight -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Weight</label>
                                    <div class="input-group">
                                        <input
                                            class="input"
                                            placeholder="Enter Product Weight"
                                            type="number"
                                            v-model="product.weight"
                                        />
                                        <span class="btn btn-input" style="border-color: #d8d8d8;">
                                            Kg
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Status -->
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Status</label>
                                    <select
                                        class="select"
                                        v-model="product.is_active"
                                    >
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="card-footer flex justify-between !px-5 py-5">
                            <Link href="/products/list" class="btn btn-light">Back</Link>
                            <button type="submit" class="btn btn-success justify-center md:w-[200px]">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
