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

    const fetchProductsByClient = async (clientId) => {
        try {
            const response = await axios.get(`/api/products/by-client/${clientId}`);
            availableProducts.value = response.data.products;
        } catch (error) {
            console.error("Error fetching products:", error);
        }
    };

    watch(() => form.value.mst_client_id, (newClientId) => {
        if (newClientId) {
            fetchProductsByClient(newClientId);
        } else {
            availableProducts.value = [];
        }
    });

    const updateProductPrice = (index) => {
        const selectedProduct = availableProducts.value.find(product => product.id === form.value.products[index].id);
        if (selectedProduct) {
            form.value.products[index].price = selectedProduct.price;
        }
    };

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
                    console.error('Error:', error);
                });
        } catch (error) {
            console.error('Error:', error);
        }
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
                                {{ totalTransactionValue }}
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
                                <a class="btn btn-sm btn-primary min-w-[150px] justify-center" data-modal-toggle="#modal_create_new_transaction">
                                    Add New Transaction
                                </a>
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
                                                <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox"/>
                                            </th>
                                            <th class="min-w-[200px] lg:w-[200px]" data-datatable-column="transaction_id">
                                                Transaction ID
                                            </th>
                                            <th class="w-[185px]" data-datatable-column="transaction_date">
                                                Transaction Date
                                            </th>
                                            <th class="w-[185px]" data-datatable-column="client">
                                                Client
                                            </th>
                                            <th class="min-w-[180px] w-[200px] text-center">
                                                Total Value
                                            </th>
                                            <th class="w-[85px] text-center">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="transaction in transactions" :key="transaction.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="transaction.id"/>
                                            </td>
                                            <td>
                                                {{ transaction.transaction_id }}
                                            </td>
                                            <td>
                                                {{ transaction.transaction_date }}
                                            </td>
                                            <td>
                                                {{ transaction.client_name }}
                                            </td>
                                            <td class="text-center">
                                                {{ transaction.total_value }}
                                            </td>
                                            <td class="text-center">
                                                <button @click="viewTransactionDetail(transaction.id)" data-modal-toggle="#modal_view_transaction" class="btn btn-sm btn-icon btn-light btn-clear">
                                                    <i class="ki-filled ki-search-list"></i>
                                                </button>
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
        <div class="modal-content max-w-[600px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">
                        Add Transaction
                    </h3>
                    <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="flex flex-wrap lg:flex-nowrap gap-2.5 flex-col p-5">
                        <!-- Client -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Client
                                <span class="ms-1 text-danger">*</span>
                            </label>
                            <select class="select" v-model="form.mst_client_id">
                                <option value="" disabled selected>Select Client</option>
                                <option v-for="(name, id) in clients" :key="id" :value="id">
                                    {{ name }}
                                </option>
                            </select>
                        </div>

                        <!-- Transaction Date -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Transaction Date</label>
                            <input class="input" name="transaction_date" type="date" v-model="form.transaction_date"/>
                        </div>

                        <!-- Products -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Products</label>
                            <div v-for="(product, index) in form.products" :key="index" class="flex gap-2 mb-2">
                                <select class="select" v-model="product.id" @change="updateProductPrice(index)">
                                    <option value="" disabled>Select Product</option>
                                    <option v-for="(productOption) in availableProducts" :key="productOption.id" :value="productOption.id">
                                        {{ productOption.name }}
                                    </option>
                                </select>
                                <input class="input" type="text" readonly v-model="product.price" placeholder="Price"/>
                                <button type="button" class="btn btn-sm btn-light" @click="form.products.splice(index, 1)">
                                    Remove
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" @click="form.products.push({ id: '', price: '' })">
                                Add Product
                            </button>
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
</template>
