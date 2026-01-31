<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch, computed, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';

    // Props from the backend
    const props = defineProps({
        transaction: {
            type: Object,
            required: true,
        },
        customers: {
            type: Array,
            required: true,
        },
        statuses: {
            type: Object, // statuses is an object with "purchase" and "sell" arrays
            required: true,
        },
    });

    // Reactive form state
    const form = ref({
        id                  : props.transaction ? props.transaction.id : '',
        transaction_code    : props.transaction ? props.transaction.transaction_code : '',
        transaction_type    : props.transaction ? props.transaction.transaction_type : '',
        customer            : props.transaction ? props.transaction.customer : '',
        mst_customer_id     : props.transaction ? props.transaction.mst_customer_id : '',
        products            : props.transaction ? props.transaction.products : [],
        transaction_date    : props.transaction ? props.transaction.transaction_date : '',
        grand_total         : props.transaction ? props.transaction.grand_total : 0,
        expedition_fee      : props.transaction ? props.transaction.expedition_fee : 0,
        status              : props.transaction ? props.transaction.status : '',
    });

    watch(
        () => form.value.transaction_type,
        (newTransactionType) => {
            if (newTransactionType) {
                const validStatuses = props.statuses[newTransactionType]?.map((status) => status.value) || [];
                if (!validStatuses.includes(form.value.status)) {
                    // Set to the first valid status or reset to an empty string
                    form.value.status = validStatuses.length > 0 ? validStatuses[0] : '';
                }
            } else {
                // Reset the status if transaction_type is not selected
                form.value.status = '';
            }
        },
        { immediate: true } // Ensures this logic runs when the component is mounted
    );

    // Available products for the selected customer
    const availableProducts = ref([]);
    const searchQuery = ref('');
    const errors = ref({});

    // Populate the form with transaction data on load
    onMounted(() => {
        if (props.transaction && props.customers.length) {
            // Fetch available products based on the selected customer
            const selectedCustomer = props.customers.find(customer => customer.id === props.transaction.mst_customer_id);
            if (selectedCustomer) {
                availableProducts.value = selectedCustomer.products || [];
            }

            // Sync stock quantities for products in the transaction
            syncProductStock();
        } else {
            console.error('Transaction or customer data is missing or invalid:', props.transaction, props.customers);
        }
    });

    // Synchronize product stock quantities
    const syncProductStock = () => {
        if (props.transaction.products.length && availableProducts.value.length) {
            form.value.products.forEach(product => {
                const matchedProduct = availableProducts.value.find(p => p.id === product.id);
                if (matchedProduct) {
                    product.stock_quantity = matchedProduct.stock_quantity || 0;
                }
            });
        } else {
            console.warn('No products or available products to sync.');
        }
    };

    // Add or remove products from the selected list
    const toggleProductSelection = (product) => {
        const productIndex = form.value.products.findIndex(p => p.id === product.id);

        if (productIndex !== -1) {
            form.value.products.splice(productIndex, 1);
        } else {
            form.value.products.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                stock_quantity: product.stock_quantity || 0,
                total_price: 0,
            });
        }
    };
    
    const calculateGrandTotal = () => {
        form.value.grand_total = form.value.products.reduce(
            (total, product) => total + product.price * product.quantity,
            form.value.expedition_fee || 0
        );
        // console.log('Grand total updated:', form.value.grand_total); 
    };

    // Update grand_total when products or expedition_fee changes
    watch(
        () => [form.value.products, form.value.expedition_fee],
        () => {
            form.value.grand_total = form.value.products.reduce((total, product) => {
                const productTotal = Number(product.price) * Number(product.quantity);
                return  Number(total) + productTotal;
            }, form.value.expedition_fee || 0);
        },
        { deep: true }
    );

    // Update available products when customer changes
    watch(() => props.transaction.mst_customer_id, (newCustomerId) => {
        const selectedCustomer = props.customers.find(customer => customer.id === newCustomerId);
        availableProducts.value = selectedCustomer?.products || [];
        form.value.products = []; // Reset products
    });

    // Filter products by search query
    watch(searchQuery, (newQuery) => {
        const customerProducts = props.customers.find(customer => customer.id === form.value.mst_customer_id)?.products || [];
        availableProducts.value = customerProducts.filter(product =>
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

    // Clear search input
    const clearSearch = () => {
        searchQuery.value = '';
    };

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
    };

    // Format stock (integer, no decimal places)
    const formatStock = (value) => {
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0
        }).format(Math.floor(value || 0));
    };

    // Update transaction API call
    const updateTransaction = () => {
        try {
            errors.value = {};
            axios.put(`/transaction/api/update/${form.value.id}`, form.value)
                .then(response => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                    });
                    router.visit(route('transaction.list'));
                })
                .catch(err => {
                    console.log('Validation errors:', err.response?.data);
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
                text: 'Failed to update transaction',
            });
        }
    };

    const confirmNavigation = (event) => {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "You will lose any unsaved changes.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'No, stay here'
        }).then((result) => {
            if (result.isConfirmed) {
                router.visit(route('transaction.list'));
            }
        });
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
    <AppLayout title="Edit Transactions">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Transactions
            </h2>
        </template>

        <!-- Container -->
        <div class="container mx-auto p-6">
            <div class="flex flex-col lg:flex-row gap-7 lg:gap-10">
                <!-- Order Details -->
                <div class="bg-white shadow rounded-lg p-6 w-full lg:w-2/3">
                    <!-- Card Header -->
                    <div class="border-b pb-4 mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Edit Transaction</h2>
                    </div>

                    <!-- Error Messages -->
                    <div v-if="Object.keys(errors).length" class="bg-red-100 border-l-4 border-red-300 text-red-700 p-4 mb-5" role="alert">
                        <p class="font-bold mb-3">Error!</p>
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Card Body -->
                    <div class="grid lg:grid-cols-4 sm:grid-cols-1 gap-4">
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Transaction Type <span class="ms-1 text-danger">*</span></label>
                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                <button type="button"
                                    :class="['inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-200 rounded-s-lg focus:z-10 focus:ring-0', purchaseButtonClasses]"
                                    @click="form.transaction_type = 'purchase'">
                                    <i class="ki-solid ki-entrance-left me-2"></i>
                                    Purchase
                                </button>
                                <button type="button"
                                    :class="['inline-flex items-center px-4 py-2 text-sm font-medium border-t border-b border-e rounded-e-lg border-gray-200 focus:z-10 focus:ring-2 focus:ring-blue-700', sellButtonClasses]"
                                    @click="form.transaction_type = 'sell'">
                                    <i class="ki-solid ki-exit-left me-2"></i>
                                    Sell
                                </button>
                            </div>
                        </div>

                        <!-- Transaction Status -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Transaction Status <span class="ms-1 text-danger">*</span>
                            </label>
                            <select v-model="form.status" class="input" :disabled="!form.transaction_type">
                                <option v-for="status in statuses[form.transaction_type]" :key="status.value"
                                    :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Customer Dropdown -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Customer <span class="ms-1 text-danger">*</span></label>
                            <select class="select" v-model="form.mst_customer_id">
                                <option value="" disabled selected>Select Customer</option>
                                <option v-for="customer in props.customers" :key="customer.id" :value="customer.id">
                                    {{ customer.customer_name }}
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
                    <div class="flex flex-col gap-6">
                        <!-- Selected Products -->
                        <div class="overflow-x-auto">
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
                                    <tr
                                        v-for="(product, index) in form.products"
                                        :key="product.id"
                                        class="hover:bg-gray-100 border-b border-dashed border-gray-400"
                                    >
                                        <td class="py-4 px-4">
                                            <div class="flex items-center">
                                                <!-- Product Thumbnail -->
                                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-teal-100 text-teal-700 font-bold border border-teal-400 shrink-0">
                                                    <!-- Display initials -->
                                                    {{ product.name.split(' ').length > 1 
                                                        ? product.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('') 
                                                        : product.name[0].toUpperCase() 
                                                    }}
                                                </div>
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
                                        <td class="py-2 px-4 text-center">
                                            <input
                                                class="input text-center"
                                                type="number"
                                                v-model="product.quantity"
                                                min="1"
                                            />
                                        </td>
                                        <!-- Price -->
                                        <td class="py-2 px-4 text-right">
                                            <span>{{ formatCurrency(product.price) }}</span>
                                        </td>
                                        <!-- Line Total -->
                                        <td class="py-2 px-4 text-right">
                                            <span>{{ formatCurrency(product.price * (product.quantity || 1)) }}</span>
                                        </td>
                                        <!-- Remove Button -->
                                        <td class="py-2 px-4 text-center">
                                            <button
                                                class="btn btn-outline btn-icon btn-danger"
                                                @click="form.products.splice(index, 1)"
                                            >
                                                <i class="ki-filled ki-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.products.length === 0">
                                        <td colspan="5" class="text-center text-gray-500 py-4">No products added yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Grand Total -->
                        <div class="flex flex-col py-4 border-gray-300 border-b text-lg font-bold text-gray-700 text-end justify-end">
                            <!-- Expedition Fee -->
                            <div class="flex py-2 justify-end items-center">
                                Expedition Fee
                                <div class="input-group ms-3">
                                    <span class="btn btn-input" style="border-color: #d8d8d8;">IDR</span>
                                    <input
                                        class="input w-[200px]"
                                        type="number"
                                        v-model="form.expedition_fee"
                                    />
                                </div>
                            </div>
                            <!-- Grand Total -->
                            <div class="py-2">
                                Grand Total:
                                <span :class="{ 'text-green-500': form.grand_total > 0 }">
                                    {{ formatCurrency(form.grand_total) }}
                                </span>
                            </div>
                        </div>
                    </div>


                    <!-- Submit Buttons -->
                    <div class="flex justify-end my-4">
                        <button @click="confirmNavigation" class="btn btn-light me-2">Cancel</button>
                        <button class="btn btn-primary" @click="updateTransaction">Update Transaction</button>
                    </div>

                    <!--begin::Search products-->
                    <div class="flex items-center relative max-w-[300px] mb-4">
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
                                    <span class="font-semibold">{{ formatStock(product.stock_quantity) }}</span>
                                </td>
                            </tr>
                            <tr v-else class="text-center">
                                <td colspan="3" class="py-4">No products added yet.</td>
                            </tr>
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
            </div>
        </div>
    </AppLayout>
</template>
