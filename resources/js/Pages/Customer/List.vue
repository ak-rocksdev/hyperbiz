<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import axios from 'axios';
    import { ref, watch, computed } from 'vue';
    import Swal from 'sweetalert2';

    const form = ref({});
    const errors = ref({});
    const page = ref('');
    const searchQuery = ref('');

    const props = defineProps({
        customers: Object,
        pagination: {
            type: Object,
            required: true,
        },
        customerTypes: Object,
        totalCustomers: Number,
        customerCategoriesCount: Number,
        totalSearchResults: Number,
    });

    const currentPage = ref(props.pagination.current_page || 1);
    const perPageOptions = ref([5, 10, 25, 50]);
    const selectedPerPage = ref(props.pagination.per_page);

    const searchCustomers = () => {
        currentPage.value = 1;
        router.get(
            route('customer.list'),
            { search: searchQuery.value },
            { preserveState: true, preserveScroll: true }
        );
    };

    watch(currentPage, (newPage) => {
        router.get(route('customer.list'), { search: searchQuery.value, page: newPage }, { preserveState: true });
    });

    const visiblePages = computed(() => {
        const totalPages = props.pagination.last_page || 1;
        const maxVisible = 5;
        const pages = [];

        if (totalPages <= maxVisible) {
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            if (currentPage.value <= 3) {
                pages.push(1, 2, 3, 4, '...', totalPages);
            } else if (currentPage.value > totalPages - 3) {
                pages.push(1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages);
            } else {
                pages.push(
                    1,
                    '...',
                    currentPage.value - 1,
                    currentPage.value,
                    currentPage.value + 1,
                    '...',
                    totalPages
                );
            }
        }

        return pages;
    });

    watch([currentPage, selectedPerPage], ([newPage, newPerPage]) => {
        router.get(route('customer.list'), {
            page: newPage,
            per_page: newPerPage,
            ...(searchQuery.value ? { search: searchQuery.value } : {}),
        }, { preserveState: true, replace: true });
    });

    const goToPage = (page) => {
        if (page !== '...') {
            currentPage.value = page;
            router.get(route('customer.list'), { search: searchQuery.value, page }, { preserveState: true, replace: true });
        }
    };

    const selectedCustomer = ref(null);

    const viewCustomerDetail = async (id) => {
        selectedCustomer.value = null;
        try {
            const response = await axios.get(`/customer/api/detail/${id}`);
            selectedCustomer.value = response.data.customer;
        } catch (error) {
            console.error("Error fetching customer details:", error);
        }
    };

    const submitForm = () => {
        errors.value = {};

        try {
            axios.post('/customer/api/store', form.value)
                .then(response => {
                    form.value = {};

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    });

                    KTModal.init();

                    const modalEl = document.querySelector('#modal_create_new_customer');
                    const modal = KTModal.getInstance(modalEl);

                    modal.hide();

                    if (document.querySelector('.modal-backdrop')) {
                        document.querySelector('.modal-backdrop').remove();
                    }

                    router.visit(route('customer.list'), { search: '', page: '' }, { preserveState: true });
                })
                .catch(error => {
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
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
            } else {
                console.error('An unexpected error occurred:', error);
            }
        }
    };

    const clearSearch = () => {
        searchQuery.value = '';
        searchCustomers();
    };
</script>

<template>
    <AppLayout title="Customers">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Customers
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <div class="py-5">
                <div class="grid grid-cols-2 gap-5 lg:gap-7.5 w-full items-stretch">
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ totalCustomers }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Customers
                            </span>
                        </div>
                    </div>
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ customerCategoriesCount }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Categories of Customers
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Customers
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input class="input input-sm ps-8" placeholder="Search Customer" v-model="searchQuery"
                                        @input="searchCustomers" />
                                    <button
                                        v-if="searchQuery"
                                        @click="clearSearch"
                                        class="absolute right-2 flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200"
                                        style="right: 10px; top: 50%; transform: translateY(-50%);"
                                        aria-label="Clear Search">
                                        <i class="ki-filled ki-cross-circle text-gray-500"></i>
                                    </button>
                                </div>
                                <a class="btn btn-sm btn-primary min-w-[100px] justify-center" data-modal-toggle="#modal_create_new_customer">
                                    Add New Customer
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
                                            <th class="min-w-[200px] lg:w-[200px]" data-datatable-column="name">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Name
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[185px]">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Customer Type
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[185px]">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Location
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[185px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Total Sales Value
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[180px] w-[200px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Total Purchase Value
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[85px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Action
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="customer in customers" v-if="customers.length" :key="customer.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="customer.id"/>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-purple-100 text-purple-700 font-bold border border-purple-400 shrink-0">
                                                        {{ customer.name.split(' ').length > 1
                                                            ? customer.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('')
                                                            : customer.name[0].toUpperCase()
                                                        }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span @click="viewCustomerDetail(customer.id)" data-modal-toggle="#modal_view_customer" class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px hover:cursor-pointer">
                                                            {{ customer.name }}
                                                        </span>
                                                        <span class="text-2sm text-gray-700 font-normal">
                                                            {{ customer.email }}
                                                        </span>
                                                        <span class="text-2sm text-gray-700 font-normal">
                                                            {{ customer.phone_number }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-outline badge-success">
                                                    {{ customer.customer_type }}
                                                </span>
                                            </td>
                                            <td>
                                                <span v-if="customer.address">
                                                    {{ customer.address.country_name }} - {{ customer.address.state_name }}, {{ customer.address.city_name }}
                                                </span>
                                                <span v-else>
                                                    N/A
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                {{ customer.sell_value }}
                                            </td>
                                            <td class="text-center">
                                                {{ customer.purchase_value }}
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
                                                                <Link class="menu-link" :href="'/customer/detail/' + customer.id">
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
                                                                <Link class="menu-link" :href="'/customer/edit/' + customer.id">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-pencil">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Edit
                                                                    </span>
                                                                </Link>
                                                            </div>
                                                            <div class="menu-item">
                                                                <a class="menu-link" href="#">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-copy">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Make a copy
                                                                    </span>
                                                                </a>
                                                            </div>
                                                            <div class="menu-item">
                                                                <a class="menu-link" href="#">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-dollar"></i>
                                                                    </span>
                                                                    <span class="menu-title text-start">
                                                                        Transactions
                                                                    </span>
                                                                </a>
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
                                        <tr v-else class="w-full">
                                            <td colspan="100%">
                                                <div class="flex items-center justify-center h-52">
                                                    <div class="flex flex-col items-center">
                                                        <i class="ki-filled ki-user-square text-8xl text-gray-300 mb-4"></i>
                                                        <span class="text-gray-500 text-md">No Data Available!</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                                <div class="flex items-center gap-2">
                                    Show
                                    <select v-model="selectedPerPage" class="select select-sm w-16">
                                        <option v-for="option in perPageOptions" :key="option" :value="option">
                                            {{ option }}
                                        </option>
                                    </select>
                                    per page
                                </div>
                                <span v-if="totalSearchResults > 0" class="text-slate-500 text-sm ms-4">
                                    Total: {{ totalSearchResults }} Result{{ totalSearchResults === 1 ? '' : 's' }}
                                </span>
                                <div class="pagination flex items-center gap-2">
                                    <button class="btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                                        <i class="ki-outline ki-black-left"></i>
                                    </button>

                                    <span v-for="(page, index) in visiblePages" :key="index" class="btn"
                                        :class="{ 'active': page === currentPage }"
                                        @click="goToPage(page)"
                                        v-if="page !== '...'">
                                        {{ page }}
                                    </span>

                                    <span v-else class="btn disabled">...</span>

                                    <button class="btn" :disabled="currentPage >= props.pagination.last_page"
                                        @click="goToPage(currentPage + 1)">
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
    <div class="modal" data-modal="true" id="modal_create_new_customer">
        <div class="modal-content max-w-[600px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">
                        Add Customer
                    </h3>
                    <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross">
                        </i>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="Object.keys(errors).length" class="bg-red-100 border-l-4 border-red-300 text-red-700 p-4 mb-5" role="alert">
                        <p class="font-bold mb-3">Error!</p>
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-wrap lg:flex-nowrap gap-2.5 flex-col p-5">
                        <!-- Customer Name -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Name
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="customer_name"
                                placeholder="Enter Customer Name"
                                type="text"
                                v-model="form.customer_name"
                            />
                        </div>

                        <!-- Customer Type -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label max-w-60 mb-2">
                                    Customer Type
                                    <span class="ms-1 text-danger">
                                        *
                                    </span>
                                </label>
                                <select
                                    class="select"
                                    name="mst_customer_type_id"
                                    v-model="form.mst_customer_type_id"
                                >
                                    <option value="" disabled selected>Select Customer Type</option>
                                    <option v-for="(name, id) in customerTypes" :key="id" :value="id">
                                        {{ name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label max-w-60 mb-4">
                                    Is a Customer
                                    <span class="ms-1 text-danger">
                                        *
                                    </span>
                                </label>
                                <label class="switch switch-lg">
                                    <input class="order-2" v-model="form.is_customer" type="checkbox" />
                                </label>
                            </div>
                        </div>

                        <!-- Customer Phone Number -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Customer Phone Number
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="customer_phone_number"
                                placeholder="Enter Customer Phone Number"
                                type="text"
                                v-model="form.customer_phone_number"
                            />
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Email</label>
                            <input
                                class="input"
                                name="email"
                                placeholder="Enter Customer Email"
                                type="email"
                                v-model="form.email"
                            />
                        </div>

                        <!-- Contact Person -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Contact Person Name
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="contact_person"
                                placeholder="Enter Name"
                                type="text"
                                v-model="form.contact_person"
                            />
                        </div>

                        <!-- Contact Person Phone Number -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Contact Person Phone</label>
                            <input
                                class="input"
                                name="contact_person_phone_number"
                                placeholder="Enter Phone Number"
                                type="text"
                                v-model="form.contact_person_phone_number"
                            />
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-end">
                    <div class="flex gap-4">
                        <button class="btn btn-light" data-modal-dismiss="true">
                            Cancel
                        </button>
                        <button class="btn btn-primary">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal pb-10" data-modal="true" id="modal_view_customer">
        <div class="modal-content max-w-[600px] top-[10%]">
            <div class="modal-header">
                <h3 class="modal-title">
                    View Customer
                </h3>
                <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                    <i class="ki-outline ki-cross">
                    </i>
                </button>
            </div>
            <div class="modal-body">
                <div v-if="selectedCustomer">
                    <div class="p-5">
                        <div class="d-flex gap-5">
                            <div class="p-5">
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Customer Name</label>
                                    <div class="flex items-center gap-2.5">
                                        <span class="!text-gray-500">{{ selectedCustomer.name }}</span>
                                        <span class="badge badge-outline badge-success">
                                            {{ selectedCustomer.customer_type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Email</label>
                                    <p class="!text-gray-500">{{ selectedCustomer.email }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Company Phone</label>
                                    <p class="!text-gray-500">{{ selectedCustomer.phone_number }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Contact Person</label>
                                    <p v-if="selectedCustomer.contact_person"  class="!text-gray-500">{{ selectedCustomer.contact_person }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Contact Person Phone Number</label>
                                    <p v-if="selectedCustomer.contact_person_phone_number" class="!text-gray-500">{{ selectedCustomer.contact_person_phone_number }}</p>
                                    <p v-else class="!text-gray-500">N/A</p>
                                </div>
                                <div>
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Address</label>
                                    <div v-if="selectedCustomer.address">
                                        <p class="!text-gray-500">{{ selectedCustomer.address?.address }}</p>
                                        <p class="!text-gray-500">{{ selectedCustomer.address?.city_name }}, {{ selectedCustomer.address?.state_name }}</p>
                                        <p class="!text-gray-500">{{ selectedCustomer.address?.country_name }}</p>
                                    </div>
                                    <div v-else>
                                        <p class="!text-gray-500">N/A</p>
                                    </div>
                                </div>
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
</template>
