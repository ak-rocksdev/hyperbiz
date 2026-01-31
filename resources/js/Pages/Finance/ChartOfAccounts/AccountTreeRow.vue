<script setup>
import { computed } from 'vue';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
    level: {
        type: Number,
        default: 0,
    },
    expandedNodes: {
        type: Object, // Set
        required: true,
    },
    accountTypeColors: {
        type: Object,
        default: () => ({}),
    },
    accountTypes: {
        type: Object,
        default: () => ({}),
    },
    hasPermission: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['toggle-expand', 'edit', 'toggle-status', 'delete']);

// Check if this node is expanded
const isExpanded = computed(() => {
    return props.expandedNodes.has(props.account.id);
});

// Check if account has children
const hasChildren = computed(() => {
    return props.account.children && props.account.children.length > 0;
});

// Get badge color class for account type
const getTypeColor = (type) => {
    const colors = props.accountTypeColors || {};
    return colors[type] || 'secondary';
};

// Calculate indentation padding
const indentStyle = computed(() => {
    return { paddingLeft: `${props.level * 24}px` };
});

// Handle expand/collapse toggle
const handleToggle = () => {
    emit('toggle-expand', props.account.id);
};

// Forward events to parent
const handleEdit = () => {
    emit('edit', props.account);
};

const handleToggleStatus = () => {
    emit('toggle-status', props.account);
};

const handleDelete = () => {
    emit('delete', props.account);
};
</script>

<template>
    <!-- Current Account Row -->
    <tr class="hover:bg-slate-50 dark:hover:bg-coal-600">
        <td>
            <div class="flex items-center gap-2" :style="indentStyle">
                <!-- Expand/Collapse Toggle -->
                <button
                    v-if="hasChildren"
                    @click="handleToggle"
                    class="btn btn-xs btn-icon btn-light shrink-0"
                >
                    <i :class="isExpanded ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"></i>
                </button>
                <span v-else class="w-[26px] shrink-0"></span>

                <!-- Account Icon -->
                <div
                    class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0"
                    :class="account.is_header ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600'"
                >
                    <i :class="account.is_header ? 'ki-filled ki-folder' : 'ki-filled ki-file'"></i>
                </div>

                <!-- Account Info -->
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-sm font-semibold text-gray-900">
                            {{ account.account_code }}
                        </span>
                        <span class="text-sm text-gray-700">
                            {{ account.account_name }}
                        </span>
                        <span
                            v-if="account.is_system"
                            class="badge badge-xs badge-outline badge-secondary"
                        >
                            System
                        </span>
                        <span
                            v-if="account.is_bank_account"
                            class="badge badge-xs badge-outline badge-info"
                        >
                            Bank
                        </span>
                    </div>
                </div>
            </div>
        </td>
        <td>
            <span
                class="badge badge-sm"
                :class="`badge-${getTypeColor(account.account_type)}`"
            >
                {{ account.account_type_label || accountTypes?.[account.account_type] || account.account_type }}
            </span>
        </td>
        <td class="text-center">
            <span class="text-sm text-gray-600 capitalize">
                {{ account.normal_balance }}
            </span>
        </td>
        <td class="text-center">
            <span
                v-if="account.is_active"
                class="badge badge-sm badge-outline badge-success"
            >
                Active
            </span>
            <span
                v-else
                class="badge badge-sm badge-outline badge-danger"
            >
                Inactive
            </span>
        </td>
        <td class="text-center">
            <div class="menu flex-inline justify-center" data-menu="true">
                <div
                    class="menu-item"
                    data-menu-item-offset="0, 10px"
                    data-menu-item-placement="bottom-end"
                    data-menu-item-toggle="dropdown"
                    data-menu-item-trigger="click|lg:click"
                >
                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                        <i class="ki-filled ki-dots-vertical"></i>
                    </button>
                    <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                        <!-- Edit Action -->
                        <div v-if="hasPermission('chart-of-accounts.edit')" class="menu-item">
                            <span
                                class="menu-link cursor-pointer"
                                @click="handleEdit"
                                data-modal-toggle="#modal_account_form"
                            >
                                <span class="menu-icon">
                                    <i class="ki-filled ki-pencil"></i>
                                </span>
                                <span class="menu-title">Edit</span>
                            </span>
                        </div>

                        <!-- Toggle Status Action -->
                        <div
                            v-if="hasPermission('chart-of-accounts.edit') && !account.is_system"
                            class="menu-item"
                        >
                            <span class="menu-link cursor-pointer" @click="handleToggleStatus">
                                <span class="menu-icon">
                                    <i
                                        :class="account.is_active
                                            ? 'ki-filled ki-cross-circle'
                                            : 'ki-filled ki-check-circle'"
                                    ></i>
                                </span>
                                <span class="menu-title">
                                    {{ account.is_active ? 'Deactivate' : 'Activate' }}
                                </span>
                            </span>
                        </div>

                        <!-- Separator -->
                        <div
                            v-if="hasPermission('chart-of-accounts.delete') && !account.is_system"
                            class="menu-separator"
                        ></div>

                        <!-- Delete Action -->
                        <div
                            v-if="hasPermission('chart-of-accounts.delete') && !account.is_system"
                            class="menu-item"
                        >
                            <span class="menu-link cursor-pointer" @click="handleDelete">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-trash"></i>
                                </span>
                                <span class="menu-title text-danger">Delete</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>

    <!-- Recursive Children Rows -->
    <template v-if="hasChildren && isExpanded">
        <AccountTreeRow
            v-for="child in account.children"
            :key="child.id"
            :account="child"
            :level="level + 1"
            :expanded-nodes="expandedNodes"
            :account-type-colors="accountTypeColors"
            :account-types="accountTypes"
            :has-permission="hasPermission"
            @toggle-expand="(id) => emit('toggle-expand', id)"
            @edit="(acc) => emit('edit', acc)"
            @toggle-status="(acc) => emit('toggle-status', acc)"
            @delete="(acc) => emit('delete', acc)"
        />
    </template>
</template>
