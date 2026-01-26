<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
    },
    logs: {
        type: Array,
        required: true,
    },
});

const currentPage = ref(Number(props.pagination.current_page) || 1);
const perPageOptions = [10, 25, 50, 100];
const lastPage = ref(Number(props.pagination.last_page) || 1);
const totalResults = ref(Number(props.pagination.total) || 0);
const perPage = ref(Number(props.pagination.per_page) || 10);
const logs = ref(props.logs || []);
const searchQuery = ref('');
const expandedLogs = ref({});

const toggleExpand = (logId) => {
    expandedLogs.value[logId] = !expandedLogs.value[logId];
};

// Get action display info
const getActionInfo = (action) => {
    const actions = {
        'created': { label: 'CREATE', class: 'bg-emerald-500', textClass: 'text-emerald-600', bgLight: 'bg-emerald-50' },
        'updated': { label: 'UPDATE', class: 'bg-amber-500', textClass: 'text-amber-600', bgLight: 'bg-amber-50' },
        'deleted': { label: 'DELETE', class: 'bg-red-500', textClass: 'text-red-600', bgLight: 'bg-red-50' },
    };
    return actions[action?.toLowerCase()] || { label: action || 'ACTION', class: 'bg-gray-500', textClass: 'text-gray-600', bgLight: 'bg-gray-50' };
};

// Get model name from full namespace
const getModelName = (modelType) => {
    if (!modelType) return 'Unknown';
    const parts = modelType.split('\\');
    return parts[parts.length - 1];
};

// Format field name for display
const formatFieldName = (field) => {
    return field
        .replace(/^mst_/i, '')
        .replace(/_id$/i, '')
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ')
        .trim();
};

// Format value for display
const formatValue = (value) => {
    if (value === null || value === undefined) return '—';
    if (value === '') return '(empty)';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value, null, 2);

    // Truncate long values
    const str = String(value);
    if (str.length > 100) return str.substring(0, 100) + '...';
    return str;
};

// Parse changed_fields which might be string or object
const parseChangedFields = (changedFields) => {
    if (!changedFields) return null;

    // If it's a string, try to parse it
    if (typeof changedFields === 'string') {
        try {
            return JSON.parse(changedFields);
        } catch (e) {
            console.error('Failed to parse changed_fields:', e);
            return null;
        }
    }

    return changedFields;
};

// Get changed fields for comparison
const getChangedFields = (log) => {
    const changedFields = parseChangedFields(log.changed_fields);
    if (!changedFields || typeof changedFields !== 'object') return [];

    const fields = [];
    const action = log.action?.toLowerCase();

    // Fields to exclude from display
    const excludeFields = ['created_at', 'updated_at', 'created_by', 'updated_by', 'remember_token', 'password', 'two_factor_secret', 'two_factor_recovery_codes'];

    // Check the data structure
    // Structure 1 (from LogsSystemChanges trait): { "field_name": { "original": "...", "updated": "..." } }
    // Structure 2: { "old": {...}, "new": {...} }
    // Structure 3: { "attributes": {...}, "original": {...} }

    const firstKey = Object.keys(changedFields)[0];
    const firstValue = changedFields[firstKey];

    if (firstValue && typeof firstValue === 'object' && ('original' in firstValue || 'updated' in firstValue)) {
        // Structure 1: { "field_name": { "original": "...", "updated": "..." } }
        Object.keys(changedFields).forEach(fieldName => {
            if (excludeFields.includes(fieldName)) return;

            const change = changedFields[fieldName];
            const oldVal = change?.original;
            const newVal = change?.updated;

            fields.push({
                field: fieldName,
                displayName: formatFieldName(fieldName),
                oldValue: oldVal,
                newValue: newVal,
                oldDisplay: formatValue(oldVal),
                newDisplay: formatValue(newVal),
                isChanged: JSON.stringify(oldVal) !== JSON.stringify(newVal),
            });
        });
    } else if ('old' in changedFields || 'new' in changedFields) {
        // Structure 2: { "old": {...}, "new": {...} }
        const oldData = changedFields.old || {};
        const newData = changedFields.new || {};
        const allKeys = new Set([...Object.keys(oldData), ...Object.keys(newData)]);

        allKeys.forEach(key => {
            if (excludeFields.includes(key)) return;

            const oldVal = oldData[key];
            const newVal = newData[key];

            if (action === 'updated' && JSON.stringify(oldVal) === JSON.stringify(newVal)) return;

            fields.push({
                field: key,
                displayName: formatFieldName(key),
                oldValue: oldVal,
                newValue: newVal,
                oldDisplay: formatValue(oldVal),
                newDisplay: formatValue(newVal),
                isChanged: JSON.stringify(oldVal) !== JSON.stringify(newVal),
            });
        });
    } else if ('attributes' in changedFields || 'original' in changedFields) {
        // Structure 3: { "attributes": {...}, "original": {...} }
        const oldData = changedFields.original || {};
        const newData = changedFields.attributes || {};
        const allKeys = new Set([...Object.keys(oldData), ...Object.keys(newData)]);

        allKeys.forEach(key => {
            if (excludeFields.includes(key)) return;

            const oldVal = oldData[key];
            const newVal = newData[key];

            if (action === 'updated' && JSON.stringify(oldVal) === JSON.stringify(newVal)) return;

            fields.push({
                field: key,
                displayName: formatFieldName(key),
                oldValue: oldVal,
                newValue: newVal,
                oldDisplay: formatValue(oldVal),
                newDisplay: formatValue(newVal),
                isChanged: JSON.stringify(oldVal) !== JSON.stringify(newVal),
            });
        });
    }

    return fields;
};

// Format JSON for display
const formatJSON = (data) => {
    if (!data) return '—';
    try {
        const parsed = parseChangedFields(data);
        return JSON.stringify(parsed, null, 2);
    } catch (e) {
        return String(data);
    }
};

// Toggle raw JSON view
const showRawJson = ref({});

// Parse user agent to get browser/device info
const parseUserAgent = (ua) => {
    if (!ua) return { browser: 'Unknown', os: 'Unknown' };

    let browser = 'Unknown';
    let os = 'Unknown';

    // Detect browser
    if (ua.includes('Chrome') && !ua.includes('Edg')) browser = 'Chrome';
    else if (ua.includes('Firefox')) browser = 'Firefox';
    else if (ua.includes('Safari') && !ua.includes('Chrome')) browser = 'Safari';
    else if (ua.includes('Edg')) browser = 'Edge';
    else if (ua.includes('Opera') || ua.includes('OPR')) browser = 'Opera';

    // Detect OS
    if (ua.includes('Windows')) os = 'Windows';
    else if (ua.includes('Mac OS')) os = 'macOS';
    else if (ua.includes('Linux')) os = 'Linux';
    else if (ua.includes('Android')) os = 'Android';
    else if (ua.includes('iOS') || ua.includes('iPhone')) os = 'iOS';

    return { browser, os };
};

const clearSearch = () => {
    searchQuery.value = '';
    fetchLogs();
};

const goToPage = (page) => {
    if (typeof page === 'number' && page !== currentPage.value) {
        currentPage.value = page;
    }
};

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

let skipNextFetch = false;

watch(currentPage, () => {
    if (skipNextFetch) {
        skipNextFetch = false;
        return;
    }
    fetchLogs();
});

watch(perPage, () => {
    if (currentPage.value !== 1) {
        skipNextFetch = true; // Prevent double fetch when resetting page
        currentPage.value = 1;
    }
    fetchLogs();
});

watch(
    () => props.pagination,
    (newPagination) => {
        currentPage.value = Number(newPagination.current_page) || 1;
        lastPage.value = Number(newPagination.last_page) || 1;
        totalResults.value = Number(newPagination.total) || 0;
        perPage.value = Number(newPagination.per_page) || 10;
    },
    { immediate: true }
);

watch(
    () => props.logs,
    (newLogs) => {
        logs.value = newLogs || [];
    },
    { immediate: true }
);

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
</script>

<template>
    <AppLayout title="Audit Logs">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Audit Logs
            </h2>
        </template>

        <div class="container-fixed">
            <div class="py-5">
                <div class="grid gap-5 lg:gap-7.5">
                    <!-- Header Card -->
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">System Audit Trail</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Complete history of all data changes for compliance and security auditing
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right hidden md:block">
                                        <div class="text-2xl font-bold text-gray-900">{{ totalResults }}</div>
                                        <div class="text-xs text-gray-500">Total Records</div>
                                    </div>
                                    <div class="relative">
                                        <i class="ki-filled ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 start-0 -translate-y-1/2 ms-3"></i>
                                        <input
                                            type="text"
                                            class="input input-sm ps-8 w-[220px]"
                                            placeholder="Search by user, model..."
                                            v-model="searchQuery"
                                            @input="fetchLogs"
                                        />
                                        <button
                                            v-if="searchQuery"
                                            @click="clearSearch"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="ki-filled ki-cross text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs Table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <!-- Empty State -->
                            <div v-if="logs.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                                <i class="ki-filled ki-shield-search text-6xl mb-4"></i>
                                <p class="text-lg font-medium">No audit logs found</p>
                                <p class="text-sm">System activities will be recorded here</p>
                            </div>

                            <!-- Audit Log Table -->
                            <div v-else class="overflow-x-auto">
                                <table class="table-auto w-full">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[180px]">Timestamp</th>
                                            <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[100px]">Action</th>
                                            <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase tracking-wider">Entity</th>
                                            <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[150px]">User</th>
                                            <th class="text-left p-4 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[130px]">IP Address</th>
                                            <th class="text-center p-4 font-semibold text-gray-600 text-xs uppercase tracking-wider w-[80px]">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template v-for="log in logs" :key="log.id">
                                            <!-- Main Row -->
                                            <tr
                                                class="hover:bg-gray-50 cursor-pointer transition-colors"
                                                :class="{ 'bg-blue-50/50': expandedLogs[log.id] }"
                                                @click="toggleExpand(log.id)"
                                            >
                                                <!-- Timestamp -->
                                                <td class="p-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ log.created_at }}</div>
                                                    <div class="text-xs text-gray-400">{{ log.human_readable_time }}</div>
                                                </td>

                                                <!-- Action -->
                                                <td class="p-4">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold text-white"
                                                        :class="getActionInfo(log.action).class"
                                                    >
                                                        {{ getActionInfo(log.action).label }}
                                                    </span>
                                                </td>

                                                <!-- Entity -->
                                                <td class="p-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ getModelName(log.model_type) }}</div>
                                                    <div class="text-xs text-gray-400">ID: {{ log.model_id || '—' }}</div>
                                                </td>

                                                <!-- User -->
                                                <td class="p-4">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                                                            {{ log.user?.name?.charAt(0)?.toUpperCase() || 'S' }}
                                                        </div>
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">{{ log.user?.name || 'System' }}</div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- IP Address -->
                                                <td class="p-4">
                                                    <div class="flex items-center gap-1.5">
                                                        <i class="ki-filled ki-geolocation text-gray-400 text-sm"></i>
                                                        <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded font-mono text-gray-700">
                                                            {{ log.ip_address || '—' }}
                                                        </code>
                                                    </div>
                                                </td>

                                                <!-- Expand Button -->
                                                <td class="p-4 text-center">
                                                    <button
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 transition-colors"
                                                        :class="{ 'bg-blue-100': expandedLogs[log.id] }"
                                                    >
                                                        <i
                                                            class="ki-filled transition-transform text-gray-500"
                                                            :class="expandedLogs[log.id] ? 'ki-minus' : 'ki-plus'"
                                                        ></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Expanded Details Row -->
                                            <tr v-if="expandedLogs[log.id]">
                                                <td colspan="6" class="p-0 bg-slate-50">
                                                    <div class="p-5 border-l-4" :class="getActionInfo(log.action).class.replace('bg-', 'border-')">
                                                        <!-- Changes Section -->
                                                        <div class="mb-5">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <h4 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                                                    <i class="ki-filled ki-document text-gray-400"></i>
                                                                    Data Changes
                                                                    <span class="text-xs font-normal text-gray-400">({{ getChangedFields(log).length }} fields)</span>
                                                                </h4>
                                                                <button
                                                                    @click.stop="showRawJson[log.id] = !showRawJson[log.id]"
                                                                    class="text-xs px-2 py-1 rounded border transition-colors"
                                                                    :class="showRawJson[log.id] ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-gray-100 border-gray-300 text-gray-600 hover:bg-gray-200'"
                                                                >
                                                                    <i class="ki-filled ki-code me-1"></i>
                                                                    {{ showRawJson[log.id] ? 'Hide JSON' : 'View JSON' }}
                                                                </button>
                                                            </div>

                                                            <!-- Raw JSON View -->
                                                            <div v-if="showRawJson[log.id]" class="mb-4">
                                                                <div class="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                                                    <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap">{{ formatJSON(log.changed_fields) }}</pre>
                                                                </div>
                                                            </div>

                                                            <!-- Before / After Comparison Table -->
                                                            <div v-if="getChangedFields(log).length > 0" class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                                                <table class="w-full text-sm">
                                                                    <thead>
                                                                        <tr class="bg-gray-100">
                                                                            <th class="text-left p-3 font-semibold text-gray-600 w-[20%] border-r border-gray-200">Field</th>
                                                                            <th class="text-left p-3 font-semibold text-gray-600 w-[40%] border-r border-gray-200">
                                                                                <span class="flex items-center gap-2">
                                                                                    <span class="w-3 h-3 rounded bg-red-100 border border-red-300 flex items-center justify-center">
                                                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                                                    </span>
                                                                                    Before (Old Value)
                                                                                </span>
                                                                            </th>
                                                                            <th class="text-left p-3 font-semibold text-gray-600 w-[40%]">
                                                                                <span class="flex items-center gap-2">
                                                                                    <span class="w-3 h-3 rounded bg-green-100 border border-green-300 flex items-center justify-center">
                                                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                                                    </span>
                                                                                    After (New Value)
                                                                                </span>
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100">
                                                                        <tr v-for="(change, idx) in getChangedFields(log)" :key="idx" class="hover:bg-gray-50">
                                                                            <td class="p-3 font-medium text-gray-700 bg-gray-50 border-r border-gray-200 align-top">
                                                                                <div class="flex flex-col">
                                                                                    <span>{{ change.displayName }}</span>
                                                                                    <code class="text-[10px] text-gray-400 font-mono">{{ change.field }}</code>
                                                                                </div>
                                                                            </td>
                                                                            <td class="p-3 border-r border-gray-200 align-top">
                                                                                <div
                                                                                    v-if="change.oldValue !== null && change.oldValue !== undefined"
                                                                                    class="bg-red-50 border border-red-200 rounded px-3 py-2 text-red-800 text-xs font-mono break-all"
                                                                                >
                                                                                    <template v-if="typeof change.oldValue === 'object'">
                                                                                        <pre class="whitespace-pre-wrap">{{ JSON.stringify(change.oldValue, null, 2) }}</pre>
                                                                                    </template>
                                                                                    <template v-else>{{ change.oldDisplay }}</template>
                                                                                </div>
                                                                                <div v-else class="text-gray-400 text-xs italic flex items-center gap-1">
                                                                                    <i class="ki-filled ki-minus-circle text-gray-300"></i>
                                                                                    No previous value
                                                                                </div>
                                                                            </td>
                                                                            <td class="p-3 align-top">
                                                                                <div
                                                                                    v-if="change.newValue !== null && change.newValue !== undefined"
                                                                                    class="bg-green-50 border border-green-200 rounded px-3 py-2 text-green-800 text-xs font-mono break-all"
                                                                                >
                                                                                    <template v-if="typeof change.newValue === 'object'">
                                                                                        <pre class="whitespace-pre-wrap">{{ JSON.stringify(change.newValue, null, 2) }}</pre>
                                                                                    </template>
                                                                                    <template v-else>{{ change.newDisplay }}</template>
                                                                                </div>
                                                                                <div v-else class="text-gray-400 text-xs italic flex items-center gap-1">
                                                                                    <i class="ki-filled ki-minus-circle text-gray-300"></i>
                                                                                    Value removed
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <!-- No Changes Message -->
                                                            <div v-else class="bg-white rounded-lg border border-gray-200 p-4 text-center text-gray-500">
                                                                <i class="ki-filled ki-information-2 text-2xl text-gray-300 mb-2"></i>
                                                                <p class="text-sm">No field changes recorded for this entry</p>
                                                                <button
                                                                    @click.stop="showRawJson[log.id] = true"
                                                                    class="text-xs text-blue-600 hover:underline mt-1"
                                                                >
                                                                    View raw data
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- Session Info Section -->
                                                        <div>
                                                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                                                                <i class="ki-filled ki-shield-tick text-gray-400"></i>
                                                                Session Information
                                                            </h4>
                                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                                                <!-- IP Address Card -->
                                                                <div class="bg-white rounded-lg border border-gray-200 p-3">
                                                                    <div class="text-xs text-gray-500 mb-1">IP Address</div>
                                                                    <div class="flex items-center gap-2">
                                                                        <i class="ki-filled ki-geolocation text-blue-500"></i>
                                                                        <code class="text-sm font-mono font-semibold text-gray-900">{{ log.ip_address || '—' }}</code>
                                                                    </div>
                                                                </div>

                                                                <!-- Browser Card -->
                                                                <div class="bg-white rounded-lg border border-gray-200 p-3">
                                                                    <div class="text-xs text-gray-500 mb-1">Browser</div>
                                                                    <div class="flex items-center gap-2">
                                                                        <i class="ki-filled ki-chrome text-blue-500"></i>
                                                                        <span class="text-sm font-medium text-gray-900">
                                                                            {{ parseUserAgent(log.user_agent).browser }}
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <!-- OS Card -->
                                                                <div class="bg-white rounded-lg border border-gray-200 p-3">
                                                                    <div class="text-xs text-gray-500 mb-1">Operating System</div>
                                                                    <div class="flex items-center gap-2">
                                                                        <i class="ki-filled ki-laptop text-blue-500"></i>
                                                                        <span class="text-sm font-medium text-gray-900">
                                                                            {{ parseUserAgent(log.user_agent).os }}
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <!-- User Card -->
                                                                <div class="bg-white rounded-lg border border-gray-200 p-3">
                                                                    <div class="text-xs text-gray-500 mb-1">Performed By</div>
                                                                    <div class="flex items-center gap-2">
                                                                        <i class="ki-filled ki-user text-blue-500"></i>
                                                                        <span class="text-sm font-medium text-gray-900">{{ log.user?.name || 'System' }}</span>
                                                                    </div>
                                                                    <div v-if="log.user?.email" class="text-xs text-gray-400 mt-1 ml-5">{{ log.user.email }}</div>
                                                                </div>
                                                            </div>

                                                            <!-- Full User Agent -->
                                                            <div class="mt-3 bg-white rounded-lg border border-gray-200 p-3">
                                                                <div class="text-xs text-gray-500 mb-1">Full User Agent String</div>
                                                                <code class="text-xs text-gray-600 break-all block font-mono bg-gray-50 p-2 rounded">{{ log.user_agent || '—' }}</code>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div v-if="logs.length > 0" class="flex flex-col md:flex-row gap-4 text-gray-600 text-sm font-medium justify-between items-center p-4 border-t border-gray-200 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500">Show</span>
                                    <select class="select select-sm w-20" v-model.number="perPage">
                                        <option v-for="option in perPageOptions" :key="option" :value="option">
                                            {{ option }}
                                        </option>
                                    </select>
                                    <span class="text-gray-500">entries</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 text-sm">
                                        Page {{ currentPage }} of {{ lastPage }}
                                    </span>
                                </div>

                                <div class="pagination flex items-center gap-1">
                                    <button
                                        class="btn btn-sm btn-icon btn-light"
                                        :disabled="currentPage <= 1"
                                        @click="goToPage(currentPage - 1)"
                                    >
                                        <i class="ki-outline ki-black-left"></i>
                                    </button>

                                    <template v-for="(page, index) in visiblePages" :key="index">
                                        <span v-if="page === '...'" class="px-2 text-gray-400">...</span>
                                        <button
                                            v-else
                                            class="btn btn-sm btn-icon"
                                            :class="page === currentPage ? 'btn-primary' : 'btn-light'"
                                            :style="page === currentPage ? { color: 'white' } : {}"
                                            @click="goToPage(page)"
                                        >
                                            {{ page }}
                                        </button>
                                    </template>

                                    <button
                                        class="btn btn-sm btn-icon btn-light"
                                        :disabled="currentPage >= lastPage"
                                        @click="goToPage(currentPage + 1)"
                                    >
                                        <i class="ki-outline ki-black-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
