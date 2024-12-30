<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import axios from 'axios';
    import { ref, watch, computed } from 'vue';
    import Swal from 'sweetalert2';

    const form = ref({
        mst_client_id: '',
        products: [],
        transaction_date: '',
    });

    const props = defineProps({
        transactions: Object,
        pagination: {
            type: Object,
            required: true,
        },
        clients: Object,
        totalTransactions: Number,
        totalPurchaseValue: Number,
        totalSellValue: Number,
        statuses: {
            type: Object,
            required: true,
            default: () => ({
                purchase: [],
                sell: [],
            }),
        },
        resultCount: Number,
        allTransactionsCount: Number,
        });

    const urlQuery = new URLSearchParams(window.location.search);
    const searchQuery = ref(urlQuery.get('search') || '');
    const currentPage = ref(props.pagination.current_page || 1);
    const perPageOptions = ref([1, 5, 10, 25, 50]);
    const selectedPerPage = ref(props.pagination.per_page || 5);

    watch([currentPage, selectedPerPage], ([newPage, newPerPage]) => {
        router.get(route('transaction.list'), {
            page: newPage,
            per_page: newPerPage,
        }, { preserveState: true, replace: true });
    });

    const fetchTransactions = () => {
        router.get(route('transaction.list'), {
            search: searchQuery.value,
            per_page: selectedPerPage.value,
            page: currentPage.value,
        }, { preserveScroll: true, preserveState: true });
    };

    // Perform Search
    const performSearch = () => {
        currentPage.value = 1; // Reset to the first page when performing a search
        fetchTransactions();
    };

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

    const visiblePages = computed(() => {
        const total = props.pagination.last_page;
        const current = currentPage.value;

        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

        if (current <= 4) return [1, 2, 3, 4, '...', total];
        if (current >= total - 3) return [1, '...', total - 3, total - 2, total - 1, total];

        return [1, '...', current - 1, current, current + 1, '...', total];
    });

    const goToPage = (page) => {
        if (page !== '...') currentPage.value = page;
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

    const removeTransaction = (transactionId) => {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action will permanently delete the transaction and adjust stock levels.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/transaction/api/delete/${transactionId}`)
                    .then((response) => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false,
                        });

                        // Refresh the list of transactions
                        router.reload({
                            only: [
                                'transactions', 
                                'totalTransactions', 
                                'totalPurchaseValue', 
                                'totalSellValue',
                                'products',
                                'clients',
                            ],
                            preserveScroll: true,
                        });
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
    
    const statusColors = {
        pending             : 'bg-yellow-100 text-yellow-600 border-yellow-300',
        processing          : 'bg-blue-100 text-blue-600 border-blue-300',
        completed           : 'bg-green-100 text-green-600 border-green-300',
        cancelled           : 'bg-red-100 text-red-600 border-red-300',
        approved            : 'bg-purple-100 text-purple-600 border-purple-300',
        received            : 'bg-teal-100 text-teal-600 border-teal-300',
        partially_received  : 'bg-orange-100 text-orange-600 border-orange-300',
        rejected            : 'bg-gray-100 text-gray-600 border-gray-300',
        refunded            : 'bg-pink-100 text-pink-600 border-pink-300',
        backordered         : 'bg-indigo-100 text-indigo-600 border-indigo-300',
        paid                : 'bg-green-100 text-green-600 border-green-300',
        shipped             : 'bg-blue-100 text-blue-600 border-blue-300',
        delivered           : 'bg-green-100 text-green-600 border-green-300',
        partially_delivered : 'bg-orange-100 text-orange-600 border-orange-300',
        returned            : 'bg-red-100 text-red-600 border-red-300',
    };

    const exportPdf = (id) => {
        router.visit(route('transactions.export-pdf', { id }), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const showPdf = ref(false);
    const pdfUrl = ref(null); // Original dynamic URL
    const cachedPdfUrl = ref(null); // Cached URL to prevent reload

    // Watch for changes to pdfUrl
    watch(pdfUrl, async (newUrl) => {
        if (!newUrl) {
            cachedPdfUrl.value = null; // Reset cached URL if source is cleared
            return;
        }
        cachedPdfUrl.value = null; // Clear cached URL to prevent reuse
        try {
            // Preload the PDF URL to prevent iframe reloads
            await fetch(newUrl, { method: 'HEAD' });
            cachedPdfUrl.value = newUrl; // Update cached URL
        } catch (error) {
            console.error('Error loading PDF:', error);
            cachedPdfUrl.value = null; // Handle errors gracefully
        }
    });

    // Function to set the PDF URL and show the modal
    const previewPdf = (id) => {
        showPdf.value = true; // Open the modal
        // open new blank page to go to the link
        // window.open(`/transaction/${id}/export-pdf`, '_blank');
        pdfUrl.value = '/transaction/' + id + '/export-pdf'; // Set the PDF URL
    };

    // Function to close the modal and reset the URL
    const closeModal = () => {
        showPdf.value = false;
        pdfUrl.value = '';
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
                <div class="grid grid-cols-3 lg:grid-cols-3 gap-6 w-full items-stretch">
                    <!-- Total Transactions -->
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

                    <!-- Total Purchases -->
                    <div class="card flex-col justify-between gap-6 h-full bg-cover">
                        <div class="flex flex-col gap-1 py-5 px-5 relative">
                            <!-- create overlay icon for background this element using absolute position for icon <i class="ki-solid ki-entrance-left me-2 text-green-200"></i> position on the left -->
                            <span class="text-7xl absolute start-2 -translate-y-1/2 font-semibold text-gray-900 top-1/2 z-0">
                                <i class="ki-solid ki-entrance-left me-2 text-orange-100"></i>
                            </span>
                            <span class="text-3xl font-semibold text-gray-900 z-10">
                                {{ formatCurrency(totalPurchaseValue) }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700 z-10">
                                Total Purchases Value
                            </span>
                        </div>
                    </div>

                    <!-- Total Sales -->
                    <div class="card flex-col justify-between gap-6 h-full bg-cover">
                        <div class="flex flex-col gap-1 py-5 px-5 relative">
                            <span class="text-7xl absolute start-2 -translate-y-1/2 font-semibold text-gray-900 top-1/2 z-0">
                                <i class="ki-solid ki-exit-left me-2 text-green-100"></i>
                            </span>
                            <span class="text-3xl font-semibold text-gray-900 z-10">
                                {{ formatCurrency(totalSellValue) }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700 z-10">
                                Total Sales Value
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
                                    <input v-model="searchQuery" class="input input-sm pl-8"
                                        placeholder="Search Transactions" @input="performSearch" />
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
                                            <th class="min-w-[200px] lg:w-[300px]" data-datatable-column="transaction_code">
                                                Client
                                            </th>
                                            <th class="w-[150px] text-center" data-datatable-column="transaction_type">
                                                Transaction Type
                                            </th>
                                            <th class="w-[150px] text-center" data-datatable-column="transaction_date">
                                                Transaction Date
                                            </th>
                                            <th class="py-2 px-4 w-[100px] text-center">
                                                Status
                                            </th>
                                            <th class="min-w-[180px] w-[200px] text-end">
                                                Transaction Value
                                            </th>
                                            <th class="w-[85px] text-center">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-slate-100" v-if="transactions.length > 0">
                                            <td>
                                                <div class="flex flex-col gap-2 group">
                                                    <!-- Client Name -->
                                                    <span class="font-bold text-blue-500 group-hover:text-blue-700 flex cursor-pointer items-center" 
                                                        @click="viewTransactionDetail(transaction.id)" data-modal-toggle="#modal_view_transaction">
                                                        {{ transaction.client_name }}
                                                        <!-- Magnifier Icon -->
                                                        <i class="ki-filled ki-magnifier ms-2 hidden group-hover:inline text-gray-600"></i>
                                                    </span>
                                                    <!-- Transaction Code -->
                                                    <span>Transaction Code: {{ transaction.transaction_code }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span :class="{'badge-warning': transaction.transaction_type == 'purchase', 'badge-success': transaction.transaction_type != 'purchase' }" class="badge badge-outline">
                                                    {{ transaction.transaction_type }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                {{ transaction.transaction_date }}
                                            </td>
                                            <!-- Status -->
                                            <td class="py-4 px-4 text-center">
                                                <span v-if="statuses[transaction.transaction_type]"
                                                    :class="`text-xs rounded-lg px-2 py-1 border ${statusColors[transaction.status]}`">
                                                    {{ statuses[transaction.transaction_type].find(s => s.value === transaction.status)?.label || 'Unknown' }}
                                                </span>
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
                                                                <button class="menu-link" @click="previewPdf(transaction.id)" data-modal-toggle="#modal_pdf_preview">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-printer">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Print Preview
                                                                    </span>
                                                                </button>
                                                                <Link class="menu-link" :href="'/transaction/' + transaction.id + '/preview'">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-document">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        View Detail
                                                                    </span>
                                                                </Link>
                                                                <!-- <Link class="menu-link" :href="'/transaction/detail/' + transaction.id">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-search-list">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        View Detail
                                                                    </span>
                                                                </Link> -->
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
                                                                <button class="menu-link" @click.prevent="removeTransaction(transaction.id)">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-trash">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title !text-red-500 hover:!text-red-600">
                                                                        Remove
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-else class="w-full">
                                            <td colspan="100%">
                                                <!-- Centered content -->
                                                <div class="flex items-center justify-center h-52">
                                                    <div class="flex flex-col items-center">
                                                        <i class="ki-filled ki-calculator text-8xl text-gray-300 mb-4"></i>
                                                        <span class="text-gray-500 text-md">No Data Available!</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="card-footer justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                                <div class="flex items-center gap-2">
                                    Show
                                    <select v-model="selectedPerPage" class="select select-sm w-16">
                                        <option v-for="option in perPageOptions" :key="option" :value="option">
                                            {{ option }}</option>
                                    </select>
                                    per page
                                </div>
                                <span v-if="resultCount > 0">
                                    Showing {{ resultCount }} of {{ allTransactionsCount }} results
                                </span>
                                <div class="pagination flex items-center">
                                    <button class="btn" :disabled="currentPage <= 1" @click="currentPage--">
                                        <i class="ki-outline ki-black-left"></i>
                                    </button>
                                    <span v-for="page in visiblePages" :key="page" class="btn"
                                        :class="{ active: page === currentPage }" @click="goToPage(page)">
                                        {{ page }}
                                    </span>
                                    <button class="btn" :disabled="currentPage >= props.pagination.last_page"
                                        @click="currentPage++">
                                        <i class="ki-outline ki-black-right"></i>
                                    </button>
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
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex flex-row gap-3 w-full">
                                <label class="form-label !font-extrabold text-md !text-blue-500">Status</label>
                                <p class="!text-gray-500">
                                    <span v-if="selectedTransaction.status" 
                                        :class="{'badge-warning': selectedTransaction.status == 'pending', 'badge-success': selectedTransaction.status != 'pending' }"
                                        class="badge badge-outline">
                                        {{ selectedTransaction.status }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex flex-row gap-3 w-full">
                                <label class="form-label !font-extrabold text-md !text-blue-500">Grand Total</label>
                                <p class="!text-cyan-900 font-bold">{{ selectedTransaction.grand_total }}</p>
                            </div>
                        </div>
                        <div class="p-5 mb-10 bg-white border-gray-300 border rounded-xl shadow-lg space-y-2 sm:py-4 sm:flex sm:items-start sm:space-y-0 sm:space-x-6">
                            <div class="mb-5">
                                <img :src="'https://picsum.photos/500'" alt="Product Image" class="sm:w-full lg:max-w-[200px] h-auto rounded shadow-lg" />
                                <!-- <p v-else class="!text-gray-500">No image available</p> -->
                            </div>
                            <div class="grid grid-cols-2 gap-4 w-full">
                                <div class="mb-3">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Client</label>
                                    <p class="!text-gray-500">{{ selectedTransaction.client_name }}</p>
                                </div>
                                <div class="w-full">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Transaction Date</label>
                                    <p class="!text-gray-500 text-sm">{{ selectedTransaction.transaction_date }}</p>
                                </div>
                                <!-- transaction type -->
                                <div class="mb-3 w-full">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Transaction Type</label>
                                    <span class="capitalize">
                                        <span v-if="selectedTransaction.transaction_type == 'sell'">
                                            <i class="ki-solid ki-entrance-left me-1 text-green-500"></i> {{ selectedTransaction.transaction_type }}
                                        </span>
                                        <span v-else>
                                            <i class="ki-solid ki-exit-left me-1 text-orange-500"></i> {{ selectedTransaction.transaction_type }}
                                        </span>
                                    </span>
                                </div>
                                <!-- show transaction_code -->
                                <div class="w-full">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Transaction Code</label>
                                    <p class="!text-gray-500">{{ selectedTransaction.transaction_code }}</p>
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

    <div v-if="showPdf" class="modal" data-modal="true" id="modal_pdf_preview">
        <div class="modal-content max-w-[800px]">
            <div class="modal-header flex justify-between items-center">
                <h3 class="modal-title">Transaction Preview</h3>
                <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                    <i class="ki-outline ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-4">
                <iframe
                    v-if="cachedPdfUrl"
                    :src="cachedPdfUrl"
                    frameborder="0"
                    class="w-full h-[600px]"
                    title="PDF Preview"
                ></iframe>
                <div v-else>
                    <div class="flex items-center justify-center h-52">
                        <div class="flex flex-row gap-1 items-center bg-slate-100 px-5 py-2 border border-gray-300 border-1 rounded-lg">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-700" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" stroke="currentColor" stroke-width="4" cx="12" cy="12"
                                    r="10"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span class="text-gray-600 text-md">Loading</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-modal-dismiss="true" @click="closeModal">Close</button>
            </div>
        </div>
    </div>
</template>
