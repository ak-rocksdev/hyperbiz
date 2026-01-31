<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const product = ref({ ...props.product });
const customers = ref({ ...props.customers });
const brands = ref({ ...props.brands });
const productCategories = ref({ ...props.productCategories });
const productUoms = ref([...(props.productUoms || [])]);
const uoms = ref(props.uoms || []);
const isLoading = ref(false);
const activeTab = ref('basic');

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
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0
    }).format(Math.floor(value || 0));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

// Product Update
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

// UoM Configuration
const showAddUomModal = ref(false);
const showEditUomModal = ref(false);
const isUomLoading = ref(false);

const newUom = ref({
    uom_id: null,
    is_base_uom: false,
    is_purchase_uom: true,
    is_sales_uom: true,
    is_default_purchase: false,
    is_default_sales: false,
    conversion_factor: 1,
    default_purchase_price: null,
    default_sales_price: null,
    is_active: true,
});

const editingUom = ref(null);

const resetNewUom = () => {
    newUom.value = {
        uom_id: null,
        is_base_uom: false,
        is_purchase_uom: true,
        is_sales_uom: true,
        is_default_purchase: false,
        is_default_sales: false,
        conversion_factor: 1,
        default_purchase_price: null,
        default_sales_price: null,
        is_active: true,
    };
};

const openAddUomModal = () => {
    resetNewUom();
    // If no UoMs configured, make this the base UoM by default
    if (productUoms.value.length === 0) {
        newUom.value.is_base_uom = true;
        newUom.value.is_default_purchase = true;
        newUom.value.is_default_sales = true;
    }
    showAddUomModal.value = true;
};

const closeAddUomModal = () => {
    showAddUomModal.value = false;
    resetNewUom();
};

const saveNewUom = () => {
    if (!newUom.value.uom_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: 'Please select a UoM.',
        });
        return;
    }

    isUomLoading.value = true;
    axios.post(`/products/api/${product.value.id}/uoms`, newUom.value)
        .then(response => {
            productUoms.value.push(response.data.productUom);
            closeAddUomModal();
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
                text: error.response?.data?.message || 'Failed to add UoM.',
            });
        })
        .finally(() => {
            isUomLoading.value = false;
        });
};

const openEditUomModal = (uom) => {
    editingUom.value = { ...uom };
    showEditUomModal.value = true;
};

const closeEditUomModal = () => {
    showEditUomModal.value = false;
    editingUom.value = null;
};

const updateUom = () => {
    if (!editingUom.value) return;

    isUomLoading.value = true;
    axios.put(`/products/api/${product.value.id}/uoms/${editingUom.value.id}`, editingUom.value)
        .then(response => {
            const index = productUoms.value.findIndex(u => u.id === editingUom.value.id);
            if (index !== -1) {
                productUoms.value[index] = response.data.productUom;
            }
            // Reload to get updated base/default flags
            loadProductUoms();
            closeEditUomModal();
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
                text: error.response?.data?.message || 'Failed to update UoM.',
            });
        })
        .finally(() => {
            isUomLoading.value = false;
        });
};

const deleteUom = (uom) => {
    Swal.fire({
        title: 'Delete UoM?',
        text: `Are you sure you want to remove "${uom.uom_code} - ${uom.uom_name}" from this product?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/products/api/${product.value.id}/uoms/${uom.id}`)
                .then(response => {
                    productUoms.value = productUoms.value.filter(u => u.id !== uom.id);
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
                        text: error.response?.data?.message || 'Failed to delete UoM.',
                    });
                });
        }
    });
};

const loadProductUoms = () => {
    axios.get(`/products/api/${product.value.id}/uoms`)
        .then(response => {
            productUoms.value = response.data.productUoms;
        })
        .catch(error => {
            console.error('Failed to load product UoMs:', error);
        });
};

// Available UoMs for dropdown (exclude already configured)
const availableUoms = computed(() => {
    const configuredUomIds = productUoms.value.map(u => u.uom_id);
    return uoms.value.filter(u => !configuredUomIds.includes(u.value));
});

// Get base UoM info
const baseUom = computed(() => {
    return productUoms.value.find(u => u.is_base_uom);
});

// Stats for UoM tab
const uomStats = computed(() => {
    return {
        total: productUoms.value.length,
        active: productUoms.value.filter(u => u.is_active).length,
        purchase: productUoms.value.filter(u => u.is_purchase_uom).length,
        sales: productUoms.value.filter(u => u.is_sales_uom).length,
    };
});
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
                <!-- Left Column: Tabs Content -->
                <div class="lg:col-span-2">
                    <!-- Tab Navigation -->
                    <div class="card mb-5">
                        <div class="card-body p-0">
                            <div class="flex border-b border-gray-200 dark:border-gray-700">
                                <button
                                    @click="activeTab = 'basic'"
                                    :class="[
                                        'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                                        activeTab === 'basic'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <i class="ki-filled ki-document me-2"></i>
                                    Basic Information
                                </button>
                                <button
                                    @click="activeTab = 'uom'"
                                    :class="[
                                        'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                                        activeTab === 'uom'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <i class="ki-filled ki-cube-2 me-2"></i>
                                    Units of Measure
                                    <span class="badge badge-sm badge-light ms-2">{{ productUoms.length }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Info Tab -->
                    <div v-show="activeTab === 'basic'">
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

                    <!-- UoM Configuration Tab -->
                    <div v-show="activeTab === 'uom'">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-cube-2 text-gray-500 me-2"></i>
                                    Units of Measure Configuration
                                </h3>
                                <button @click="openAddUomModal" class="btn btn-primary btn-sm">
                                    <i class="ki-filled ki-plus me-1"></i> Add UoM
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Stats Row -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ uomStats.total }}</div>
                                        <div class="text-xs text-gray-500">Total UoMs</div>
                                    </div>
                                    <div class="bg-success-light rounded-lg p-3 text-center">
                                        <div class="text-2xl font-bold text-success">{{ uomStats.active }}</div>
                                        <div class="text-xs text-gray-500">Active</div>
                                    </div>
                                    <div class="bg-info-light rounded-lg p-3 text-center">
                                        <div class="text-2xl font-bold text-info">{{ uomStats.purchase }}</div>
                                        <div class="text-xs text-gray-500">For Purchase</div>
                                    </div>
                                    <div class="bg-primary-light rounded-lg p-3 text-center">
                                        <div class="text-2xl font-bold text-primary">{{ uomStats.sales }}</div>
                                        <div class="text-xs text-gray-500">For Sales</div>
                                    </div>
                                </div>

                                <!-- Base UoM Info -->
                                <div v-if="baseUom" class="bg-primary-light border border-primary/20 rounded-lg p-4 mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center">
                                            <i class="ki-filled ki-star text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-500">Base Unit of Measure</div>
                                            <div class="font-semibold text-gray-900 dark:text-white">
                                                {{ baseUom.uom_code }} - {{ baseUom.uom_name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="bg-warning-light border border-warning/20 rounded-lg p-4 mb-5">
                                    <div class="flex items-center gap-3">
                                        <i class="ki-filled ki-information-2 text-warning text-xl"></i>
                                        <div>
                                            <div class="font-medium text-warning-active">No Base UoM Configured</div>
                                            <div class="text-sm text-gray-500">Add a UoM and mark it as the base unit.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- UoM Table -->
                                <div class="overflow-x-auto">
                                    <table class="table table-border">
                                        <thead>
                                            <tr>
                                                <th class="w-12">#</th>
                                                <th>UoM</th>
                                                <th class="text-center">Conversion</th>
                                                <th class="text-center">Usage</th>
                                                <th class="text-center">Default Price</th>
                                                <th class="text-center">Status</th>
                                                <th class="w-28 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="productUoms.length === 0">
                                                <td colspan="7" class="text-center py-8 text-gray-500">
                                                    <i class="ki-filled ki-cube-2 text-4xl text-gray-300 mb-3"></i>
                                                    <div>No UoMs configured for this product.</div>
                                                    <button @click="openAddUomModal" class="btn btn-light btn-sm mt-3">
                                                        <i class="ki-filled ki-plus me-1"></i> Add First UoM
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-for="(uom, index) in productUoms" :key="uom.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="text-gray-500">{{ index + 1 }}</td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold">
                                                            {{ uom.uom_code }}
                                                        </div>
                                                        <div>
                                                            <div class="font-medium text-gray-900 dark:text-white">{{ uom.uom_name }}</div>
                                                            <div class="flex gap-1 mt-1">
                                                                <span v-if="uom.is_base_uom" class="badge badge-xs badge-primary">Base</span>
                                                                <span v-if="uom.is_default_purchase" class="badge badge-xs badge-info">Def. Purchase</span>
                                                                <span v-if="uom.is_default_sales" class="badge badge-xs badge-success">Def. Sales</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span v-if="uom.is_base_uom" class="text-gray-400">1 (Base)</span>
                                                    <span v-else class="font-mono">{{ uom.conversion_factor }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="flex justify-center gap-2">
                                                        <span v-if="uom.is_purchase_uom" class="badge badge-sm badge-outline badge-info">Purchase</span>
                                                        <span v-if="uom.is_sales_uom" class="badge badge-sm badge-outline badge-success">Sales</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="text-xs space-y-1">
                                                        <div v-if="uom.default_purchase_price" class="text-info">
                                                            Buy: {{ formatCurrency(uom.default_purchase_price) }}
                                                        </div>
                                                        <div v-if="uom.default_sales_price" class="text-success">
                                                            Sell: {{ formatCurrency(uom.default_sales_price) }}
                                                        </div>
                                                        <div v-if="!uom.default_purchase_price && !uom.default_sales_price" class="text-gray-400">-</div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span :class="['badge badge-sm', uom.is_active ? 'badge-success' : 'badge-secondary']">
                                                        {{ uom.is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="flex justify-center gap-1">
                                                        <button @click="openEditUomModal(uom)" class="btn btn-icon btn-xs btn-light" title="Edit">
                                                            <i class="ki-filled ki-pencil"></i>
                                                        </button>
                                                        <button
                                                            @click="deleteUom(uom)"
                                                            class="btn btn-icon btn-xs btn-light text-danger"
                                                            :disabled="uom.is_base_uom || productUoms.length <= 1"
                                                            :title="uom.is_base_uom ? 'Cannot delete base UoM' : 'Delete'"
                                                        >
                                                            <i class="ki-filled ki-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Help Text -->
                                <div class="mt-5 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="ki-filled ki-information-2 me-1"></i> About UoM Configuration
                                    </h4>
                                    <ul class="text-xs text-gray-500 space-y-1 list-disc list-inside">
                                        <li><strong>Base UoM</strong>: The smallest unit used for inventory tracking. All conversions are relative to this.</li>
                                        <li><strong>Conversion Factor</strong>: How many base units equal 1 of this UoM. Example: 1 Box = 12 Pieces (factor = 12).</li>
                                        <li><strong>Purchase/Sales UoM</strong>: Enable this UoM for purchase orders or sales orders.</li>
                                        <li><strong>Default</strong>: Pre-selected UoM when creating new orders.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
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

        <!-- Add UoM Modal -->
        <div v-if="showAddUomModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="closeAddUomModal"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="ki-filled ki-plus-square me-2 text-primary"></i>
                        Add Unit of Measure
                    </h3>
                    <button @click="closeAddUomModal" class="btn btn-icon btn-sm btn-light">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- UoM Selection -->
                    <div>
                        <label class="form-label">Unit of Measure <span class="text-danger">*</span></label>
                        <SearchableSelect
                            v-model="newUom.uom_id"
                            :options="availableUoms"
                            placeholder="Select UoM"
                        />
                    </div>

                    <!-- Flags Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="newUom.is_base_uom" />
                            <span class="text-sm">Base UoM</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="newUom.is_active" />
                            <span class="text-sm">Active</span>
                        </label>
                    </div>

                    <!-- Usage Flags -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="newUom.is_purchase_uom" />
                            <span class="text-sm">For Purchase</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="newUom.is_sales_uom" />
                            <span class="text-sm">For Sales</span>
                        </label>
                    </div>

                    <!-- Default Flags -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="newUom.is_default_purchase" :disabled="!newUom.is_purchase_uom" />
                            <span class="text-sm" :class="!newUom.is_purchase_uom ? 'text-gray-400' : ''">Default Purchase</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="newUom.is_default_sales" :disabled="!newUom.is_sales_uom" />
                            <span class="text-sm" :class="!newUom.is_sales_uom ? 'text-gray-400' : ''">Default Sales</span>
                        </label>
                    </div>

                    <!-- Conversion Factor -->
                    <div v-if="!newUom.is_base_uom">
                        <label class="form-label">
                            Conversion Factor
                            <span class="text-gray-400 text-xs">(1 of this UoM = X base units)</span>
                        </label>
                        <input
                            type="number"
                            class="input w-full"
                            v-model="newUom.conversion_factor"
                            step="0.000001"
                            min="0.000001"
                            placeholder="e.g., 12 for Box of 12"
                        />
                    </div>

                    <!-- Pricing -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Default Purchase Price</label>
                            <div class="input-group">
                                <span class="btn btn-input text-xs">IDR</span>
                                <input
                                    type="number"
                                    class="input w-full"
                                    v-model="newUom.default_purchase_price"
                                    step="0.01"
                                    min="0"
                                    placeholder="Optional"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Default Sales Price</label>
                            <div class="input-group">
                                <span class="btn btn-input text-xs">IDR</span>
                                <input
                                    type="number"
                                    class="input w-full"
                                    v-model="newUom.default_sales_price"
                                    step="0.01"
                                    min="0"
                                    placeholder="Optional"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                    <button @click="closeAddUomModal" class="btn btn-light">Cancel</button>
                    <button @click="saveNewUom" class="btn btn-primary" :disabled="isUomLoading">
                        <span v-if="isUomLoading" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isUomLoading ? 'Saving...' : 'Add UoM' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit UoM Modal -->
        <div v-if="showEditUomModal && editingUom" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="closeEditUomModal"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="ki-filled ki-pencil me-2 text-primary"></i>
                        Edit UoM: {{ editingUom.uom_code }}
                    </h3>
                    <button @click="closeEditUomModal" class="btn btn-icon btn-sm btn-light">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- UoM Display (readonly) -->
                    <div>
                        <label class="form-label">Unit of Measure</label>
                        <div class="input w-full bg-gray-50 dark:bg-gray-800 cursor-not-allowed">
                            {{ editingUom.uom_code }} - {{ editingUom.uom_name }}
                        </div>
                    </div>

                    <!-- Flags Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="editingUom.is_base_uom" />
                            <span class="text-sm">Base UoM</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="editingUom.is_active" />
                            <span class="text-sm">Active</span>
                        </label>
                    </div>

                    <!-- Usage Flags -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="editingUom.is_purchase_uom" />
                            <span class="text-sm">For Purchase</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="editingUom.is_sales_uom" />
                            <span class="text-sm">For Sales</span>
                        </label>
                    </div>

                    <!-- Default Flags -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="editingUom.is_default_purchase" :disabled="!editingUom.is_purchase_uom" />
                            <span class="text-sm" :class="!editingUom.is_purchase_uom ? 'text-gray-400' : ''">Default Purchase</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox" v-model="editingUom.is_default_sales" :disabled="!editingUom.is_sales_uom" />
                            <span class="text-sm" :class="!editingUom.is_sales_uom ? 'text-gray-400' : ''">Default Sales</span>
                        </label>
                    </div>

                    <!-- Conversion Factor -->
                    <div v-if="!editingUom.is_base_uom">
                        <label class="form-label">
                            Conversion Factor
                            <span class="text-gray-400 text-xs">(1 of this UoM = X base units)</span>
                        </label>
                        <input
                            type="number"
                            class="input w-full"
                            v-model="editingUom.conversion_factor"
                            step="0.000001"
                            min="0.000001"
                            placeholder="e.g., 12 for Box of 12"
                        />
                    </div>

                    <!-- Pricing -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Default Purchase Price</label>
                            <div class="input-group">
                                <span class="btn btn-input text-xs">IDR</span>
                                <input
                                    type="number"
                                    class="input w-full"
                                    v-model="editingUom.default_purchase_price"
                                    step="0.01"
                                    min="0"
                                    placeholder="Optional"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Default Sales Price</label>
                            <div class="input-group">
                                <span class="btn btn-input text-xs">IDR</span>
                                <input
                                    type="number"
                                    class="input w-full"
                                    v-model="editingUom.default_sales_price"
                                    step="0.01"
                                    min="0"
                                    placeholder="Optional"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                    <button @click="closeEditUomModal" class="btn btn-light">Cancel</button>
                    <button @click="updateUom" class="btn btn-primary" :disabled="isUomLoading">
                        <span v-if="isUomLoading" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isUomLoading ? 'Saving...' : 'Save Changes' }}
                    </button>
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
