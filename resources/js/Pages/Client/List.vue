<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link } from '@inertiajs/vue3';
    import Detail from './Detail.vue'; // Import the Detail component
    import axios from 'axios';
    import { ref } from 'vue';

    const form = ref({});

    const props = defineProps({
        clients: Object,
        clientTypes: Object,
        totalClients: Number,
        clientCategoriesCount: Number,
    });

    const selectedClient = ref(null);

    const viewClientDetail = async (id) => {
        selectedClient.value = null;
        try {
            const response = await axios.get(`/client/api/detail/${id}`);
            selectedClient.value = response.data.client;
        } catch (error) {
            console.error("Error fetching client details:", error);
        }
    };

    const submitForm = () => {
        console.log('Form submitted:', form.value);
        
        try {
            axios.post('/client/api/store', form.value)
                .then(response => {
                    // Reset the form
                    form.value = {};

                    // Close the modal
                    document.querySelector('#modal_create_new_client').dispatchEvent(new Event('modal-dismiss'));

                    // Refresh the clients list
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
    <AppLayout title="Clients">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Clients
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <div class="py-5">
                <div class="grid grid-cols-2 gap-5 lg:gap-7.5 w-full items-stretch">
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ totalClients }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Clients
                            </span>
                        </div>
                    </div>
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ clientCategoriesCount }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Categories of Clients
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Clients
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input data-datatable-search="#data_container" class="input input-sm ps-8" placeholder="Search Client" value="" />
                                </div>
                                <a class="btn btn-sm btn-primary min-w-[100px] justify-center" data-modal-toggle="#modal_create_new_client">
                                    Add New Client
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
                                                        Client Type
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[185px]">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Phone
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <!-- location -->
                                            <th class="w-[185px]">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Location
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[180px] w-[200px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Registered At
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
                                        <tr v-for="client in clients" :key="client.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="client.id"/>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="size-12 rounded-full overflow-hidden flex-shrink-0">
                                                        <img :src="'https://picsum.photos/500/300.jpg'" alt="Client Image" class="object-cover w-full h-full" />
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span @click="viewClientDetail(client.id)" data-modal-toggle="#modal_view_client" class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px hover:cursor-pointer">
                                                            {{ client.name }}
                                                        </span>
                                                        <span class="text-2sm text-gray-700 font-normal">
                                                            {{ client.email }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-outline badge-success">
                                                    {{ client.client_type }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ client.phone_number }}
                                            </td>
                                            <td>
                                                <span v-if="client.address">
                                                    {{ client.address.country_name }} - {{ client.address.state_name }}, {{ client.address.city_name }}
                                                </span>
                                                <span v-else>
                                                    N/A
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                {{ client.register_at }}
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
                                                                <Link class="menu-link" :href="'/client/detail/' + client.id">
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
                                                                <Link class="menu-link" :href="'/client/edit/' + client.id">
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
    <div class="modal" data-modal="true" id="modal_create_new_client">
        <div class="modal-content max-w-[600px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">
                        Add Client
                    </h3>
                    <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross">
                        </i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="flex flex-wrap lg:flex-nowrap gap-2.5 flex-col p-5">
                        <!-- Client Name -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Name
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="client_name"
                                placeholder="Enter Client Name"
                                type="text"
                                v-model="form.client_name"
                            />
                        </div>

                        <!-- Client Type -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Client Type
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <select
                                class="select"
                                name="mst_client_type_id"
                                v-model="form.mst_client_type_id"
                            >
                                <option value="" disabled selected>Select Client Type</option>
                                <option v-for="(name, id) in clientTypes" :key="id" :value="id">
                                    {{ name }}
                                </option>
                            </select>
                        </div>

                        <!-- Client Phone Number -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Client Phone Number
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="client_phone_number"
                                placeholder="Enter Client Phone Number"
                                type="text"
                                v-model="form.client_phone_number"
                            />
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Email</label>
                            <input
                                class="input"
                                name="email"
                                placeholder="Enter Client Email"
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

    <div class="modal pb-10" data-modal="true" id="modal_view_client">
        <div class="modal-content max-w-[600px] top-[10%]">
            <div class="modal-header">
                <h3 class="modal-title">
                    View Client
                </h3>
                <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                    <i class="ki-outline ki-cross">
                    </i>
                </button>
            </div>
            <div class="modal-body">
                <div v-if="selectedClient">
                    <div class="p-5">
                        <div class="d-flex gap-5">
                            <div class="p-5">
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Client Name</label>
                                    <!-- use tailwind css class name for flex -->
                                    <div class="flex items-center gap-2.5">
                                        <span class="!text-gray-500">{{ selectedClient.name }}</span>
                                        <span class="badge badge-outline badge-success">
                                            {{ selectedClient.client_type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Email</label>
                                    <p class="!text-gray-500">{{ selectedClient.email }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Company Phone</label>
                                    <p class="!text-gray-500">{{ selectedClient.phone_number }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Contact Person</label>
                                    <p v-if="selectedClient.contact_person"  class="!text-gray-500">{{ selectedClient.contact_person }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Contact Person Phone Number</label>
                                    <p v-if="selectedClient.contact_person_phone_number" class="!text-gray-500">{{ selectedClient.contact_person_phone_number }}</p>
                                    <p v-else class="!text-gray-500">N/A</p>
                                </div>
                                <div>
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Address</label>
                                    <div v-if="selectedClient.address">
                                        <p class="!text-gray-500">{{ selectedClient.address?.address }}</p>
                                        <p class="!text-gray-500">{{ selectedClient.address?.city_name }}, {{ selectedClient.address?.state_name }}</p>
                                        <p class="!text-gray-500">{{ selectedClient.address?.country_name }}</p>
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
                    <!-- <div class="flex items-center justify-center">
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <div class="flex items-center gap-2 px-4 py-2 font-medium leading-none text-2sm border border-gray-300 shadow-default rounded-md text-gray-700 bg-light">
                                <svg class="animate-spin -ml-1 h-5 w-5 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </div>
                        </div>
                    </div> -->
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