<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, onMounted } from 'vue';
    import axios from 'axios';

    // Define props using defineProps
    const props = defineProps({
        users: Object,
        filters: Object,
    });

    // Reactive variable to store dynamic user data (if needed)
    const dynamicUsers = ref([]);

    // Fetch users dynamically via API if needed
    const fetchUsers = async () => {
        try {
            const response = await axios.get('/api/users');
            dynamicUsers.value = response.data.data; // Use response.data if no pagination
        } catch (error) {
            console.error('Error fetching users:', error);
        }
    };

    // Function to load external scripts dynamically
    const loadScript = (src) => {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });
    };

    // Initialize the table with data
    const loadTable = () => {
        const apiUrl = 'http://localhost:8000/api/users';
        const element = document.querySelector('#users_table');

        if (!element) {
            console.error('Table element not found!');
            return;
        }

        const dataTableOptions = {
            apiEndpoint: apiUrl,
            pageSize: 5, // Number of rows per page
            serverSide: true, // Enable server-side processing
            columns: {
                select: {
                    render: (item, data) => {
                        const checkbox = document.createElement('input');
                        checkbox.className = 'checkbox checkbox-sm';
                        checkbox.type = 'checkbox';
                        checkbox.value = data.id.toString();
                        checkbox.setAttribute('data-datatable-row-check', 'true');
                        return checkbox.outerHTML.trim();
                    },
                    createdCell(cell) {
                        cell.classList.add('text-center');
                    },
                },
                name: {
                    title: 'Name',
                    render: (item) => item,
                },
                email: {
                    title: 'Email',
                    render: (_, data) => data.email,
                },
                created_at: {
                    title: 'Registered At',
                    render: (item) => item,
                },
                action: {
                    title: 'Action',
                    render: (_, data) => `
                        <a class="btn btn-primary btn-sm" href="/user/${data.id}">
                            View Detail
                        </a>
                    `,
                    createdCell(cell) {
                        cell.classList.add('text-center');
                    },
                },
            },
            requestParams: (page, pageSize) => ({
                page,
                per_page: pageSize,
            }),
            parseResponse: (response) => ({
                rows: response.data,
                totalRecords: response.total,
            }),
        };

        new KTDataTable(element, dataTableOptions);
    };

    // On component mount, load scripts and initialize table
    onMounted(async () => {
        try {
            // Load required external script
            await loadScript('/assets/js/core.bundle.js');

            // Wait for the document to be ready
            KTDom.ready(() => {
                // loadTable();
                KTDataTable.init()

                // Initialzie pending datatables
                KTDataTable.createInstances();

                // const datatableEl = document.querySelector('#users_table');
                // const options = {
                //     pageSize: 5,
                // };
                // setTimeout(() => {
                //     const datatable = new KTDataTable(datatableEl, options);
                //     console.log('datatable', datatable);
                    
                // }, 1000);
            });
        } catch (err) {
            console.error('Failed to load script:', err);
        }
    });
</script>


<template>
    <AppLayout title="User">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                User
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header">
                        <h3 class="card-title">
                            Users
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input data-datatable-search="#users_table" class="input input-sm ps-8" placeholder="Search Users" value="" />
                                </div>
                                <a class="btn btn-sm btn-primary" href="/user/create">
                                    Create User
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="true" id="users_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px]">
                                                <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox"/>
                                            </th>
                                            <th class="min-w-[170px]" data-datatable-column="name">
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
                                                        Email
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[185px]">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Registered At
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[185px] text-center">
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
                                        <tr v-for="user in users" :key="user.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="user.id"/>
                                            </td>
                                            <td>
                                                {{ user.name }}
                                            </td>
                                            <td>
                                                {{ user.email }}
                                            </td>
                                            <td>
                                                {{ user.created_at }}
                                            </td>
                                            <td class="text-center">
                                                <a class="btn btn-primary btn-sm" :href="'/user/' + user.id">
                                                    View Detail
                                                </a>
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
</template>