<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// Props from controller
const props = defineProps({
    uom: Object,
    derivedUoms: Array,
});

// Computed uom data
const uom = computed(() => props.uom);

// Format helpers
const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatNumber = (value, decimals = 4) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals
    }).format(value);
};

// Get UoM initials for avatar
const uomInitials = computed(() => {
    const code = uom.value?.code || '';
    if (code.length >= 2) {
        return code.substring(0, 2).toUpperCase();
    }
    return code.toUpperCase() || 'U';
});
</script>

<template>
    <AppLayout title="UoM Detail">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/uom/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ uom?.name }}
                </h2>
            </div>
        </template>

        <!-- Main Content Container -->
        <div class="container-fixed py-5">
            <!-- Action Bar - Always Visible -->
            <div class="card mb-5">
                <div class="card-body py-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <Link href="/uom/list" class="btn btn-icon btn-light btn-sm">
                                <i class="ki-filled ki-arrow-left"></i>
                            </Link>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ uom?.name }}</h2>
                                    <span v-if="uom?.is_base" class="badge badge-sm badge-primary">
                                        Base UoM
                                    </span>
                                    <span :class="uom?.is_active ? 'badge badge-sm badge-success' : 'badge badge-sm badge-danger'">
                                        {{ uom?.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="badge badge-sm badge-outline badge-secondary">
                                        {{ uom?.products_count || 0 }} products
                                    </span>
                                    <span class="badge badge-sm badge-outline badge-info">
                                        {{ uom?.product_uoms_count || 0 }} product UoMs
                                    </span>
                                    <span v-if="uom?.category_name" class="badge badge-sm badge-outline badge-primary">
                                        {{ uom.category_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Link :href="`/uom/edit/${uom?.id}`" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-pencil me-1"></i> Edit UoM
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Details (2/3 width) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- UoM Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-abstract-26 text-gray-500 me-2"></i>
                                UoM Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- UoM Header with Avatar -->
                            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl mb-5">
                                <div class="flex items-center justify-center w-20 h-20 rounded-xl bg-primary-light text-primary font-bold text-2xl shrink-0">
                                    {{ uomInitials }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ uom?.name }}</h4>
                                        <span v-if="uom?.is_base" class="badge badge-sm badge-primary">
                                            Base UoM
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500">Code: {{ uom?.code }}</p>
                                    <p v-if="uom?.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ uom.description }}</p>
                                </div>
                            </div>

                            <!-- UoM Details Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Code</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ uom?.code || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Name</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ uom?.name || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Category</span>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ uom?.category_name || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Base UoM</span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <span v-if="uom?.is_base" class="text-primary">This is a Base UoM</span>
                                        <Link v-else-if="uom?.base_uom_id" :href="`/uom/detail/${uom.base_uom_id}`" class="text-primary hover:underline">
                                            {{ uom.base_uom_name }} ({{ uom.base_uom_code }})
                                        </Link>
                                        <span v-else>-</span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Conversion Factor</span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ uom?.is_base ? '1 (Base)' : formatNumber(uom?.conversion_factor) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Status</span>
                                    <p>
                                        <span :class="uom?.is_active ? 'badge badge-sm badge-success' : 'badge badge-sm badge-danger'">
                                            {{ uom?.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div v-if="uom?.description" class="mt-5 pt-5 border-t border-gray-200 dark:border-coal-300">
                                <span class="text-sm text-gray-500">Description</span>
                                <p class="font-medium text-gray-900 dark:text-white mt-1">{{ uom.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Derived UoMs Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-element-11 text-gray-500 me-2"></i>
                                Derived UoMs
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <!-- Derived UoMs Table -->
                            <div v-if="derivedUoms && derivedUoms.length > 0" class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[100px]">Code</th>
                                            <th class="min-w-[180px]">Name</th>
                                            <th class="w-[150px] text-end">Conversion Factor</th>
                                            <th class="w-[100px] text-center">Status</th>
                                            <th class="w-[80px] text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="derived in derivedUoms" :key="derived.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                            <td>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ derived.code }}</span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 text-blue-700 font-bold text-sm shrink-0">
                                                        {{ derived.code?.substring(0, 2)?.toUpperCase() || 'U' }}
                                                    </div>
                                                    <Link
                                                        :href="`/uom/detail/${derived.id}`"
                                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary">
                                                        {{ derived.name }}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td class="text-end font-medium text-gray-900 dark:text-white">
                                                {{ formatNumber(derived.conversion_factor) }}
                                            </td>
                                            <td class="text-center">
                                                <span :class="derived.is_active ? 'badge badge-sm badge-success' : 'badge badge-sm badge-danger'">
                                                    {{ derived.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <Link
                                                    :href="`/uom/detail/${derived.id}`"
                                                    class="btn btn-sm btn-icon btn-light">
                                                    <i class="ki-filled ki-eye"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="flex flex-col items-center justify-center py-10">
                                <i class="ki-filled ki-abstract-26 text-6xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No derived UoMs found</p>
                                <p class="text-gray-400 text-xs mt-1">UoMs that use this as their base will appear here</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary (1/3 width) -->
                <div class="space-y-5">
                    <!-- Quick Stats Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-chart-simple text-gray-500 me-2"></i>
                                Quick Stats
                            </h3>
                        </div>
                        <div class="card-body space-y-4">
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-primary-light">
                                    <i class="ki-filled ki-package text-primary text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ uom?.products_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Products</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-info-light">
                                    <i class="ki-filled ki-abstract-26 text-info text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ uom?.product_uoms_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Product UoMs</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-success-light">
                                    <i class="ki-filled ki-element-11 text-success text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ derivedUoms?.length || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Derived UoMs</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-setting-2 text-gray-500 me-2"></i>
                                Actions
                            </h3>
                        </div>
                        <div class="card-body space-y-3">
                            <!-- Edit UoM -->
                            <Link :href="`/uom/edit/${uom?.id}`" class="btn btn-primary w-full">
                                <i class="ki-filled ki-pencil me-2"></i>
                                Edit UoM
                            </Link>

                            <!-- Back to List -->
                            <Link href="/uom/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to UoM List
                            </Link>
                        </div>
                    </div>

                    <!-- Record Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-information-2 text-gray-500 me-2"></i>
                                Record Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created At</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatDateTime(uom?.created_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Updated At</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatDateTime(uom?.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
