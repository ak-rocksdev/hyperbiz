<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch, computed } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';

    const form = ref({
        id: [],
        mst_client_id: '',
        products: [],
        transaction_date: '',
        grand_total: 0,
        expedition_fee: 0,
        transaction_type: null,
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
        statuses: {
            type: Array,
            required: true, // Pass the statuses array from the backend
        },
    });

    const availableProducts = ref([]);
    const searchQuery = ref('');

    // Manage selected products
    const toggleProductSelection = (product) => {
        const productIndex = form.value.products.findIndex(p => p.id === product.id);

        if (productIndex !== -1) {
            form.value.products = form.value.products.filter((_, index) => index !== productIndex);
        } else {
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
        () => [form.value.products, form.value.expedition_fee],
        ([newProducts, expeditionFee]) => {
            form.value.grand_total = newProducts.reduce((total, product) => {
                return total + (product.price * product.quantity);
            }, 0) + expeditionFee;
        },
        { deep: true }
    );

    // Update available products based on client selection
    watch(() => form.value.mst_client_id, (newClientId) => {
        form.value.products = []; // Clear selected products when client changes
        const selectedClient = props.clients.find(client => client.id === newClientId);

        if (selectedClient?.products) {
            // Convert the products object to an array
            availableProducts.value = Array.isArray(selectedClient.products)
                ? selectedClient.products
                : Object.values(selectedClient.products); // Convert object to array
        } else {
            availableProducts.value = [];
        }
    });

    // Filter products by search query
    watch(searchQuery, (newQuery) => {
        // Get the selected client
        const selectedClient = props.clients.find(client => client.id === form.value.mst_client_id);

        // Convert products to an array
        const clientProducts = Array.isArray(selectedClient?.products)
            ? selectedClient.products
            : Object.values(selectedClient?.products || {});

        // Filter products based on the search query
        availableProducts.value = clientProducts.filter(product => {
            const productName = product.name || ''; // Default to empty string if undefined
            return productName.toLowerCase().includes(newQuery.toLowerCase());
        });

        // Reset to full list if search query is empty
        if (!newQuery.trim()) {
            availableProducts.value = clientProducts;
        }
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

    const errors = ref({});

    const submitTransaction = () => {
        try {
            errors.value = {};
            axios.post('/transaction/api/store', form.value)
                .then(response => {
                    console.log(response.data);
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message
                    });

                    // continue action after success using best practice
                    router.visit(route('transaction.list'));
                }).catch(err => {
                    if (err.response?.data?.errors) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            icon: 'error',
                            title: 'Failed!',
                            text: err.response?.data?.message || 'An error occurred.',
                        });

                        errors.value = err.response.data.errors;
                    }
                });
        } catch (err) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'error',
                title: 'Error',
                text: 'Failed to create new transaction'
            });
        }
    };
    const purchaseButtonClasses = computed(() => {
        return form.value.transaction_type === 'purchase'
            ? 'btn-success text-white'
            : 'bg-white text-gray-800 hover:bg-gray-100 hover:text-green-600';
    });

    const sellButtonClasses = computed(() => {
        return form.value.transaction_type === 'sell'
            ? 'btn-success text-white'
            : 'bg-white text-gray-900 hover:bg-gray-100 hover:text-green-600';
    });
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
                    <div v-if="Object.keys(errors).length"
                        class="bg-red-100 border-l-4 border-red-300 text-red-700 p-4 mb-5" role="alert">
                        <p class="font-bold mb-3">Error!</p>
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>
                    <!--begin::Card body-->
                    <div class="grid lg:grid-cols-4 sm:grid-cols-1 gap-4">
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Transaction Type <span class="ms-1 text-danger">*</span></label>
                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                <button type="button"
                                    :class="['inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-200 rounded-s-lg focus:z-10 focus:ring-0', purchaseButtonClasses]"
                                    @click="form.transaction_type = 'purchase'"
                                >
                                    <i class="ki-solid ki-entrance-left me-2"></i>
                                    Purchase
                                </button>
                                <button type="button"
                                    :class="['inline-flex items-center px-4 py-2 text-sm font-medium border-t border-b border-e rounded-e-lg border-gray-200 focus:z-10 focus:ring-2 focus:ring-blue-700', sellButtonClasses]"
                                    @click="form.transaction_type = 'sell'"
                                >
                                    <i class="ki-solid ki-exit-left me-2"></i>
                                    Sell
                                </button>
                            </div>
                        </div>

                        <!-- Transaction Status -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Transaction Status <span
                                    class="ms-1 text-danger">*</span></label>
                            <select v-model="form.status" class="input" :disabled="!form.transaction_type">
                                <option v-for="status in statuses[form.transaction_type]" :key="status.value"
                                    :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

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
                    <div class="border-t pb-5"></div>
                    <div v-if="form.mst_client_id && form.transaction_type" class="flex flex-col gap-3">
                        <div v-if="form.mst_client_id" class="overflow-x-auto">
                            <label v-if="form.products.length == 0" class="block text-sm font-medium text-gray-700 mb-2">
                                Add products to this order
                            </label>
                            <div v-if="form.products.length === 0"
                                class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 border border-dashed rounded-lg p-4 max-h-72 overflow-auto bg-gray-50">
                                <span class="text-gray-500 text-sm">
                                    Select one or more products from the list below by ticking the checkbox.
                                </span>
                            </div>
                            <!-- Display selected products -->
                            <div v-else class="overflow-x-auto">
                                <table class="table-auto w-full text-sm rounded-lg">
                                    <thead>
                                        <tr class="text-left text-gray-700 border-gray-300 border-b-2 border-t-2">
                                            <th class="py-2 px-4">Product</th>
                                            <th class="py-2 px-4 text-center">Quantity</th>
                                            <th class="py-2 px-4 text-right">Price</th>
                                            <th class="py-2 px-4 text-right">Total</th>
                                            <th class="py-2 px-4 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(product, index) in form.products" :key="index" class="hover:bg-gray-100 border-b border-dashed border-gray-400">
                                            <!-- Product Details -->
                                            <td class="py-4 px-4">
                                                <div class="flex items-center">
                                                    <!-- Product Image -->
                                                    <!-- <a href="#" class="w-12 h-12 rounded-lg overflow-hidden border-2 border-success">
                                                        <img class="object-cover w-full h-full" :src="'https://picsum.photos/500'" alt="Product Image" />
                                                    </a> -->
                                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-teal-100 text-teal-700 font-bold border border-teal-400 shrink-0">
                                                        <!-- Display initials -->
                                                        {{ product.name.split(' ').length > 1 
                                                            ? product.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('') 
                                                            : product.name[0].toUpperCase() 
                                                        }}
                                                    </div>
                                                    <!-- Product Info -->
                                                    <div class="ml-4">
                                                        <a href="#" class="text-gray-800 hover:text-primary font-medium text-sm">
                                                            {{ product.name }}
                                                        </a>
                                                        <div class="text-xs text-gray-500">
                                                            SKU: {{ product.sku ?? 'N/A' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Quantity -->
                                            <td class="py-2 px-4 w-[125px] text-center">
                                                <input
                                                    class="input text-center"
                                                    type="number"
                                                    v-model="product.quantity"
                                                    placeholder="Qty"
                                                    min="1"
                                                />
                                            </td>
                                            <!-- Price -->
                                            <td class="py-2 px-4 text-right w-[150px]">
                                                <input
                                                    class="input text-center"
                                                    type="number"
                                                    v-model="product.price"
                                                    placeholder="Price"
                                                    min="1"
                                                />
                                            </td>
                                            <!-- Line Total -->
                                            <td class="py-2 px-4 text-right w-[150px]">
                                                <span>{{ formatCurrency(product.price * (product.quantity || 1)) }}</span>
                                            </td>
                                            <!-- Remove Button -->
                                            <td class="py-2 px-4 text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline btn-icon btn-danger"
                                                    @click="form.products.splice(index, 1)"
                                                >
                                                    <i class="ki-filled ki-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.products.length === 0">
                                            <td colspan="5" class="text-center text-gray-500 py-4">
                                                No products added yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Grand Total Display -->
                            <div class="flex flex-col py-4 border-gray-300 border-b text-lg font-bold text-gray-700 text-end justify-end">
                                <div class="flex py-2 justify-end items-center">
                                    Expedition Fee 
                                    <div class="input-group ms-3">
                                        <span class="btn btn-input" style="border-color: #d8d8d8;">
                                            IDR
                                        </span>
                                        <input class="input w-[200px]" type="number" v-model="form.expedition_fee" />
                                    </div>
                                </div>
                                <div class="py-2">
                                    Grand Total: <span :class="{'text-green-500': form.grand_total > 0}">{{ formatCurrency(form.grand_total) }}</span>
                                </div>
                            </div>
                            <!-- button submit -->
                            <div class="flex justify-end my-4">
                                <Link :href="route('transaction.list')" class="btn btn-light me-2">Cancel</Link>
                                <button class="btn btn-primary" @click="submitTransaction">Create New Transaction</button>
                            </div>
                        </div>
                        <!--end::Input group-->
                        
                        <!--begin::Search products-->
                        <div class="flex items-center relative max-w-[300px]">
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
                                    <th class="py-3 px-4">Weight</th>
                                    <th class="py-3 px-4 text-right">Qty Remaining</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <tr v-if="availableProducts" class="hover:bg-gray-100" v-for="(product, index) in availableProducts" :key="index">
                                    <td class="py-2 px-2 w-6 pr-2">
                                        <input class="checkbox" type="checkbox" 
                                            :value="product.id"
                                            :checked="form.products.some(p => p.id === product.id)"
                                            @change="toggleProductSelection(product)"
                                            />
                                    </td>
                                    <td class="py-2 px-4">
                                        <div class="flex items-center">
                                            <!--begin::Thumbnail-->
                                            <!-- <a href="#" class="w-12 h-12 rounded-lg overflow-hidden border-2 border-success">
                                                <img class="object-cover w-full h-full" src="https://picsum.photos/500" alt="Product Image">
                                            </a> -->
                                            <div class="flex items-center justify-center text-xl w-16 h-16 rounded-full bg-teal-100 text-teal-700 font-bold border border-teal-400 shrink-0">
                                                <!-- Display initials -->
                                                {{ product.name.split(' ').length > 1 
                                                    ? product.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('') 
                                                    : product.name[0].toUpperCase() 
                                                }}
                                            </div>
                                            <!--end::Thumbnail-->
                                            <div class="ml-4">
                                                <!--begin::Title-->
                                                <a href="#" class="text-gray-800 hover:text-primary font-medium text-sm">
                                                    {{ product.name }}
                                                    <span v-if="product.stock_quantity == 0" class="text-xs text-danger ms-1">(Out of Stock)</span>
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
                                    <td class="py-2 px-4">
                                        <span>{{ product.weight }} Kg</span>
                                    </td>
                                    <td class="py-2 px-4 text-right">
                                        <span :class="{'text-danger': product.stock_quantity < product.min_stock_level}" class="font-semibold">
                                            {{ product.stock_quantity }} Pcs
                                        </span>
                                    </td>
                                </tr>
                                <tr v-else class="text-center">
                                    <td colspan="3" class="py-4">No products added yet.</td>
                                </tr>
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                    <!-- else -->
                    <div v-else>
                        <!-- separator -->
                        <div class="flex items-center justify-center h-52">
                            <!-- message -->
                            <div class="flex flex-col items-center">
                                <i class="ki-solid ki-handcart text-8xl text-gray-300 mb-4"></i>
                                <span class="text-gray-500 text-md">Please Select Client and Transaction Type to Continue</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Order details-->
            </div>
        </div>
    </AppLayout>
</template>
