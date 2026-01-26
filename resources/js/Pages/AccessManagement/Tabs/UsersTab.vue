<script setup>
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    users: Array,
    roles: Array,
    pagination: Object,
    filters: Object,
});

// Reactive state
const users = ref(props.users || []);
const currentPage = ref(Number(props.pagination?.current_page) || 1);
const lastPage = ref(Number(props.pagination?.last_page) || 1);
const totalResults = ref(Number(props.pagination?.total) || 0);
const perPage = ref(Number(props.pagination?.per_page) || 10);
const perPageOptions = [10, 25, 50, 100];

// Filters
const searchQuery = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const statusFilter = ref(props.filters?.status || '');

// Modal state
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailModal = ref(false);
const selectedUser = ref(null);
const isSubmitting = ref(false);

// Form data
const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});
const errors = ref({});

// Debounce timer
let searchTimeout = null;

// Watch for filter changes
watch([searchQuery, roleFilter, statusFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchUsers();
    }, 300);
});

watch(perPage, () => {
    currentPage.value = 1;
    fetchUsers();
});

watch(currentPage, () => {
    fetchUsers();
});

// Watch props changes
watch(() => props.users, (newUsers) => {
    users.value = newUsers || [];
});

watch(() => props.pagination, (newPagination) => {
    currentPage.value = Number(newPagination?.current_page) || 1;
    lastPage.value = Number(newPagination?.last_page) || 1;
    totalResults.value = Number(newPagination?.total) || 0;
    perPage.value = Number(newPagination?.per_page) || 10;
});

// Fetch users with filters
const fetchUsers = () => {
    const params = {
        tab: 'users',
        page: currentPage.value,
        per_page: perPage.value,
    };

    if (searchQuery.value) params.search = searchQuery.value;
    if (roleFilter.value) params.role = roleFilter.value;
    if (statusFilter.value) params.status = statusFilter.value;

    router.get(route('access-management.index'), params, {
        preserveState: true,
        replace: true,
    });
};

// Reload users without page navigation
const reloadUsers = () => {
    router.reload({ only: ['users', 'pagination', 'stats'] });
};

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    roleFilter.value = '';
    statusFilter.value = '';
    currentPage.value = 1;
    fetchUsers();
};

// Pagination
const goToPage = (page) => {
    if (typeof page === 'number' && page !== currentPage.value && page >= 1 && page <= lastPage.value) {
        currentPage.value = page;
    }
};

const visiblePages = computed(() => {
    const maxVisible = 5;
    const pages = [];

    if (lastPage.value <= maxVisible) {
        for (let i = 1; i <= lastPage.value; i++) pages.push(i);
    } else {
        if (currentPage.value <= 3) {
            pages.push(1, 2, 3, 4, '...', lastPage.value);
        } else if (currentPage.value > lastPage.value - 3) {
            pages.push(1, '...', lastPage.value - 3, lastPage.value - 2, lastPage.value - 1, lastPage.value);
        } else {
            pages.push(1, '...', currentPage.value - 1, currentPage.value, currentPage.value + 1, '...', lastPage.value);
        }
    }
    return pages;
});

// Get role badge color
const getRoleBadgeClass = (role) => {
    const colors = {
        'superadmin': 'badge-danger',
        'admin': 'badge-primary',
        'staff': 'badge-info',
    };
    return colors[role.toLowerCase()] || 'badge-secondary';
};

// Options for SearchableSelect
const roleOptions = computed(() => {
    return [
        { value: '', label: 'All Roles' },
        ...props.roles.map(role => ({
            value: role.name,
            label: role.name.charAt(0).toUpperCase() + role.name.slice(1),
        }))
    ];
});

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active', sublabel: '' },
    { value: 'inactive', label: 'Inactive', sublabel: '' },
];

// Get user initials
const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length > 1) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name[0].toUpperCase();
};

// Open create modal
const openCreateModal = () => {
    form.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
    };
    errors.value = {};
    showCreateModal.value = true;
};

// Open edit modal
const openEditModal = async (user) => {
    try {
        const response = await axios.get(`/user/api/detail/${user.id}`);
        selectedUser.value = response.data.user;
        form.value = {
            name: selectedUser.value.name,
            email: selectedUser.value.email,
            password: '',
            password_confirmation: '',
            role: selectedUser.value.roles?.[0] || '',
        };
        errors.value = {};
        showEditModal.value = true;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load user details.',
        });
    }
};

// Open detail modal
const openDetailModal = async (user) => {
    try {
        const response = await axios.get(`/user/api/detail/${user.id}`);
        selectedUser.value = response.data.user;
        showDetailModal.value = true;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load user details.',
        });
    }
};

// Close modals
const closeModals = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    showDetailModal.value = false;
    selectedUser.value = null;
    errors.value = {};
};

// Edit from detail modal
const editFromDetail = () => {
    const user = { ...selectedUser.value };
    showDetailModal.value = false;
    openEditModal(user);
};

// Submit create form
const submitCreate = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/user/api/store', form.value);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message,
        });

        closeModals();
        reloadUsers();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to create user.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Submit edit form
const submitEdit = async () => {
    if (isSubmitting.value || !selectedUser.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.put(`/user/api/update/${selectedUser.value.id}`, form.value);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message,
        });

        closeModals();
        reloadUsers();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to update user.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Toggle user status
const toggleStatus = async (user) => {
    const result = await Swal.fire({
        title: user.is_active ? 'Deactivate User?' : 'Activate User?',
        text: user.is_active
            ? `Are you sure you want to deactivate ${user.name}?`
            : `Are you sure you want to activate ${user.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: user.is_active ? '#f59e0b' : '#10b981',
        confirmButtonText: user.is_active ? 'Yes, deactivate' : 'Yes, activate',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/user/api/toggle-status/${user.id}`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message,
            });

            reloadUsers();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to update user status.',
            });
        }
    }
};

// Delete user
const deleteUser = async (user) => {
    const result = await Swal.fire({
        title: 'Delete User?',
        text: `Are you sure you want to delete ${user.name}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/user/api/delete/${user.id}`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message,
            });

            reloadUsers();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to delete user.',
            });
        }
    }
};

// Check permissions
const page = usePage();

const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};
</script>

<template>
    <div class="p-5">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Users</h3>
                <p class="text-sm text-gray-500">Manage user accounts and permissions</p>
            </div>
            <!-- Create Button -->
            <button
                v-if="hasPermission('users.create')"
                @click="openCreateModal"
                class="btn btn-primary"
            >
                <i class="ki-filled ki-plus-squared me-1.5"></i>
                Add User
            </button>
        </div>

        <!-- Filters Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-4 bg-gray-50 rounded-lg mb-5">
            <!-- Search -->
            <div class="relative flex-1 min-w-0">
                <i class="ki-filled ki-magnifier text-gray-400 absolute top-1/2 left-3 -translate-y-1/2"></i>
                <input
                    type="text"
                    class="input input-sm w-full bg-white"
                    style="padding-left: 2.5rem;"
                    placeholder="Search by name or email..."
                    v-model="searchQuery"
                />
            </div>

            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Role Filter -->
                <div class="min-w-[150px]">
                    <SearchableSelect
                        v-model="roleFilter"
                        :options="roleOptions"
                        placeholder="All Roles"
                        :searchable="roles.length > 5"
                        size="sm"
                    />
                </div>

                <!-- Status Filter -->
                <div class="min-w-[140px]">
                    <SearchableSelect
                        v-model="statusFilter"
                        :options="statusOptions"
                        placeholder="All Status"
                        :searchable="false"
                        size="sm"
                    />
                </div>

                <!-- Clear Filters -->
                <button
                    v-if="searchQuery || roleFilter || statusFilter"
                    @click="clearFilters"
                    class="btn btn-sm btn-icon btn-light bg-white"
                    title="Clear filters"
                >
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <!-- Empty State -->
            <div v-if="users.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400 bg-white">
                <i class="ki-filled ki-people text-5xl mb-4"></i>
                <p class="text-lg font-medium">No users found</p>
                <p class="text-sm">Try adjusting your search or filters</p>
            </div>

            <!-- Table -->
            <table v-else class="table-auto w-full">
                <thead>
                    <tr class="bg-gray-100/80">
                        <th class="text-left px-5 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">User</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[140px]">Role</th>
                        <th class="text-center px-5 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[100px]">Status</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[160px]">Registered</th>
                        <th class="text-center px-5 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[80px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                        <!-- User Info -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div v-if="user.profile_photo_path" class="w-10 h-10 rounded-full overflow-hidden border-2 border-gray-100 shadow-sm flex-shrink-0">
                                    <img :src="'/storage/' + user.profile_photo_path" :alt="user.name" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-primary/10 text-primary font-semibold flex items-center justify-center text-sm flex-shrink-0">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 truncate">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500 truncate">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Roles -->
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="role in user.roles"
                                    :key="role"
                                    class="badge badge-sm capitalize"
                                    :class="getRoleBadgeClass(role)"
                                >
                                    {{ role }}
                                </span>
                                <span v-if="!user.roles?.length" class="text-gray-400 text-sm italic">No role</span>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="px-5 py-4 text-center">
                            <span
                                class="badge badge-sm badge-outline"
                                :class="user.is_active ? 'badge-success' : 'badge-warning'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full me-1.5" :class="user.is_active ? 'bg-green-500' : 'bg-amber-500'"></span>
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <!-- Registered -->
                        <td class="px-5 py-4">
                            <div class="text-sm text-gray-900">{{ user.created_at }}</div>
                            <div class="text-xs text-gray-400">{{ user.human_readable_time }}</div>
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-4 text-center">
                            <div class="menu inline-flex" data-menu="true">
                                <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click">
                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                        <i class="ki-filled ki-dots-vertical"></i>
                                    </button>
                                    <div class="menu-dropdown menu-default w-[160px]" data-menu-dismiss="true">
                                        <!-- View -->
                                        <div class="menu-item">
                                            <button @click="openDetailModal(user)" class="menu-link">
                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                <span class="menu-title">View Details</span>
                                            </button>
                                        </div>

                                        <!-- Edit -->
                                        <div v-if="hasPermission('users.edit')" class="menu-item">
                                            <button @click="openEditModal(user)" class="menu-link">
                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                <span class="menu-title">Edit</span>
                                            </button>
                                        </div>

                                        <!-- Toggle Status -->
                                        <div v-if="hasPermission('users.edit')" class="menu-item">
                                            <button @click="toggleStatus(user)" class="menu-link">
                                                <span class="menu-icon">
                                                    <i :class="user.is_active ? 'ki-filled ki-lock' : 'ki-filled ki-lock-open'"></i>
                                                </span>
                                                <span class="menu-title">{{ user.is_active ? 'Deactivate' : 'Activate' }}</span>
                                            </button>
                                        </div>

                                        <div class="menu-separator"></div>

                                        <!-- Delete -->
                                        <div v-if="hasPermission('users.delete')" class="menu-item">
                                            <button @click="deleteUser(user)" class="menu-link">
                                                <span class="menu-icon"><i class="ki-filled ki-trash text-red-500"></i></span>
                                                <span class="menu-title text-red-500">Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.length > 0" class="flex flex-col sm:flex-row gap-3 text-gray-600 text-sm justify-between items-center px-5 py-4 border-t border-gray-200 bg-white rounded-b-lg">
            <div class="flex items-center gap-2 text-gray-500">
                <span>Showing</span>
                <select class="select select-sm w-16 bg-gray-50" v-model.number="perPage">
                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                </select>
                <span>of <span class="font-medium text-gray-700">{{ totalResults }}</span> users</span>
            </div>

            <div class="pagination flex items-center gap-1">
                <button
                    class="btn btn-sm btn-icon btn-light"
                    :disabled="currentPage <= 1"
                    @click="goToPage(currentPage - 1)"
                    :class="{ 'opacity-50 cursor-not-allowed': currentPage <= 1 }"
                >
                    <i class="ki-outline ki-black-left"></i>
                </button>

                <template v-for="(pg, index) in visiblePages" :key="index">
                    <span v-if="pg === '...'" class="px-2 text-gray-400 select-none">...</span>
                    <button
                        v-else
                        class="btn btn-sm btn-icon"
                        :class="pg === currentPage ? 'btn-primary text-white' : 'btn-light hover:bg-gray-100'"
                        @click="goToPage(pg)"
                    >
                        {{ pg }}
                    </button>
                </template>

                <button
                    class="btn btn-sm btn-icon btn-light"
                    :disabled="currentPage >= lastPage"
                    @click="goToPage(currentPage + 1)"
                    :class="{ 'opacity-50 cursor-not-allowed': currentPage >= lastPage }"
                >
                    <i class="ki-outline ki-black-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <form @submit.prevent="submitCreate">
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add New User</h3>
                    <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Error Alert -->
                    <div v-if="Object.keys(errors).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                        <p class="font-semibold mb-2">Please fix the following errors:</p>
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="form-label">Name <span class="text-red-500">*</span></label>
                        <input type="text" class="input" placeholder="Enter full name" v-model="form.name" required />
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="form-label">Email <span class="text-red-500">*</span></label>
                        <input type="email" class="input" placeholder="Enter email address" v-model="form.email" required />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="form-label">Password <span class="text-red-500">*</span></label>
                        <input type="password" class="input" placeholder="Enter password (min 8 characters)" v-model="form.password" required />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" class="input" placeholder="Re-enter password" v-model="form.password_confirmation" required />
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="form-label">Role <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-4 mt-2">
                            <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    class="radio radio-sm"
                                    :value="role.name"
                                    v-model="form.role"
                                    name="create_role"
                                />
                                <span class="text-sm text-gray-700 capitalize">{{ role.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                    <button type="button" @click="closeModals" class="btn btn-light">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <span v-if="isSubmitting">Creating...</span>
                        <span v-else>Create User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="showEditModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <form @submit.prevent="submitEdit">
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
                    <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Error Alert -->
                    <div v-if="Object.keys(errors).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                        <p class="font-semibold mb-2">Please fix the following errors:</p>
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="form-label">Name <span class="text-red-500">*</span></label>
                        <input type="text" class="input" placeholder="Enter full name" v-model="form.name" required />
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="form-label">Email <span class="text-red-500">*</span></label>
                        <input type="email" class="input" placeholder="Enter email address" v-model="form.email" required />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="form-label">New Password <span class="text-gray-400 text-sm">(leave blank to keep current)</span></label>
                        <input type="password" class="input" placeholder="Enter new password" v-model="form.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="input" placeholder="Re-enter new password" v-model="form.password_confirmation" />
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="form-label">Role <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-4 mt-2">
                            <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    class="radio radio-sm"
                                    :value="role.name"
                                    v-model="form.role"
                                    name="edit_role"
                                />
                                <span class="text-sm text-gray-700 capitalize">{{ role.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                    <button type="button" @click="closeModals" class="btn btn-light">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <span v-if="isSubmitting">Saving...</span>
                        <span v-else>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View User Detail Modal -->
    <div v-if="showDetailModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">User Details</h3>
                <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                    <i class="ki-outline ki-cross"></i>
                </button>
            </div>
            <div class="p-5">
                <!-- User Avatar & Name -->
                <div class="flex items-center gap-4 mb-6">
                    <div v-if="selectedUser.profile_photo_path" class="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-200">
                        <img :src="'/storage/' + selectedUser.profile_photo_path" :alt="selectedUser.name" class="w-full h-full object-cover">
                    </div>
                    <div v-else class="w-16 h-16 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-xl">
                        {{ getInitials(selectedUser.name) }}
                    </div>
                    <div>
                        <h4 class="text-xl font-semibold text-gray-900">{{ selectedUser.name }}</h4>
                        <p class="text-gray-500">{{ selectedUser.email }}</p>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 uppercase">Status</label>
                        <div class="mt-1">
                            <span class="badge" :class="selectedUser.is_active ? 'badge-success' : 'badge-warning'">
                                {{ selectedUser.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase">Roles</label>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span v-for="role in selectedUser.roles" :key="role" class="badge badge-sm" :class="getRoleBadgeClass(role)">
                                {{ role }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase">Created At</label>
                        <p class="text-sm text-gray-900 mt-1">{{ selectedUser.created_at }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase">Updated At</label>
                        <p class="text-sm text-gray-900 mt-1">{{ selectedUser.updated_at }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                <button type="button" @click="closeModals" class="btn btn-light">Close</button>
                <button v-if="hasPermission('users.edit')" @click="editFromDetail" class="btn btn-primary">
                    <i class="ki-filled ki-pencil me-1"></i> Edit User
                </button>
            </div>
        </div>
    </div>
</template>
