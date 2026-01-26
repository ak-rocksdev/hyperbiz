<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    roles: Array,
    permissions: Array,
    permissionsGrouped: Object,
});

const page = usePage();
const hasPermission = (permission) => page.props.user?.permissions?.includes(permission) ?? false;

// State
const selectedRoles = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDuplicateModal = ref(false);
const selectedRole = ref(null);
const isSubmitting = ref(false);
const errors = ref({});
const searchQuery = ref('');

// Form data
const form = ref({
    name: '',
    permissions: [],
});

// Duplicate form
const duplicateForm = ref({
    name: '',
});

// Expanded module groups in modal
const expandedModules = ref({});

// Filtered roles based on search
const filteredRoles = computed(() => {
    if (!searchQuery.value) return props.roles;
    const query = searchQuery.value.toLowerCase();
    return props.roles.filter(role =>
        role.name.toLowerCase().includes(query)
    );
});

// Group permissions by module for display
const getPermissionsByModule = (permissions) => {
    const grouped = {};
    permissions.forEach(p => {
        const module = p.split('.')[0];
        if (!grouped[module]) grouped[module] = [];
        grouped[module].push(p);
    });
    return grouped;
};

// Toggle module expansion in permission selector
const toggleModule = (module) => {
    expandedModules.value[module] = !expandedModules.value[module];
};

// Select/deselect all permissions in a module
const toggleModulePermissions = (module, permissions) => {
    const modulePerms = permissions.map(p => p.name);
    const allSelected = modulePerms.every(p => form.value.permissions.includes(p));

    if (allSelected) {
        form.value.permissions = form.value.permissions.filter(p => !modulePerms.includes(p));
    } else {
        const newPerms = modulePerms.filter(p => !form.value.permissions.includes(p));
        form.value.permissions = [...form.value.permissions, ...newPerms];
    }
};

// Check if all permissions in module are selected
const isModuleFullySelected = (module, permissions) => {
    const modulePerms = permissions.map(p => p.name);
    return modulePerms.length > 0 && modulePerms.every(p => form.value.permissions.includes(p));
};

// Check if some permissions in module are selected
const isModulePartiallySelected = (module, permissions) => {
    const modulePerms = permissions.map(p => p.name);
    const selectedCount = modulePerms.filter(p => form.value.permissions.includes(p)).length;
    return selectedCount > 0 && selectedCount < modulePerms.length;
};

// Open create modal
const openCreateModal = () => {
    form.value = { name: '', permissions: [] };
    errors.value = {};
    expandedModules.value = {};
    showCreateModal.value = true;
};

// Open edit modal
const openEditModal = (role) => {
    selectedRole.value = role;
    form.value = {
        name: role.name,
        permissions: [...role.permissions],
    };
    errors.value = {};
    // Expand modules that have selected permissions
    Object.keys(props.permissionsGrouped || {}).forEach(module => {
        const modulePerms = props.permissionsGrouped[module].map(p => p.name);
        if (modulePerms.some(p => role.permissions.includes(p))) {
            expandedModules.value[module] = true;
        }
    });
    showEditModal.value = true;
};

// Open duplicate modal
const openDuplicateModal = (role) => {
    selectedRole.value = role;
    duplicateForm.value = { name: `${role.name} (Copy)` };
    errors.value = {};
    showDuplicateModal.value = true;
};

// Close all modals
const closeModals = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    showDuplicateModal.value = false;
    selectedRole.value = null;
    errors.value = {};
};

// Reload data
const reloadData = () => {
    router.reload({ only: ['roles', 'permissions', 'permissionsGrouped', 'stats'] });
};

// Create role
const submitCreate = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/access-management/roles', form.value);

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
        reloadData();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to create role.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Update role
const submitEdit = async () => {
    if (isSubmitting.value || !selectedRole.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.put(`/access-management/roles/${selectedRole.value.id}`, form.value);

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
        reloadData();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to update role.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Duplicate role
const submitDuplicate = async () => {
    if (isSubmitting.value || !selectedRole.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post(`/access-management/roles/${selectedRole.value.id}/duplicate`, duplicateForm.value);

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
        reloadData();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to duplicate role.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Delete role
const deleteRole = async (role) => {
    const result = await Swal.fire({
        title: 'Delete Role?',
        html: `Are you sure you want to delete <strong>${role.name}</strong>?<br><small class="text-gray-500">This action cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/access-management/roles/${role.id}`);

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message,
            });

            reloadData();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to delete role.',
            });
        }
    }
};

// Bulk delete
const bulkDelete = async () => {
    if (selectedRoles.value.length === 0) return;

    const result = await Swal.fire({
        title: 'Delete Selected Roles?',
        text: `Are you sure you want to delete ${selectedRoles.value.length} role(s)?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete all',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.post('/access-management/roles/bulk-delete', {
                ids: selectedRoles.value
            });

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message,
            });

            selectedRoles.value = [];
            reloadData();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to delete roles.',
            });
        }
    }
};

// Toggle role selection
const toggleRoleSelection = (roleId) => {
    const index = selectedRoles.value.indexOf(roleId);
    if (index > -1) {
        selectedRoles.value.splice(index, 1);
    } else {
        selectedRoles.value.push(roleId);
    }
};

// Toggle all roles selection
const toggleAllRoles = () => {
    if (selectedRoles.value.length === filteredRoles.value.length) {
        selectedRoles.value = [];
    } else {
        selectedRoles.value = filteredRoles.value.map(r => r.id);
    }
};

// Get module badge color
const getModuleBadgeColor = (module) => {
    const colors = {
        'users': 'badge-primary',
        'roles': 'badge-success',
        'customers': 'badge-info',
        'transactions': 'badge-warning',
        'products': 'badge-danger',
        'brands': 'badge-dark',
        'product-categories': 'badge-secondary',
        'company': 'badge-light',
        'logs': 'badge-primary',
    };
    return colors[module] || 'badge-secondary';
};

// Format module name for display
const formatModuleName = (module) => {
    return module
        .replace(/-/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<template>
    <div class="p-5">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Roles</h3>
                <p class="text-sm text-gray-500">Manage user roles and their permissions</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative">
                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2 z-10"></i>
                    <input
                        type="text"
                        class="input input-sm w-[200px]"
                        style="padding-left: 2.25rem;"
                        placeholder="Search roles..."
                        v-model="searchQuery"
                    />
                </div>

                <!-- Bulk Actions -->
                <button
                    v-if="selectedRoles.length > 0 && hasPermission('roles.delete')"
                    @click="bulkDelete"
                    class="btn btn-sm btn-danger"
                >
                    <i class="ki-filled ki-trash me-1"></i>
                    Delete ({{ selectedRoles.length }})
                </button>

                <!-- Create Button -->
                <button
                    v-if="hasPermission('roles.create')"
                    @click="openCreateModal"
                    class="btn btn-sm btn-primary"
                >
                    <i class="ki-filled ki-plus me-1"></i>
                    Create Role
                </button>
            </div>
        </div>

        <!-- Roles Table -->
        <div class="overflow-x-auto">
            <!-- Empty State -->
            <div v-if="filteredRoles.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="ki-filled ki-shield-tick text-5xl mb-4"></i>
                <p class="text-lg">No roles found</p>
                <p class="text-sm">Create your first role to get started</p>
            </div>

            <!-- Table -->
            <table v-else class="table table-auto table-border w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="w-[50px] p-4">
                            <input
                                type="checkbox"
                                class="checkbox checkbox-sm"
                                :checked="selectedRoles.length === filteredRoles.length && filteredRoles.length > 0"
                                @change="toggleAllRoles"
                            />
                        </th>
                        <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase">Role Name</th>
                        <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase">Permissions</th>
                        <th class="text-center p-4 font-semibold text-gray-600 text-xs uppercase w-[100px]">Users</th>
                        <th class="text-center p-4 font-semibold text-gray-600 text-xs uppercase w-[120px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="role in filteredRoles" :key="role.id" class="hover:bg-gray-50">
                        <td class="p-4">
                            <input
                                type="checkbox"
                                class="checkbox checkbox-sm"
                                :checked="selectedRoles.includes(role.id)"
                                @change="toggleRoleSelection(role.id)"
                            />
                        </td>
                        <td class="p-4">
                            <div class="font-medium text-gray-900">{{ role.name }}</div>
                            <div class="text-xs text-gray-400">Created {{ role.created_at }}</div>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(perms, module) in getPermissionsByModule(role.permissions)" :key="module">
                                    <span
                                        class="badge badge-sm"
                                        :class="getModuleBadgeColor(module)"
                                        :title="perms.join(', ')"
                                    >
                                        {{ formatModuleName(module) }} ({{ perms.length }})
                                    </span>
                                </template>
                                <span v-if="role.permissions.length === 0" class="text-gray-400 text-sm">
                                    No permissions
                                </span>
                            </div>
                        </td>
                        <td class="p-4 text-center">
                            <span class="badge badge-sm badge-outline">
                                {{ role.users_count }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <div class="menu inline-flex" data-menu="true">
                                <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click">
                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                        <i class="ki-filled ki-dots-vertical"></i>
                                    </button>
                                    <div class="menu-dropdown menu-default w-[160px]" data-menu-dismiss="true">
                                        <div v-if="hasPermission('roles.edit')" class="menu-item">
                                            <button @click="openEditModal(role)" class="menu-link">
                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                <span class="menu-title">Edit</span>
                                            </button>
                                        </div>
                                        <div v-if="hasPermission('roles.create')" class="menu-item">
                                            <button @click="openDuplicateModal(role)" class="menu-link">
                                                <span class="menu-icon"><i class="ki-filled ki-copy"></i></span>
                                                <span class="menu-title">Duplicate</span>
                                            </button>
                                        </div>
                                        <div class="menu-separator"></div>
                                        <div v-if="hasPermission('roles.delete') && role.name !== 'superadmin'" class="menu-item">
                                            <button @click="deleteRole(role)" class="menu-link">
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
    </div>

    <!-- Create/Edit Role Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <form @submit.prevent="showEditModal ? submitEdit() : submitCreate()">
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ showEditModal ? 'Edit Role' : 'Create New Role' }}
                    </h3>
                    <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-5">
                    <!-- Error Alert -->
                    <div v-if="Object.keys(errors).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Role Name -->
                    <div>
                        <label class="form-label">Role Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            class="input"
                            placeholder="Enter role name"
                            v-model="form.name"
                            required
                            :disabled="showEditModal && selectedRole?.name === 'superadmin'"
                        />
                        <p v-if="showEditModal && selectedRole?.name === 'superadmin'" class="text-xs text-amber-600 mt-1">
                            The superadmin role name cannot be changed.
                        </p>
                    </div>

                    <!-- Permissions by Module -->
                    <div>
                        <label class="form-label mb-3">
                            Permissions
                            <span class="text-gray-400 text-sm font-normal">({{ form.permissions.length }} selected)</span>
                        </label>
                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                            <div v-for="(modulePerms, module) in permissionsGrouped" :key="module">
                                <!-- Module Header (Collapsible) -->
                                <div
                                    @click="toggleModule(module)"
                                    class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50"
                                >
                                    <div class="flex items-center gap-3">
                                        <i
                                            class="ki-filled text-gray-400 transition-transform text-xs"
                                            :class="expandedModules[module] ? 'ki-down' : 'ki-right'"
                                        ></i>
                                        <span class="font-medium text-gray-900">{{ formatModuleName(module) }}</span>
                                        <span
                                            class="badge badge-sm"
                                            :class="isModuleFullySelected(module, modulePerms) ? 'badge-success' : isModulePartiallySelected(module, modulePerms) ? 'badge-warning' : 'badge-outline'"
                                        >
                                            {{ modulePerms.filter(p => form.permissions.includes(p.name)).length }}/{{ modulePerms.length }}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click.stop="toggleModulePermissions(module, modulePerms)"
                                        class="btn btn-xs"
                                        :class="isModuleFullySelected(module, modulePerms) ? 'btn-primary' : 'btn-light'"
                                    >
                                        {{ isModuleFullySelected(module, modulePerms) ? 'Deselect All' : 'Select All' }}
                                    </button>
                                </div>
                                <!-- Module Permissions -->
                                <div v-if="expandedModules[module]" class="p-3 pt-0 bg-gray-50">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 ml-6">
                                        <label
                                            v-for="perm in modulePerms"
                                            :key="perm.id"
                                            class="flex items-center gap-2 cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                class="checkbox checkbox-sm"
                                                :value="perm.name"
                                                v-model="form.permissions"
                                            />
                                            <span class="text-sm text-gray-700">{{ perm.name.split('.')[1] }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                    <button type="button" @click="closeModals" class="btn btn-light">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <span v-if="isSubmitting">{{ showEditModal ? 'Saving...' : 'Creating...' }}</span>
                        <span v-else>{{ showEditModal ? 'Save Changes' : 'Create Role' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Duplicate Role Modal -->
    <div v-if="showDuplicateModal && selectedRole" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <form @submit.prevent="submitDuplicate">
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Duplicate Role</h3>
                    <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-800">
                            <i class="ki-filled ki-information me-1"></i>
                            Duplicating <strong>{{ selectedRole.name }}</strong> with {{ selectedRole.permissions.length }} permission(s).
                        </p>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="Object.keys(errors).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <label class="form-label">New Role Name <span class="text-red-500">*</span></label>
                        <input type="text" class="input" placeholder="Enter new role name" v-model="duplicateForm.name" required />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                    <button type="button" @click="closeModals" class="btn btn-light">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <span v-if="isSubmitting">Duplicating...</span>
                        <span v-else>Duplicate Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
