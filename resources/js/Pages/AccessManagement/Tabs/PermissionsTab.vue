<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    permissions: Array,
});

const page = usePage();
const hasPermission = (permission) => page.props.user?.permissions?.includes(permission) ?? false;

// State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedPermission = ref(null);
const isSubmitting = ref(false);
const errors = ref({});
const searchQuery = ref('');

// Form data
const form = ref({
    name: '',
});

// Computed: filtered permissions
const filteredPermissions = computed(() => {
    if (!searchQuery.value) return props.permissions;
    const query = searchQuery.value.toLowerCase();
    return props.permissions.filter(p =>
        p.name.toLowerCase().includes(query) ||
        p.module.toLowerCase().includes(query)
    );
});

// Computed: permissions grouped by module for display
const permissionsByModule = computed(() => {
    const grouped = {};
    filteredPermissions.value.forEach(p => {
        if (!grouped[p.module]) grouped[p.module] = [];
        grouped[p.module].push(p);
    });
    return grouped;
});

// Get available modules for suggestion
const availableModules = computed(() => {
    return [...new Set(props.permissions.map(p => p.module))].sort();
});

// Reload data
const reloadData = () => {
    router.reload({ only: ['permissions', 'permissionsGrouped', 'stats'] });
};

// Open create modal
const openCreateModal = () => {
    form.value = { name: '' };
    errors.value = {};
    showCreateModal.value = true;
};

// Open edit modal
const openEditModal = (permission) => {
    selectedPermission.value = permission;
    form.value = { name: permission.name };
    errors.value = {};
    showEditModal.value = true;
};

// Close modals
const closeModals = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedPermission.value = null;
    errors.value = {};
};

// Create permission
const submitCreate = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/access-management/permissions', form.value);

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
                text: error.response?.data?.message || 'Failed to create permission.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Update permission
const submitEdit = async () => {
    if (isSubmitting.value || !selectedPermission.value) return;
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.put(`/access-management/permissions/${selectedPermission.value.id}`, form.value);

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
                text: error.response?.data?.message || 'Failed to update permission.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Delete permission
const deletePermission = async (permission) => {
    // Warn if roles are using it
    if (permission.roles_count > 0) {
        await Swal.fire({
            title: 'Permission In Use',
            html: `This permission is used by <strong>${permission.roles_count}</strong> role(s).<br><br>You must remove it from all roles before deleting.`,
            icon: 'warning',
            confirmButtonText: 'Understood',
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Delete Permission?',
        html: `Are you sure you want to delete <code class="bg-gray-100 px-2 py-1 rounded">${permission.name}</code>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/access-management/permissions/${permission.id}`);

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
                text: error.response?.data?.message || 'Failed to delete permission.',
            });
        }
    }
};

// Format module name
const formatModuleName = (module) => {
    return module
        .replace(/-/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

// Set module prefix when typing
const suggestModule = (module) => {
    form.value.name = `${module}.`;
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
</script>

<template>
    <div class="p-5">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Permissions</h3>
                <p class="text-sm text-gray-500">Manage system permissions (module.action format)</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative">
                    <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2 z-10"></i>
                    <input
                        type="text"
                        class="input input-sm w-[200px]"
                        style="padding-left: 2.25rem;"
                        placeholder="Search permissions..."
                        v-model="searchQuery"
                    />
                </div>

                <!-- Create Button -->
                <button
                    v-if="hasPermission('roles.create')"
                    @click="openCreateModal"
                    class="btn btn-sm btn-primary"
                >
                    <i class="ki-filled ki-plus me-1"></i>
                    Create Permission
                </button>
            </div>
        </div>

        <!-- Permissions Table -->
        <div class="overflow-x-auto">
            <!-- Empty State -->
            <div v-if="filteredPermissions.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="ki-filled ki-key text-5xl mb-4"></i>
                <p class="text-lg">No permissions found</p>
                <p class="text-sm">Create your first permission to get started</p>
            </div>

            <!-- Table -->
            <table v-else class="table table-auto table-border w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase">Permission Name</th>
                        <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase w-[150px]">Module</th>
                        <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase w-[120px]">Action</th>
                        <th class="text-center p-4 font-semibold text-gray-600 text-xs uppercase w-[120px]">Used By</th>
                        <th class="text-center p-4 font-semibold text-gray-600 text-xs uppercase w-[100px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="permission in filteredPermissions" :key="permission.id" class="hover:bg-gray-50">
                        <td class="p-4">
                            <code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono text-gray-800">
                                {{ permission.name }}
                            </code>
                        </td>
                        <td class="p-4">
                            <span class="badge badge-sm" :class="getModuleBadgeColor(permission.module)">
                                {{ formatModuleName(permission.module) }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-gray-700">{{ permission.action }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <span
                                class="badge badge-sm"
                                :class="permission.roles_count > 0 ? 'badge-success' : 'badge-outline'"
                            >
                                {{ permission.roles_count }} role(s)
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button
                                    v-if="hasPermission('roles.edit')"
                                    @click="openEditModal(permission)"
                                    class="btn btn-sm btn-icon btn-light"
                                    title="Edit"
                                >
                                    <i class="ki-filled ki-pencil"></i>
                                </button>
                                <button
                                    v-if="hasPermission('roles.delete')"
                                    @click="deletePermission(permission)"
                                    class="btn btn-sm btn-icon btn-light"
                                    :class="{ 'opacity-50': permission.roles_count > 0 }"
                                    :title="permission.roles_count > 0 ? 'Cannot delete - in use by roles' : 'Delete'"
                                >
                                    <i class="ki-filled ki-trash text-red-500"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Permission Count -->
        <div v-if="filteredPermissions.length > 0" class="mt-4 text-sm text-gray-500">
            Showing {{ filteredPermissions.length }} of {{ permissions.length }} permissions
        </div>
    </div>

    <!-- Create Permission Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <form @submit.prevent="submitCreate">
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Create Permission</h3>
                    <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Error Alert -->
                    <div v-if="Object.keys(errors).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                        <ul class="list-disc pl-5 text-sm">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Info -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-800">
                            <i class="ki-filled ki-information me-1"></i>
                            Use format: <code class="bg-blue-100 px-1 rounded">module.action</code>
                            <br>Example: <code class="bg-blue-100 px-1 rounded">users.export</code>, <code class="bg-blue-100 px-1 rounded">reports.view</code>
                        </p>
                    </div>

                    <!-- Quick Module Selection -->
                    <div v-if="availableModules.length > 0">
                        <label class="form-label text-sm text-gray-500">Quick select module:</label>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <button
                                v-for="module in availableModules"
                                :key="module"
                                type="button"
                                @click="suggestModule(module)"
                                class="badge badge-sm cursor-pointer transition-colors"
                                :class="form.name.startsWith(module + '.') ? 'badge-primary' : 'badge-light hover:badge-primary'"
                            >
                                {{ module }}
                            </button>
                        </div>
                    </div>

                    <!-- Permission Name -->
                    <div>
                        <label class="form-label">Permission Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            class="input font-mono"
                            placeholder="e.g., users.export"
                            v-model="form.name"
                            required
                        />
                        <p class="text-xs text-gray-400 mt-1">Use lowercase letters, numbers, hyphens, and a single dot.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                    <button type="button" @click="closeModals" class="btn btn-light">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <span v-if="isSubmitting">Creating...</span>
                        <span v-else>Create Permission</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Permission Modal -->
    <div v-if="showEditModal && selectedPermission" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" @click="closeModals"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <form @submit.prevent="submitEdit">
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Permission</h3>
                    <button type="button" @click="closeModals" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Warning if in use -->
                    <div v-if="selectedPermission.roles_count > 0" class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <p class="text-sm text-amber-800">
                            <i class="ki-filled ki-information-2 me-1"></i>
                            This permission is used by <strong>{{ selectedPermission.roles_count }}</strong> role(s). Renaming will affect all roles using it.
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

                    <!-- Permission Name -->
                    <div>
                        <label class="form-label">Permission Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            class="input font-mono"
                            placeholder="e.g., users.export"
                            v-model="form.name"
                            required
                        />
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
</template>
