<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 5,
        total: 0,
    },
    logs: {
        type: Array,
        required: true,
    },
});



const currentPage       = ref(props.pagination.current_page || 1);
const perPageOptions    = ref([5, 10, 25, 50, 100]);
const lastPage          = ref(props.pagination.last_page || 1);
const totalResults      = ref(props.pagination.total || 0);
const perPage           = ref(props.pagination.per_page || 5);
const logs              = ref(props.logs || []);

console.log('Props:', props.pagination.per_page);

const searchQuery = ref('');

// Handle pagination and search
function formatJSON(jsonString) {
    try {
        return JSON.stringify(JSON.parse(jsonString), null, 2);
    } catch (e) {
        return jsonString; // Return raw string if not valid JSON
    }
}

const clearSearch = () => {
    searchQuery.value = '';
    fetchLogs();
};

const goToPage = (page) => {
    if (typeof page === 'number' && page !== currentPage.value) {
        currentPage.value = page;
    }
};

const changePerPage = (event) => {
    const newPerPage = Number(event.target.value);
    if (newPerPage !== perPage.value) {
        perPage.value = newPerPage;
        currentPage.value = 1; // Reset to first page when perPage changes
    }
};

// Fetch logs
const fetchLogs = () => {
    const queryParams = {
        per_page: perPage.value,
        ...(searchQuery.value ? { search: searchQuery.value } : { page: currentPage.value }),
    };

    router.get(route('logs.index'), queryParams, {
        preserveState: true,
        replace: true
    });
};

watch([currentPage, perPage], () => {
    fetchLogs();
});

watch(
    () => props.pagination,
    (newPagination) => {
        currentPage.value = newPagination.current_page || 1;
        lastPage.value = newPagination.last_page || 1;
        totalResults.value = newPagination.total || 0;
        perPage.value = newPagination.per_page || 5;
    }, {
        immediate: true
    }
);

watch(
    () => props.logs,
    (newLogs) => {
        logs.value = newLogs || [];
    }, {
        immediate: true
    }
);

const visiblePages = computed(() => {
    const maxVisible = 5;
    const pages = [];

    if (lastPage.value <= maxVisible) {
        for (let i = 1; i <= lastPage.value; i++) {
            pages.push(i);
        }
    } else {
        if (currentPage.value <= 3) {
            pages.push(1, 2, 3, 4, '...', lastPage.value);
        } else if (currentPage.value > lastPage.value - 3) {
            pages.push(
                1,
                '...',
                lastPage.value - 3,
                lastPage.value - 2,
                lastPage.value - 1,
                lastPage.value
            );
        } else {
            pages.push(
                1,
                '...',
                currentPage.value - 1,
                currentPage.value,
                currentPage.value + 1,
                '...',
                lastPage.value
            );
        }
    }

    return pages;
});

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
                                                <th class="p-2 max-w-[200px]">Changes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="log in logs" :key="log.id">
                                                <td class="p-2 min-w-[200px]">
                                                    <div class="flex flex-col">
                                                        <span class="mb-1">{{ log.created_at }}</span>
                                                        <span>{{ log.user?.name || 'System' }}</span>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="flex flex-col">
                                                        <span class="p-2 rounded mb-3">
                                                            <div class="badge badge-secondary mb-2 me-3">Changes</div><span>{{ log.model_type }}</span>
                                                            <div>{{ formatJSON(log.changed_fields) }}</div>
                                                        </span>
                                                        <span class=" p-2 rounded">
                                                            <div class="badge badge-secondary mb-2">Agent :</div>
                                                            <div>{{ log.user_agent }}</div>
                                                        </span>
                                                    </div>
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
                                <div class="card-footer flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium w-full justify-between items-center">
                                    <div class="flex justify-between items-center gap-2">
                                        Show
                                        <select class="select select-sm w-16" :value="perPage" @change="changePerPage">
                                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        per page
                                    </div>
                                    <span v-if="totalResults > 0" class="text-slate-500 text-sm ms-4">
                                        Total: {{ totalResults }} Result{{ totalResults === 1 ? '' : 's' }}
                                    </span>
                                    <div class="pagination flex items-center gap-2">
                                        <!-- Previous button -->
                                        <button class="btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                                            <i class="ki-outline ki-black-left"></i>
                                        </button>

                                        <!-- Page buttons -->
                                        <span v-for="(page, index) in visiblePages"
                                            :key="index"
                                            class="btn"
                                            :class="{ 'active': page === currentPage }"
                                            @click="goToPage(page)">
                                            {{ page }}
                                        </span>

                                        <!-- Next button -->
                                        <button class="btn" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
                                            <i class="ki-outline ki-black-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>