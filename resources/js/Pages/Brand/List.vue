<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';
    import axios from 'axios';

    // Data and Props
    const props = defineProps({
        brands: Object,
        totalBrands: Number,
    });

    // State
    const form = ref({}); // Used for creating/updating brands
    const selectedBrand = ref(null); // Used for viewing/editing a specific brand

    // Methods
    const viewBrandDetail = async (id) => {
        selectedBrand.value = null;
        try {
            const response = await axios.get(`/brand/api/detail/${id}`);
            selectedBrand.value = response.data.brand;
        } catch (error) {
            console.error('Error fetching brand details:', error);
        }
    };

    const submitForm = () => {
        console.log('Form submitted:', form.value);

        try {
            axios.post('/brand/api/store', form.value)
                .then((response) => {
                    form.value = {}; // Reset the form
                    document.querySelector('#modal_create_new_brand').dispatchEvent(new Event('modal-dismiss')); // Close modal
                    window.location.reload(); // Refresh the brand list
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        } catch (error) {
            console.error('Error:', error);
        }
    };
</script>

<template>
    <AppLayout title="Brands">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Brands
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <div class="py-5">
                <div class="grid grid-cols-2 gap-5 lg:gap-7.5 w-full items-stretch">
                    <div class="card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
                        <div class="flex flex-col gap-1 py-5 px-5">
                            <span class="text-3xl font-semibold text-gray-900">
                                {{ totalBrands }}
                            </span>
                            <span class="text-2sm font-normal text-gray-700">
                                Brands
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Brands
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input data-datatable-search="#data_container" class="input input-sm ps-8" placeholder="Search Brand" value="" />
                                </div>
                                <a class="btn btn-sm btn-primary min-w-[100px] justify-center" data-modal-toggle="#modal_create_new_brand">
                                    Add New Brand
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
                                            <th class="min-w-[180px] w-[200px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Created At
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
                                        <tr v-if="!brands || brands.length === 0">
                                            <td colspan="4" class="text-center text-gray-500">
                                                No brands found.
                                            </td>
                                        </tr>
                                        <tr v-else v-for="brand in brands" :key="brand.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="brand.id"/>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="flex flex-col">
                                                        <span data-modal-toggle="#modal_view_brand" class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px" @click="viewBrandDetail(brand.id)">
                                                            {{ brand.name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                {{ brand.created_at }}
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
                                                                <Link class="menu-link" :href="'/brand/detail/' + brand.id">
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
                                                                <Link class="menu-link" :href="'/brand/edit/' + brand.id">
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

            <!-- Create New Brand Modal -->
            <div id="modal_create_new_brand" data-modal="true" class="modal" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content max-w-[600px] top-[10%]">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Brand</h5>
                            <button type="button" class="btn-close" data-modal-dismiss="modal_create_new_brand" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form @submit.prevent="submitForm">
                                <div class="mb-4">
                                    <label for="brand_name" class="block text-sm font-medium text-gray-700">Brand Name</label>
                                    <input type="text" id="brand_name" v-model="form.name" class="input input-bordered w-full mt-1" required />
                                </div>
                                <!-- Additional form fields as needed -->
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-modal-dismiss="modal_create_new_brand">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Create</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Brand Details Modal -->
            <div class="modal pb-10" data-modal="true" id="modal_view_brand">
                <div class="modal-content max-w-[600px] top-[10%]">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            View Brand
                        </h3>
                        <button class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- If data is loaded -->
                        <div v-if="selectedBrand">
                            <div class="p-5">
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Brand Name</label>
                                    <p class="!text-gray-500">{{ selectedBrand.name }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Created At</label>
                                    <p class="!text-gray-500">{{ selectedBrand.created_at }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Loading skeleton -->
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

        </div>
        <!-- End of Container -->
    </AppLayout>
</template>
