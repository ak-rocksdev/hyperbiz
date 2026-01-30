<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

// Import tab components
import UsersTab from './Tabs/UsersTab.vue';
import RolesTab from './Tabs/RolesTab.vue';
import PermissionsTab from './Tabs/PermissionsTab.vue';

const props = defineProps({
    roles: Array,
    permissions: Array,
    permissionsGrouped: Object,
    users: Array,
    pagination: Object,
    stats: Object,
    activeTab: {
        type: String,
        default: 'users'
    },
    filters: Object,
});

const currentTab = ref(props.activeTab);

// Tab configuration
const tabs = [
    { id: 'users', label: 'Users', icon: 'ki-filled ki-people', permission: 'users.view' },
    { id: 'roles', label: 'Roles', icon: 'ki-filled ki-shield-tick', permission: 'roles.view' },
    { id: 'permissions', label: 'Permissions', icon: 'ki-filled ki-key', permission: 'roles.view' },
];

const page = usePage();

const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

const visibleTabs = computed(() => {
    return tabs.filter(tab => hasPermission(tab.permission));
});

const switchTab = (tabId) => {
    if (currentTab.value === tabId) return;
    currentTab.value = tabId;

    router.get(route('access-management.index'), { tab: tabId }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Watch for prop changes
watch(() => props.activeTab, (newTab) => {
    currentTab.value = newTab;
});
</script>

<template>
    <AppLayout title="Access Management">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                User & Access Management
            </h2>
        </template>

        <div class="container-fixed">
            <div class="py-5">
                <!-- Summary Stats Cards -->
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <i class="ki-filled ki-people text-primary text-2xl"></i>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-gray-900">{{ stats?.total_users || 0 }}</span>
                                    <p class="text-sm text-gray-500">Total Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-info/10 flex items-center justify-center">
                                    <i class="ki-filled ki-user-tick text-info text-2xl"></i>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-gray-900">{{ stats?.users_with_roles || 0 }}</span>
                                    <p class="text-sm text-gray-500">Users with Roles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
                                    <i class="ki-filled ki-shield-tick text-success text-2xl"></i>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-gray-900">{{ stats?.total_roles || 0 }}</span>
                                    <p class="text-sm text-gray-500">Total Roles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-warning/10 flex items-center justify-center">
                                    <i class="ki-filled ki-key text-warning text-2xl"></i>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-gray-900">{{ stats?.total_permissions || 0 }}</span>
                                    <p class="text-sm text-gray-500">Total Permissions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Card -->
                <div class="card">
                    <!-- Tabs Navigation -->
                    <div class="card-header border-b border-gray-200 px-5 py-0">
                        <div class="flex gap-1">
                            <button
                                v-for="tab in visibleTabs"
                                :key="tab.id"
                                @click="switchTab(tab.id)"
                                class="px-5 py-4 text-sm font-medium border-b-2 transition-colors -mb-px"
                                :class="currentTab === tab.id
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'"
                            >
                                <i :class="tab.icon" class="me-2"></i>
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="card-body p-0">
                        <!-- Users Tab Content -->
                        <UsersTab
                            v-if="currentTab === 'users'"
                            :users="users"
                            :roles="roles"
                            :pagination="pagination"
                            :filters="filters"
                        />

                        <!-- Roles Tab Content -->
                        <RolesTab
                            v-if="currentTab === 'roles'"
                            :roles="roles"
                            :permissions="permissions"
                            :permissions-grouped="permissionsGrouped"
                        />

                        <!-- Permissions Tab Content -->
                        <PermissionsTab
                            v-if="currentTab === 'permissions'"
                            :permissions="permissions"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
