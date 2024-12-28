<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link } from '@inertiajs/vue3';
    import axios from 'axios';
    import { ref, watch } from 'vue';

    const form = ref({
        mst_client_id: '',
        products: [],
        transaction_date: '',
    });

    const props = defineProps({
        transactions: Object,
        clients: Object,
        totalTransactions: Number,
        totalTransactionValue: Number,
    });

    const selectedTransaction = ref(null);
    const availableProducts = ref([]);

    const viewTransactionDetail = async (id) => {
        selectedTransaction.value = null;
        try {
            const response = await axios.get(`/transaction/api/detail/${id}`);
            selectedTransaction.value = response.data.transaction;
        } catch (error) {
            console.error("Error fetching transaction details:", error);
        }
    };

    watch(() => form.value.mst_client_id, (newClientId) => {
        form.value.products = [];
        if (newClientId) {
            const selectedClient = props.clients.find(client => client.id === newClientId);
            availableProducts.value = selectedClient?.products || [];
        } else {
            availableProducts.value = [];
        }
    });

    const updateProductPrice = (index) => {
        const selectedProduct = availableProducts.value.find(product => product.id === form.value.products[index].id);
        if (selectedProduct) {
            form.value.products[index].price = selectedProduct.price;
            form.value.products[index].stock_quantity = selectedProduct.stock_quantity;
        }
    };

    watch(
        () => form.value.products.map(product => ({ id: product.id, quantity: product.quantity })), // Watch both id and quantity
        (newProducts, oldProducts) => {
            newProducts.forEach((newProduct, index) => {
                const oldProduct = oldProducts[index] || {};
                const oldQuantity = oldProduct.quantity || 0;
                const newQuantity = newProduct.quantity || 0;
                const product = form.value.products[index];

                // Skip processing if no product is selected
                if (!newProduct.id) {
                    return;
                }

                if (newQuantity > oldQuantity) {
                    // If quantity increases, reduce stock
                    const difference = newQuantity - oldQuantity;
                    if (product.stock_quantity >= difference) {
                        product.stock_quantity -= difference;
                    } else {
                        // Revert quantity if stock is insufficient
                        form.value.products[index].quantity = oldQuantity;
                        alert(`Insufficient stock for the selected product`);
                    }
                } else if (newQuantity < oldQuantity) {
                    // If quantity decreases, return the difference to stock
                    const difference = oldQuantity - newQuantity;
                    product.stock_quantity += difference;
                }
            });
        },
        { deep: true } // Watch nested properties
    );

    const submitForm = () => {
        console.log('Form submitted:', form.value);
        try {
            axios.post('/transaction/api/store', form.value)
                .then(response => {
                    // Reset the form
                    form.value = {
                        mst_client_id: '',
                        products: [],
                        transaction_date: '',
                    };

                    // Close the modal
                    document.querySelector('#modal_create_new_transaction').dispatchEvent(new Event('modal-dismiss'));

                    // Refresh the transaction list
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error.data.message);
                });
        } catch (error) {
            console.log('Error:', error);
        }
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
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
        <div class="container-fixed">
            <div class="py-5">
                <div class="grid grid-cols-2 gap-5 lg:gap-7.5 w-full items-stretch">
                    <div class="card flex-col justify-between gap-6 h-full bg-cover">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ totalTransactions }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Total Transactions
                            </span>
                        </div>
                    </div>
                    <div class="card flex-col justify-between gap-6 h-full bg-cover">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ formatCurrency(totalTransactionValue) }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Total Transaction Value
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Transactions
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input data-datatable-search="#data_container" class="input input-sm ps-8" placeholder="Search Transaction" value="" />
                                </div>
                                <!-- data-modal-toggle="#modal_create_new_transaction" -->
                                <Link class="btn btn-sm btn-primary min-w-[150px] justify-center" :href="route('transaction.create')">
                                    Add New Transaction
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="data_container">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[200px] lg:w-[200px]" data-datatable-column="transaction_code">
                                                Client
                                            </th>
                                            <th class="w-[185px] text-center" data-datatable-column="transaction_date">
                                                Transaction Date
                                            </th>
                                            <th class="min-w-[180px] w-[200px] text-end">
                                                Total Value
                                            </th>
                                            <th class="w-[85px] text-center">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-slate-100">
                                            <td>
                                                <div class="flex flex-col gap-2 cursor-pointer group" @click="viewTransactionDetail(transaction.id)" data-modal-toggle="#modal_view_transaction">
                                                    <!-- Client Name -->
                                                    <span class="font-bold text-blue-500 group-hover:text-blue-700 flex items-center">
                                                        {{ transaction.client_name }}
                                                        <!-- Magnifier Icon -->
                                                        <i class="ki-filled ki-magnifier ms-2 hidden group-hover:inline text-gray-600"></i>
                                                    </span>
                                                    <!-- Transaction Code -->
                                                    <span>Transaction Code: {{ transaction.transaction_code }}</span>
                                                </div>

                                            </td>
                                            <td class="text-center">
                                                {{ transaction.transaction_date }}
                                            </td>
                                            <td class="text-end">
                                                {{ formatCurrency(transaction.grand_total) }}
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
                                                                <Link class="menu-link" :href="'/transaction/detail/' + transaction.id">
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
                                                                <Link class="menu-link" :href="'/transaction/edit/' + transaction.id">
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
        <!-- End of Container -->

    </AppLayout>

    <!-- Create Transaction Modal -->
    <div class="modal" data-modal="true" id="modal_create_new_transaction">
        <div class="modal-content max-w-[800px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">Add Transaction</h3>
                    <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="flex flex-wrap lg:flex-nowrap gap-2.5 flex-col p-5">
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

                        <!-- Products -->
                        <div class="mb-4">
                            <div class="flex justify-end items-center mb-5">
                                <!-- <h3 class="text-lg font-bold">Products</h3> -->
                                <button type="button" class="btn btn-primary" @click="form.products.push({ id: '', price: '', quantity: 1 })">
                                    <i class="ki-outline ki-plus-squared"></i>
                                    Add Product
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="table-auto w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-700 border-b">
                                            <th class="py-2 px-4">Product</th>
                                            <th class="py-2 px-4 text-center">Stock</th>
                                            <th class="py-2 px-4 text-center">Quantity</th>
                                            <th class="py-2 px-4 text-right">Price</th>
                                            <th class="py-2 px-4 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(product, index) in form.products" :key="index" class="hover:bg-gray-100">
                                            <!-- Product dropdown -->
                                            <td class="py-2 px-4">
                                                <select class="select w-full" v-model="product.id" @change="updateProductPrice(index)">
                                                    <option value="" disabled>Select Product</option>
                                                    <option v-for="(productOption) in availableProducts" :key="productOption.id" :value="productOption.id">
                                                        {{ productOption.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <!-- Current Stock -->
                                            <td class="py-2 px-4 w-[125px] text-center">
                                                {{ product.stock_quantity }}
                                            </td>
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
                                                <input class="input w-full text-right" type="text" readonly v-model="product.price" placeholder="Price" />
                                            </td>
                                            <!-- Remove button -->
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
                                            <td colspan="4" class="text-center text-gray-500 py-4">
                                                No products added yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-end">
                    <div class="flex gap-4">
                        <button class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                        <button class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal pb-10" data-modal="true" id="modal_view_transaction">
        <div class="modal-content max-w-[800px] top-[10%]">
            <div class="modal-header">
                <h3 class="modal-title">View Product</h3>
                <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                    <i class="ki-outline ki-cross"></i>
                </button>
            </div>
            <div class="modal-body">
                <div v-if="selectedTransaction">
                    <div class="p-5">
                        <div class="flex flex-row gap-3 mb-4">
                            <label class="form-label !font-extrabold text-md !text-blue-500">Status</label>
                            <p class="!text-gray-500">
                                <span v-if="selectedTransaction.status" 
                                    :class="{'badge-warning': selectedTransaction.status == 'pending', 'badge-success': selectedTransaction.status != 'pending' }"
                                    class="badge badge-outline">
                                    {{ selectedTransaction.status }}
                                </span>
                            </p>
                        </div>
                        <div class="p-5 mb-10 bg-white border-gray-300 border rounded-xl shadow-lg space-y-2 sm:py-4 sm:flex sm:items-start sm:space-y-0 sm:space-x-6">
                            <div class="mb-5">
                                <img :src="'https://picsum.photos/500'" alt="Product Image" class="sm:w-full lg:max-w-[200px] h-auto rounded shadow-lg" />
                                <!-- <p v-else class="!text-gray-500">No image available</p> -->
                            </div>
                            <div class="flex-grow w-full">
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-gray-600">Client</label>
                                    <p class="!text-gray-500">{{ selectedTransaction.client_name }}</p>
                                </div>
                                <div class="w-full">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-gray-600">Transaction Date</label>
                                    <p class="!text-gray-500 text-sm">{{ selectedTransaction.transaction_date }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="table-auto w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-700 border-b border-gray-300">
                                        <th class="py-2 ps-4 min-w-[125px]">Product</th>
                                        <th class="py-2 px-4 text-center">Quantity</th>
                                        <th class="py-2 px-4 text-right">Price</th>
                                        <th class="py-2 pe-4 text-right">Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(product, index) in selectedTransaction.details" :key="index" class="hover:bg-gray-100 border-b border-gray-300 border-dashed">
                                        <!-- Product dropdown -->
                                        <td class="py-4 ps-4">
                                            {{ product.name }} 
                                        </td>
                                        <td class="py-4 px-4 w-[125px] text-center">
                                            {{ product.quantity }}
                                        </td>
                                        <!-- Price -->
                                        <td class="py-4 px-4 text-right w-[150px]">
                                            {{ formatCurrency(product.price) }}
                                        </td>
                                        <td class="py-4 pe-4 w-[150px] text-end">
                                            {{ product.total_price }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
</template>
