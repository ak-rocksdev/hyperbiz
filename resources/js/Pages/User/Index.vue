<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, onMounted, watch, computed } from 'vue';
    import axios from 'axios';
    import Swal from 'sweetalert2';

    // Define props using defineProps
    const props = defineProps({
        roles: Object,
        users: Object,
        filters: Object,
        
    });

    // Reactive variable to store dynamic user data (if needed)
    const dynamicUsers = ref([]);

    const form = ref({});
    const errors = ref({});

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

    const selectedRoles = ref([]);
    onMounted(() => {
        const modalCreateUser = document.querySelector('#modal_create_new_user');
        if (modalCreateUser) {
            const modalNewUserForm = KTModal.init(modalCreateUser);
        } 
    })
    const submitForm = () => {
        // if (photoInput.value.files && photoInput.value.files.length > 0) {
        //     form.photo = photoInput.value.files[0];
        // }

        try {
            errors.value = {};

            const formData = {
                name: form.value.name,
                email: form.value.email,
                password: form.value.password,
                roles: selectedRoles.value,
            }

            axios.post('/user/api/store', formData)
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
                    document.querySelector('#modal_create_new_user').dispatchEvent(new Event('modal-dismiss'));

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
                text: 'Failed to create new user'
            });
        }

        // Perform form submission logic here           
        // console.log('Form submitted with data:', form);
    }
    // On component mount, load scripts and initialize table
    // onMounted(async () => {
    //     try {
    //         // Load required external script
    //         await loadScript('/assets/js/core.bundle.js');

    //         // Wait for the document to be ready
    //         KTDom.ready(() => {
    //             // loadTable();
    //             KTDataTable.init()

    //             // Initialzie pending datatables
    //             KTDataTable.createInstances();

    //             // const datatableEl = document.querySelector('#users_table');
    //             // const options = {
    //             //     pageSize: 5,
    //             // };
    //             // setTimeout(() => {
    //             //     const datatable = new KTDataTable(datatableEl, options);
    //             //     console.log('datatable', datatable);
                    
    //             // }, 1000);
    //         });
    //     } catch (err) {
    //         console.error('Failed to load script:', err);
    //     }
    // });
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
                    <div class="card-header gap-5">
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
                                <a v-if="$page.props.user?.permissions?.includes('user-create')" class="btn btn-sm btn-primary min-w-[100px] justify-center" data-modal-toggle="#modal_create_new_user">
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
                                        <tr v-for="user in users" :key="user.id">
                                            <td class="text-center">
                                                <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" :value="user.id"/>
                                            </td>
                                            <td class="flex items-center gap-4">
                                                <div v-if="user.profile_photo_path" class="flex items-center justify-center w-12 h-12 rounded-full overflow-hidden border border-teal-400 shrink-0">
                                                    <img :src="'/storage/' + user.profile_photo_path" :alt="user.name" class="w-full h-full object-cover">
                                                </div>
                                                <div v-else class="flex items-center justify-center w-12 h-12 rounded-xl bg-teal-100 text-teal-700 font-bold border border-teal-400 shrink-0">
                                                    <!-- Display initials -->
                                                    {{ user.name.split(' ').length > 1 
                                                        ? user.name.split(' ').map(word => word[0].toUpperCase()).slice(0, 2).join('') 
                                                        : user.name[0].toUpperCase() 
                                                    }}
                                                </div>
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-md font-medium text-gray-900 hover:text-primary-active mb-px hover:cursor-pointer">
                                                        {{ user.name }}
                                                    </span>
                                                    <span class="text-sm text-gray-600">
                                                        {{ user.email }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                {{ user.created_at }}
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
                                                                <Link class="menu-link" :href="'/user/' + user.id">
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
                                                            <div v-if="$page.props.user?.permissions?.includes('user-edit')" class="menu-item">
                                                                <a class="menu-link" href="/user/profile">
                                                                    <span class="menu-icon">
                                                                        <i class="ki-filled ki-pencil">
                                                                        </i>
                                                                    </span>
                                                                    <span class="menu-title">
                                                                        Edit
                                                                    </span>
                                                                </a>
                                                            </div>
                                                            <div v-if="$page.props.user?.permissions?.includes('user-create')" class="menu-item">
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
                                                            <div class="menu-separator">
                                                            </div>
                                                            <div v-if="$page.props.user?.permissions?.includes('user-delete')" class="menu-item">
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

    <div class="modal" data-modal="true" id="modal_create_new_user">
        <div class="modal-content max-w-[600px] top-[10%]">
            <form @submit.prevent="submitForm">
                <div class="modal-header">
                    <h3 class="modal-title">
                        Add User
                    </h3>
                    <a class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                        <i class="ki-outline ki-cross">
                        </i>
                    </a>
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
                        <!-- User Name -->
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
                                placeholder="Enter User Name"
                                type="text"
                                v-model="form.name"
                            />
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Email</label>
                            <input
                                class="input"
                                name="email"
                                placeholder="Enter User Email"
                                type="email"
                                v-model="form.email"
                            />
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Password</label>
                            <input
                                class="input"
                                name="password"
                                placeholder="Enter Password"
                                type="password"
                                v-model="form.password"
                            />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">Confirm Password</label>
                            <input
                                class="input"
                                name="password_confirmation"
                                placeholder="Re-enter Password"
                                type="password"
                                v-model="form.password_confirmation"
                            />
                        </div>

                        <!-- User Roles -->
                        <div class="mb-4">
                            <label class="form-label max-w-60 mb-2">
                                User Roles
                                <span class="ms-1 text-danger">
                                    *
                                </span>
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="role in roles" :key="role.id" class="flex items-center gap-2">
                                    <input
                                        id="`role-${role.id}`"
                                        type="checkbox"
                                        class="form-check-input h-5 w-5 text-primary rounded border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                                        :name="`roles[${role.name}]`"
                                        :value="role.name"
                                        v-model="selectedRoles"
                                    />
                                    <label
                                        :for="`role-${role.id}`"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                    {{ role.name }}
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
                        <button class="btn btn-primary">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>