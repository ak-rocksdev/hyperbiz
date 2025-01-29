<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, onMounted, watch, computed } from 'vue';
    import axios from 'axios';
    import Swal from 'sweetalert2';

    // Define props using defineProps
    const props = defineProps({
        permissions: Object,
        roles: Object,
        filters: Object,
        
    });

    // Reactive variable to store dynamic user data (if needed)
    const dynamicRoles = ref([]);

    const form = ref({});
    const errors = ref({});

    // Fetch roles dynamically via API if needed
    const fetchRoles = async () => {
        try {
            const response = await axios.get('/api/roles');
            dynamicRoles.value = response.data.data; // Use response.data if no pagination
        } catch (error) {
            console.error('Error fetching roles:', error);
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
        const apiUrl = 'http://localhost:8000/api/roles';   
        const element = document.querySelector('#roles_table');

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
                action: {
                    title: 'Action',
                    render: (_, data) => `
                        <a class="btn btn-primary btn-sm" href="/role/${data.id}">
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

    const selectedPermissions = ref([]);
    const submitForm = () => {
        // form.permissions = selectedPermissions.value;
        try {
            errors.value = {};
            // console.log("selected :", form.value);
            const formData = {
                name: form.value.name,
                permissions: selectedPermissions.value,
            }
            
            axios.post('/roles', formData)
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
                    // Close the modal
                    document.querySelector('#modal_create_new_role').dispatchEvent(new Event('modal-dismiss'));

                    // continue action after success using best practice
                    window.location.reload();
                    // router.visit(route('user.list'));
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
        } catch (error) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'error',
                title: 'Error',
                text: 'Failed to create new role'
            });
        }

        // Perform form submission logic here           
        // console.log('Form submitted with data:', form);
    }
    
</script>


<template>
    <AppLayout title="Role">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Roles
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Roles
                        </h3>
                        <div class="card-toolbar">
                            <div class="flex gap-6">
                                <div class="relative">
                                    <i
                                        class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3">
                                    </i>
                                    <input data-datatable-search="#roles_table" class="input input-sm ps-8" placeholder="Search Roles" value="" />
                                </div>
                                <a v-if="$page.props.user?.permissions?.includes('role-create')" class="btn btn-sm btn-primary min-w-[100px] justify-center" data-modal-toggle="#modal_create_new_role">
                                    Create Role
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="true" id="roles_table">
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
                                                        Role Name
                                                    </span>
                                                    <span class="sort-icon">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[180px] w-[200px] text-center">
                                                <span class="sort">
                                                    <span class="sort-label">
                                                        Permissions
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
                                        <tr v-for="role in roles" :key="role.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="role.id"/>
                                            </td>
                                            <td class="text-justify">
                                                <div class="">
                                                    <span
                                                        class="text-md font-medium text-gray-900 hover:text-primary-active mb-px hover:cursor-pointer">
                                                        {{ role.name }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="align-center">
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="permission in role.permissions" :key="permission"
                                                    class="badge badge-sm badge-pill badge-primary">
                                                        {{ permission }}
                                                    </span>
                                                </div>
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
                                                                <Link class="menu-link" :href="'/role/' + role.id" data-modal-toggle="#modal_view_role">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-search-list">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        View
                                                                    </span>
                                                                </Link>
                                                            </div>
                                                            <div class="menu-separator">
                                                            </div>
                                                            <div v-if="$page.props.user?.permissions?.includes('role-edit')" class="menu-item">
                                                                <a class="menu-link" data-modal-toggle="#modal_edit_role">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-pencil">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Edit
                                                                    </span>
                                                                </a>
                                                            </div>
                                                            <div v-if="$page.props.user?.permissions?.includes('role-create')" class="menu-item">
                                                                <a class="menu-link" href="#" data-modal-toggle="#modal_copy_role">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-copy">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Make a copy
                                                                    </span>
                                                                </a>
                                                            </div>
                                                            <div class="menu-separator">
                                                            </div>
                                                            <div v-if="$page.props.user?.permissions?.includes('role-delete')" class="menu-item">
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
    <!-- Modal Create New Role -->
    <div class="modal" data-modal="true" id="modal_create_new_role">
        <div class="modal-content max-w-[600px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">
                        Add Role
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
                        <!-- Role Name -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Name
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="name"
                                placeholder="Enter Role Name"
                                type="text"
                                v-model="form.name"
                            />
                        </div>

                        <!-- Permissions -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Permissions
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2">
                                    <input
                                        id="`permission-${permission.id}`"
                                        type="checkbox"
                                        class="form-check-input h-5 w-5 text-primary rounded border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                                        :name="`permissions[${permission.name}]`"
                                        :value="permission.name"
                                        v-model="selectedPermissions"
                                    />
                                    <label
                                        :for="`permission-${permission.id}`"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                    {{ permission.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-end">
                    <div class="flex gap-4">
                        <a class="btn btn-light" data-modal-dismiss="true">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- End of Modal Create New Role -->
    
    <!-- Modal Edit Role -->
    <div class="modal" data-modal="true" id="modal_edit_role">
        <div class="modal-content max-w-[600px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">
                        Edit Role
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
                        <!-- Role Name -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Name
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <input
                                class="input"
                                name="name"
                                placeholder="Enter Role Name"
                                type="text"
                                v-model="form.name"
                            />
                        </div>

                        <!-- Permissions -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                Permissions
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2">
                                    <input
                                        id="`permission-${permission.id}`"
                                        type="checkbox"
                                        class="form-check-input h-5 w-5 text-primary rounded border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                                        :name="`permissions[${permission.name}]`"
                                        :value="permission.name"
                                        v-model="selectedPermissions"
                                    />
                                    <label
                                        :for="`permission-${permission.id}`"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                    {{ permission.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-end">
                    <div class="flex gap-4">
                        <a class="btn btn-light" data-modal-dismiss="true">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- End of Modal Edit Role --> 
</template>