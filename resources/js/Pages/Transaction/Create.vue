<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';

const form = ref({
    id: [],
    mst_client_id: '',
    products: [],
    transaction_date: '',
    total_price: 0,
});

const props = defineProps({
    transactions: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    clients: {
        type: Array,
        required: true,
    },
});

const availableProducts = ref([]);
const searchQuery = ref('');

// Manage selected products
const toggleProductSelection = (product) => {
    // Check if the product is already selected
    const productIndex = form.value.products.findIndex(p => p.id === product.id);

    if (productIndex !== -1) {
        // Remove product if already selected
        form.value.products = form.value.products.filter((_, index) => index !== productIndex);
    } else {
        // Add product with default quantity
        form.value.products = [
            ...form.value.products,
            {
                id: product.id,
                price: product.price,
                quantity: 1,
                stock_quantity: product.stock_quantity,
                name: product.name,
                cost_price: product.cost_price,
                sku: product.sku,
            },
        ];
    }
};

// Update total price whenever form.products changes
watch(
    () => form.value.products,
    (newProducts) => {
        form.value.total_price = newProducts.reduce((total, product) => {
            let total_price = total + product.price * product.quantity;
            return total_price;
        }, 0);
    },
    { deep: true } // Ensure deep watching for nested changes
);

// Update available products based on client selection
watch(() => form.value.mst_client_id, (newClientId) => {
    form.value.products = []; // Clear selected products when client changes
    const selectedClient = props.clients.find(client => client.id === newClientId);
    availableProducts.value = selectedClient?.products || [];
});

// Filter products by search query
watch(searchQuery, (newQuery) => {
    const clientProducts = props.clients
        .find(client => client.id === form.value.mst_client_id)?.products || [];
    availableProducts.value = clientProducts.filter(product =>
        product.name.toLowerCase().includes(newQuery.toLowerCase())
    );
});

// Debounced search input handling
let debounceTimeout;
const handleSearchInput = (event) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        searchQuery.value = event.target.value;
    }, 300);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const clearSearch = () => {
    searchQuery.value = '';
};
</script>

<template>
    <AppLayout title="Transactions">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Transactions
            </h2>
        </template>

        <!-- Container -->
        <div class="container mx-auto p-6">
            <div class="flex flex-col lg:flex-row gap-7 lg:gap-10">
                <!--begin::Order details-->
                <div class="bg-white shadow rounded-lg p-6 w-full lg:w-2/3">
                    <!--begin::Card header-->
                    <div class="border-b pb-4 mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">New Transaction</h2>
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="flex flex-col gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Add products to this order
                            </label>
                            <!-- Display instructions if no products are selected -->
                            <div v-if="form.products.length === 0"
                                class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 border border-dashed rounded-lg p-4 max-h-72 overflow-auto bg-gray-50">
                                <span class="text-gray-500 text-sm">
                                    Select one or more products from the list below by ticking the checkbox.
                                </span>
                            </div>
                            <!-- Display selected products -->
                            <div v-else
                                class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 border border-dashed rounded-lg max-h-72 overflow-auto">
                                <div v-for="product in form.products" :key="product.id"
                                    class="flex items-center border border-1 border-gray-400 border-dashed rounded-lg p-4 bg-white">
                                    <!-- Product image -->
                                    <a href="#" class="w-12 h-12 rounded-lg overflow-hidden border-2 border-success">
                                        <img class="object-cover w-full h-full" :src="'https://picsum.photos/500'" alt="Product Image" />
                                    </a>
                                    <!-- Product details -->
                                    <div class="ml-4">
                                        <a href="#" class="text-gray-800 hover:text-primary font-medium text-sm">
                                            {{ product.name }}
                                        </a>
                                        <div class="text-sm text-gray-600">
                                            <div>Sell Price: <span>{{ formatCurrency(product.price) }}</span></div>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            SKU: {{ product.sku ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total price display -->
                            <div class="mt-4 text-lg font-bold text-gray-700">
                                Total Cost: <span>{{ formatCurrency(form.total_price) }}</span>
                            </div>
                        </div>

                        <!--end::Input group-->
                        <!--begin::Separator-->
                        <div class="border-t"></div>
                        <!--end::Separator-->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Client Dropdown -->
                            <div class="mb-4">
                                <label class="form-label max-w-60 mb-2">Client <span class="ms-1 text-danger">*</span></label>
                                <select class="select" v-model="form.mst_client_id">
                                    <option value="" disabled selected>Select Client</option>
                                    <option v-for="client in props.clients" :key="client.id" :value="client.id">
                                        {{ client.client_name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Transaction Date -->
                            <div class="mb-4">
                                <label class="form-label max-w-60 mb-2">Transaction Date</label>
                                <input class="input" name="transaction_date" type="date" v-model="form.transaction_date" />
                            </div>
                        </div>
                        <!--begin::Search products-->
                        <div class="flex items-center relative max-w-[300px] ">
                            <!-- Search Icon -->
                            <i class="ki-filled ki-magnifier absolute ms-4"></i>

                            <!-- Input Field -->
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                @input="handleSearchInput"
                                class="w-full rounded-md border-gray-300 shadow-sm pl-10 pr-10 py-2 focus:ring-primary focus:border-primary" 
                                placeholder="Search Products Name" 
                            />

                            <!-- Clear Button -->
                            <button 
                                v-if="searchQuery" 
                                @click="clearSearch" 
                                class="absolute right-2 flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200"
                                aria-label="Clear Search">
                                <i class="ki-outline ki-cross text-gray-500"></i>
                            </button>
                        </div>
                        <!--end::Search products-->
                        <!--begin::Table-->
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="text-gray-500 text-xs font-bold uppercase border-b">
                                    <th class="py-3 px-2 w-6 pr-2"></th>
                                    <th class="py-3 px-4">Product</th>
                                    <th class="py-3 px-4 text-right">Qty Remaining</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <tr v-if="availableProducts.length" class="hover:bg-gray-100" v-for="(product, index) in availableProducts" :key="index">
                                    <td class="py-2 px-2 w-6 pr-2">
                                        <input class="checkbox" type="checkbox" 
                                            :value="product.id"
                                            :checked="form.products.some(p => p.id === product.id)"
                                            @change="toggleProductSelection(product)" />
                                    </td>
                                    <td class="py-2 px-4">
                                        <div class="flex items-center">
                                            <!--begin::Thumbnail-->
                                            <a href="#" class="w-12 h-12 rounded-lg overflow-hidden border-2 border-success">
                                                <img class="object-cover w-full h-full" src="https://picsum.photos/500" alt="Product Image">
                                            </a>
                                            <!--end::Thumbnail-->
                                            <div class="ml-4">
                                                <!--begin::Title-->
                                                <a href="#" class="text-gray-800 hover:text-primary font-medium text-sm">
                                                    {{ product.name }}
                                                </a>
                                                <!--end::Title-->
                                                <!--begin::Price-->
                                                <div class="text-sm text-gray-600">
                                                    <div>Sell Price: <span>{{ formatCurrency(product.price) }}</span></div> 
                                                    <div class="mb-2">Stock Price: <span>{{ formatCurrency(product.cost_price) }}</span></div>
                                                </div>
                                                <!--end::Price-->
                                                <!--begin::SKU-->
                                                <div class="text-xs text-gray-500">
                                                    SKU: {{ product.sku ?? 'N/A' }}
                                                </div>
                                                <!--end::SKU-->
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-right">
                                        <span class="font-semibold">{{ product.stock_quantity }}</span>
                                    </td>
                                </tr>
                                <tr v-else class="text-center">
                                    <td colspan="3" class="py-4">No products added yet.</td>
                                </tr>
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Order details-->
            </div>
        </div>
    </AppLayout>
</template>
