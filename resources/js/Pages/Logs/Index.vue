<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

const { props } = usePage();

const logs = ref(props.logs || []);
const pagination = ref(props.pagination || {});

const currentPage = ref(pagination.value.current_page || 1);
const perPageOptions = ref([10, 25, 50, 100]);
const selectedPerPage = ref(pagination.value.per_page || 10);

const searchQuery = ref('');

// Handle pagination and search
function formatJSON(jsonString) {
    try {
        return JSON.stringify(JSON.parse(jsonString), null, 2);
    } catch (e) {
        return jsonString; // Return raw string if not valid JSON
    }
}

// Fetch logs
const fetchLogs = () => {
    router.get(
        route('logs.index'),
        {
            ...(currentPage.value ? { page: currentPage.value } : {}),
            ...(searchQuery.value ? { search: searchQuery.value } : {}),
        },
        { preserveState: true, replace: true }
    );
};

// Watchers
watch([currentPage, selectedPerPage], fetchLogs);
</script>

<template>
    <AppLayout title="System Logs">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                System Logs
            </h2>
        </template>

        <div class="container-fixed">
            <div class="py-5">
                <!-- Logs Table -->
                <div class="grid gap-5 lg:gap-7.5">
                    <div class="card card-grid min-w-full">
                        <div class="card-header gap-5">
                            <h3 class="card-title">
                                System Logs
                            </h3>
                            <div class="card-toolbar">
                                <div class="flex gap-6">
                                    <div class="relative">
                                        <i class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                        <input
                                            type="text"
                                            class="input input-sm ps-8"
                                            placeholder="Search logs"
                                            v-model="searchQuery"
                                            @input="fetchLogs"
                                        />
                                        <button 
                                            v-if="searchQuery" 
                                            @click="clearSearch" 
                                            class="absolute right-2 flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200"
                                            style="right: 10px; top: 50%; transform: translateY(-50%);"
                                            aria-label="Clear Search">
                                            <i class="ki-filled ki-cross-circle text-gray-500"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="data_container">
                                <div class="scrollable-x-auto">
                                    <table class="table table-auto table-border" data-datatable-table="true">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="p-2">Date</th>
                                                <th class="p-2">User</th>
                                                <th class="p-2">Model</th>
                                                <th class="p-2 max-w-[200px]">Changes</th>
                                                <th class="p-2">Agent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="log in logs" :key="log.id">
                                                <td class="p-2 min-w-[200px]">
                                                    {{ log.created_at }}
                                                </td>
                                                <td class="p-2 min-w-[200px]">
                                                    {{ log.user?.name || 'System' }}
                                                </td>
                                                <td class="p-2">{{ log.model_type }}</td>
                                                <td class="p-2 max-w-[300px]">
                                                    <p class="bg-gray-100 p-2 rounded">{{ formatJSON(log.changed_fields) }}</p>
                                                </td>
                                                <!-- agent -->
                                                <td class="p-2">
                                                    <p class="bg-gray-100 p-2 rounded">{{ log.user_agent }}</p>
                                                </td>
                                            </tr>
                                            <tr v-if="logs.length === 0">
                                                <td colspan="5" class="p-4 text-center text-gray-500">
                                                    No logs available.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <Pagination :currentPage="currentPage" 
                                    :lastPage="pagination.last_page"
                                    :totalResults="pagination.total" 
                                    :perPageOptions="perPageOptions"
                                    :perPage="selectedPerPage" 
                                    @update:page="(page) => (currentPage = page)"
                                    @update:perPage="(perPage) => (selectedPerPage = perPage)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>