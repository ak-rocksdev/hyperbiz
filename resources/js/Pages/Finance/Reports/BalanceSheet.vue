<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';

// Props from controller
const props = defineProps({
    report: {
        type: Object,
        default: () => ({
            as_of_date: null,
            assets: { accounts: [], total: 0 },
            liabilities: { accounts: [], total: 0 },
            equity: { accounts: [], current_year_net_income: 0, total: 0 },
            totals: { total_liabilities_equity: 0, is_balanced: true },
        })
    },
    comparison: {
        type: Object,
        default: null
    },
    filters: {
        type: Object,
        default: () => ({
            as_of_date: null,
            compare_date: null
        })
    }
});

// Filter state
const asOfDate = ref(props.filters?.as_of_date || '');
const compareDate = ref(props.filters?.compare_date || '');

// Expanded groups state - track which account groups are expanded
const expandedGroups = reactive(new Set(['assets', 'liabilities', 'equity']));

// Track individual account category expansions
const expandedCategories = reactive(new Set());

// Initialize all categories as expanded by default
const initializeExpanded = () => {
    props.report?.assets?.accounts?.forEach((_, idx) => {
        expandedCategories.add(`assets-${idx}`);
    });
    props.report?.liabilities?.accounts?.forEach((_, idx) => {
        expandedCategories.add(`liabilities-${idx}`);
    });
    props.report?.equity?.accounts?.forEach((_, idx) => {
        expandedCategories.add(`equity-${idx}`);
    });
};
initializeExpanded();

// Format currency helper
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Format date for display
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

// Check if report has comparison data
const hasComparison = computed(() => {
    return props.comparison !== null && props.comparison !== undefined;
});

// Check if the balance sheet is balanced
const isBalanced = computed(() => {
    return props.report?.totals?.is_balanced ?? false;
});

// Toggle section expand/collapse
const toggleGroup = (group) => {
    if (expandedGroups.has(group)) {
        expandedGroups.delete(group);
    } else {
        expandedGroups.add(group);
    }
};

// Toggle category expand/collapse
const toggleCategory = (categoryKey) => {
    if (expandedCategories.has(categoryKey)) {
        expandedCategories.delete(categoryKey);
    } else {
        expandedCategories.add(categoryKey);
    }
};

// Check if group is expanded
const isGroupExpanded = (group) => {
    return expandedGroups.has(group);
};

// Check if category is expanded
const isCategoryExpanded = (categoryKey) => {
    return expandedCategories.has(categoryKey);
};

// Apply filters
const applyFilters = () => {
    const params = {};
    if (asOfDate.value) params.as_of_date = asOfDate.value;
    if (compareDate.value) params.compare_date = compareDate.value;

    router.get(window.location.pathname, params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Quick date selectors
const setEndOfLastMonth = () => {
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    asOfDate.value = lastMonth.toISOString().split('T')[0];
};

const setEndOfLastQuarter = () => {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentQuarter = Math.floor(currentMonth / 3);
    const lastQuarterEnd = currentQuarter === 0
        ? new Date(today.getFullYear() - 1, 11, 31)
        : new Date(today.getFullYear(), currentQuarter * 3, 0);
    asOfDate.value = lastQuarterEnd.toISOString().split('T')[0];
};

const setEndOfLastYear = () => {
    const today = new Date();
    const lastYearEnd = new Date(today.getFullYear() - 1, 11, 31);
    asOfDate.value = lastYearEnd.toISOString().split('T')[0];
};

// Clear comparison date
const clearComparison = () => {
    compareDate.value = '';
};

// Print report
const printReport = () => {
    window.print();
};

// Export to PDF (placeholder - implement based on backend)
const exportPdf = () => {
    const params = new URLSearchParams();
    if (asOfDate.value) params.append('as_of_date', asOfDate.value);
    if (compareDate.value) params.append('compare_date', compareDate.value);
    params.append('format', 'pdf');
    window.open(`${window.location.pathname}?${params.toString()}`, '_blank');
};

// Export to Excel (placeholder - implement based on backend)
const exportExcel = () => {
    const params = new URLSearchParams();
    if (asOfDate.value) params.append('as_of_date', asOfDate.value);
    if (compareDate.value) params.append('compare_date', compareDate.value);
    params.append('format', 'excel');
    window.open(`${window.location.pathname}?${params.toString()}`, '_blank');
};

// Expand all categories
const expandAll = () => {
    expandedGroups.add('assets');
    expandedGroups.add('liabilities');
    expandedGroups.add('equity');
    props.report?.assets?.accounts?.forEach((_, idx) => {
        expandedCategories.add(`assets-${idx}`);
    });
    props.report?.liabilities?.accounts?.forEach((_, idx) => {
        expandedCategories.add(`liabilities-${idx}`);
    });
    props.report?.equity?.accounts?.forEach((_, idx) => {
        expandedCategories.add(`equity-${idx}`);
    });
};

// Collapse all categories
const collapseAll = () => {
    expandedGroups.clear();
    expandedCategories.clear();
};
</script>

<template>
    <AppLayout title="Balance Sheet">
        <!-- Container -->
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <Link href="/dashboard" class="hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Reports</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Balance Sheet</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Balance Sheet</h1>
                    <p class="text-sm text-gray-500">
                        As of {{ formatDate(report?.as_of_date) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Expand/Collapse All -->
                    <button @click="expandAll" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-plus-squared me-1"></i>
                        Expand All
                    </button>
                    <button @click="collapseAll" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-minus-squared me-1"></i>
                        Collapse All
                    </button>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-5 print:hidden">
                <div class="card-body">
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- As of Date -->
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">As of Date</label>
                            <DatePicker
                                v-model="asOfDate"
                                placeholder="Select date"
                                size="sm"
                                class="w-[180px]"
                            />
                        </div>

                        <!-- Compare to Date -->
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Compare to Date (Optional)</label>
                            <div class="flex items-center gap-2">
                                <DatePicker
                                    v-model="compareDate"
                                    placeholder="Compare date"
                                    size="sm"
                                    class="w-[180px]"
                                />
                                <button
                                    v-if="compareDate"
                                    @click="clearComparison"
                                    class="btn btn-xs btn-icon btn-light"
                                    title="Clear comparison"
                                >
                                    <i class="ki-filled ki-cross"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Quick Select Buttons -->
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Quick Select</label>
                            <div class="flex items-center gap-1">
                                <button
                                    @click="setEndOfLastMonth"
                                    class="btn btn-xs btn-light"
                                >
                                    Last Month
                                </button>
                                <button
                                    @click="setEndOfLastQuarter"
                                    class="btn btn-xs btn-light"
                                >
                                    Last Quarter
                                </button>
                                <button
                                    @click="setEndOfLastYear"
                                    class="btn btn-xs btn-light"
                                >
                                    Last Year
                                </button>
                            </div>
                        </div>

                        <!-- Spacer -->
                        <div class="grow"></div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="applyFilters"
                                class="btn btn-primary btn-sm"
                            >
                                <i class="ki-filled ki-filter me-2"></i>
                                Generate Report
                            </button>
                            <button
                                @click="printReport"
                                class="btn btn-light btn-sm"
                            >
                                <i class="ki-filled ki-printer me-2"></i>
                                Print
                            </button>
                            <div class="menu" data-menu="true">
                                <div
                                    class="menu-item"
                                    data-menu-item-offset="0, 10px"
                                    data-menu-item-placement="bottom-end"
                                    data-menu-item-toggle="dropdown"
                                    data-menu-item-trigger="click"
                                >
                                    <button class="menu-toggle btn btn-light btn-sm">
                                        <i class="ki-filled ki-exit-down me-2"></i>
                                        Export
                                    </button>
                                    <div class="menu-dropdown menu-default w-[150px]" data-menu-dismiss="true">
                                        <div class="menu-item">
                                            <span class="menu-link cursor-pointer" @click="exportPdf">
                                                <span class="menu-icon">
                                                    <i class="ki-filled ki-document"></i>
                                                </span>
                                                <span class="menu-title">Export PDF</span>
                                            </span>
                                        </div>
                                        <div class="menu-item">
                                            <span class="menu-link cursor-pointer" @click="exportExcel">
                                                <span class="menu-icon">
                                                    <i class="ki-filled ki-file-sheet"></i>
                                                </span>
                                                <span class="menu-title">Export Excel</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Indicator -->
            <div
                class="mb-5 p-4 rounded-lg flex items-center gap-3"
                :class="isBalanced ? 'bg-success/10 border border-success/20' : 'bg-danger/10 border border-danger/20'"
            >
                <div
                    class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                    :class="isBalanced ? 'bg-success/20' : 'bg-danger/20'"
                >
                    <i
                        :class="isBalanced ? 'ki-filled ki-check-circle text-success' : 'ki-filled ki-information-2 text-danger'"
                        class="text-lg"
                    ></i>
                </div>
                <div>
                    <h4
                        class="text-sm font-semibold"
                        :class="isBalanced ? 'text-success' : 'text-danger'"
                    >
                        {{ isBalanced ? 'Balance Sheet is Balanced' : 'Balance Sheet is NOT Balanced' }}
                    </h4>
                    <p class="text-xs text-gray-600">
                        {{ isBalanced
                            ? 'Total Assets equals Total Liabilities + Equity'
                            : 'Total Assets does not equal Total Liabilities + Equity. Please review your entries.'
                        }}
                    </p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Total Assets -->
                <div class="card">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-bank text-primary text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Assets</p>
                                <h3 class="text-2xl font-bold text-primary">
                                    {{ formatCurrency(report?.assets?.total) }}
                                </h3>
                                <p v-if="hasComparison && comparison?.assets?.total" class="text-xs text-gray-500 mt-1">
                                    Compare: {{ formatCurrency(comparison.assets.total) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Liabilities -->
                <div class="card">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-bill text-danger text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Liabilities</p>
                                <h3 class="text-2xl font-bold text-danger">
                                    {{ formatCurrency(report?.liabilities?.total) }}
                                </h3>
                                <p v-if="hasComparison && comparison?.liabilities?.total" class="text-xs text-gray-500 mt-1">
                                    Compare: {{ formatCurrency(comparison.liabilities.total) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Equity -->
                <div class="card">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-chart-pie-simple text-success text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Equity</p>
                                <h3 class="text-2xl font-bold text-success">
                                    {{ formatCurrency(report?.equity?.total) }}
                                </h3>
                                <p v-if="hasComparison && comparison?.equity?.total" class="text-xs text-gray-500 mt-1">
                                    Compare: {{ formatCurrency(comparison.equity.total) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Sheet Report -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Balance Sheet Report</h3>
                    <span class="text-sm text-gray-500">As of {{ formatDate(report?.as_of_date) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="scrollable-x-auto">
                        <table class="table table-border w-full">
                            <thead>
                                <tr>
                                    <th class="min-w-[300px]">Account</th>
                                    <th class="w-[180px] text-end">
                                        {{ formatDate(report?.as_of_date) }}
                                    </th>
                                    <th v-if="hasComparison" class="w-[180px] text-end">
                                        {{ formatDate(comparison?.as_of_date) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- ==================== ASSETS SECTION ==================== -->
                                <tr class="bg-gray-100">
                                    <td colspan="3" class="py-3">
                                        <button
                                            @click="toggleGroup('assets')"
                                            class="flex items-center gap-2 font-bold text-gray-900 uppercase tracking-wide"
                                        >
                                            <i
                                                :class="isGroupExpanded('assets') ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"
                                                class="text-xs"
                                            ></i>
                                            ASSETS
                                        </button>
                                    </td>
                                </tr>

                                <template v-if="isGroupExpanded('assets')">
                                    <!-- Asset Categories -->
                                    <template v-for="(category, catIdx) in report?.assets?.accounts" :key="`assets-${catIdx}`">
                                        <!-- Category Header -->
                                        <tr class="bg-gray-50">
                                            <td class="py-2">
                                                <button
                                                    @click="toggleCategory(`assets-${catIdx}`)"
                                                    class="flex items-center gap-2 ps-4 font-semibold text-gray-800"
                                                >
                                                    <i
                                                        :class="isCategoryExpanded(`assets-${catIdx}`) ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"
                                                        class="text-xs text-gray-500"
                                                    ></i>
                                                    {{ category.name }}
                                                </button>
                                            </td>
                                            <td class="text-end py-2 font-medium text-gray-700">
                                                {{ formatCurrency(category.total) }}
                                            </td>
                                            <td v-if="hasComparison" class="text-end py-2 font-medium text-gray-700">
                                                {{ formatCurrency(comparison?.assets?.accounts?.[catIdx]?.total) }}
                                            </td>
                                        </tr>

                                        <!-- Category Accounts -->
                                        <template v-if="isCategoryExpanded(`assets-${catIdx}`) && category.accounts">
                                            <tr
                                                v-for="account in category.accounts"
                                                :key="account.id || account.code"
                                                class="hover:bg-slate-50"
                                            >
                                                <td class="ps-12 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-mono text-xs text-gray-500">{{ account.code }}</span>
                                                        <span class="text-gray-700">{{ account.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end py-2 text-gray-900">
                                                    {{ formatCurrency(account.balance) }}
                                                </td>
                                                <td v-if="hasComparison" class="text-end py-2 text-gray-900">
                                                    {{ formatCurrency(account.compare_balance) }}
                                                </td>
                                            </tr>
                                        </template>

                                        <!-- Category Subtotal -->
                                        <tr v-if="isCategoryExpanded(`assets-${catIdx}`)" class="border-t border-gray-200">
                                            <td class="ps-8 py-2 text-sm text-gray-600 italic">
                                                Total {{ category.name }}
                                            </td>
                                            <td class="text-end py-2 font-semibold text-gray-800 border-t border-gray-300">
                                                {{ formatCurrency(category.total) }}
                                            </td>
                                            <td v-if="hasComparison" class="text-end py-2 font-semibold text-gray-800 border-t border-gray-300">
                                                {{ formatCurrency(comparison?.assets?.accounts?.[catIdx]?.total) }}
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Total Assets Row -->
                                    <tr class="bg-primary/5 border-t-2 border-primary">
                                        <td class="py-3 ps-4 font-bold text-primary">
                                            TOTAL ASSETS
                                        </td>
                                        <td class="text-end py-3 font-bold text-primary text-lg">
                                            {{ formatCurrency(report?.assets?.total) }}
                                        </td>
                                        <td v-if="hasComparison" class="text-end py-3 font-bold text-primary text-lg">
                                            {{ formatCurrency(comparison?.assets?.total) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Spacer Row -->
                                <tr>
                                    <td colspan="3" class="py-2"></td>
                                </tr>

                                <!-- ==================== LIABILITIES SECTION ==================== -->
                                <tr class="bg-gray-100">
                                    <td colspan="3" class="py-3">
                                        <button
                                            @click="toggleGroup('liabilities')"
                                            class="flex items-center gap-2 font-bold text-gray-900 uppercase tracking-wide"
                                        >
                                            <i
                                                :class="isGroupExpanded('liabilities') ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"
                                                class="text-xs"
                                            ></i>
                                            LIABILITIES
                                        </button>
                                    </td>
                                </tr>

                                <template v-if="isGroupExpanded('liabilities')">
                                    <!-- Liability Categories -->
                                    <template v-for="(category, catIdx) in report?.liabilities?.accounts" :key="`liabilities-${catIdx}`">
                                        <!-- Category Header -->
                                        <tr class="bg-gray-50">
                                            <td class="py-2">
                                                <button
                                                    @click="toggleCategory(`liabilities-${catIdx}`)"
                                                    class="flex items-center gap-2 ps-4 font-semibold text-gray-800"
                                                >
                                                    <i
                                                        :class="isCategoryExpanded(`liabilities-${catIdx}`) ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"
                                                        class="text-xs text-gray-500"
                                                    ></i>
                                                    {{ category.name }}
                                                </button>
                                            </td>
                                            <td class="text-end py-2 font-medium text-gray-700">
                                                {{ formatCurrency(category.total) }}
                                            </td>
                                            <td v-if="hasComparison" class="text-end py-2 font-medium text-gray-700">
                                                {{ formatCurrency(comparison?.liabilities?.accounts?.[catIdx]?.total) }}
                                            </td>
                                        </tr>

                                        <!-- Category Accounts -->
                                        <template v-if="isCategoryExpanded(`liabilities-${catIdx}`) && category.accounts">
                                            <tr
                                                v-for="account in category.accounts"
                                                :key="account.id || account.code"
                                                class="hover:bg-slate-50"
                                            >
                                                <td class="ps-12 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-mono text-xs text-gray-500">{{ account.code }}</span>
                                                        <span class="text-gray-700">{{ account.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end py-2 text-gray-900">
                                                    {{ formatCurrency(account.balance) }}
                                                </td>
                                                <td v-if="hasComparison" class="text-end py-2 text-gray-900">
                                                    {{ formatCurrency(account.compare_balance) }}
                                                </td>
                                            </tr>
                                        </template>

                                        <!-- Category Subtotal -->
                                        <tr v-if="isCategoryExpanded(`liabilities-${catIdx}`)" class="border-t border-gray-200">
                                            <td class="ps-8 py-2 text-sm text-gray-600 italic">
                                                Total {{ category.name }}
                                            </td>
                                            <td class="text-end py-2 font-semibold text-gray-800 border-t border-gray-300">
                                                {{ formatCurrency(category.total) }}
                                            </td>
                                            <td v-if="hasComparison" class="text-end py-2 font-semibold text-gray-800 border-t border-gray-300">
                                                {{ formatCurrency(comparison?.liabilities?.accounts?.[catIdx]?.total) }}
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Total Liabilities Row -->
                                    <tr class="bg-danger/5 border-t-2 border-danger">
                                        <td class="py-3 ps-4 font-bold text-danger">
                                            TOTAL LIABILITIES
                                        </td>
                                        <td class="text-end py-3 font-bold text-danger text-lg">
                                            {{ formatCurrency(report?.liabilities?.total) }}
                                        </td>
                                        <td v-if="hasComparison" class="text-end py-3 font-bold text-danger text-lg">
                                            {{ formatCurrency(comparison?.liabilities?.total) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Spacer Row -->
                                <tr>
                                    <td colspan="3" class="py-2"></td>
                                </tr>

                                <!-- ==================== EQUITY SECTION ==================== -->
                                <tr class="bg-gray-100">
                                    <td colspan="3" class="py-3">
                                        <button
                                            @click="toggleGroup('equity')"
                                            class="flex items-center gap-2 font-bold text-gray-900 uppercase tracking-wide"
                                        >
                                            <i
                                                :class="isGroupExpanded('equity') ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"
                                                class="text-xs"
                                            ></i>
                                            EQUITY
                                        </button>
                                    </td>
                                </tr>

                                <template v-if="isGroupExpanded('equity')">
                                    <!-- Equity Categories -->
                                    <template v-for="(category, catIdx) in report?.equity?.accounts" :key="`equity-${catIdx}`">
                                        <!-- Category Header -->
                                        <tr class="bg-gray-50">
                                            <td class="py-2">
                                                <button
                                                    @click="toggleCategory(`equity-${catIdx}`)"
                                                    class="flex items-center gap-2 ps-4 font-semibold text-gray-800"
                                                >
                                                    <i
                                                        :class="isCategoryExpanded(`equity-${catIdx}`) ? 'ki-filled ki-minus' : 'ki-filled ki-plus'"
                                                        class="text-xs text-gray-500"
                                                    ></i>
                                                    {{ category.name }}
                                                </button>
                                            </td>
                                            <td class="text-end py-2 font-medium text-gray-700">
                                                {{ formatCurrency(category.total) }}
                                            </td>
                                            <td v-if="hasComparison" class="text-end py-2 font-medium text-gray-700">
                                                {{ formatCurrency(comparison?.equity?.accounts?.[catIdx]?.total) }}
                                            </td>
                                        </tr>

                                        <!-- Category Accounts -->
                                        <template v-if="isCategoryExpanded(`equity-${catIdx}`) && category.accounts">
                                            <tr
                                                v-for="account in category.accounts"
                                                :key="account.id || account.code"
                                                class="hover:bg-slate-50"
                                            >
                                                <td class="ps-12 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-mono text-xs text-gray-500">{{ account.code }}</span>
                                                        <span class="text-gray-700">{{ account.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end py-2 text-gray-900">
                                                    {{ formatCurrency(account.balance) }}
                                                </td>
                                                <td v-if="hasComparison" class="text-end py-2 text-gray-900">
                                                    {{ formatCurrency(account.compare_balance) }}
                                                </td>
                                            </tr>
                                        </template>

                                        <!-- Category Subtotal -->
                                        <tr v-if="isCategoryExpanded(`equity-${catIdx}`)" class="border-t border-gray-200">
                                            <td class="ps-8 py-2 text-sm text-gray-600 italic">
                                                Total {{ category.name }}
                                            </td>
                                            <td class="text-end py-2 font-semibold text-gray-800 border-t border-gray-300">
                                                {{ formatCurrency(category.total) }}
                                            </td>
                                            <td v-if="hasComparison" class="text-end py-2 font-semibold text-gray-800 border-t border-gray-300">
                                                {{ formatCurrency(comparison?.equity?.accounts?.[catIdx]?.total) }}
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Current Year Net Income (Special Line) -->
                                    <tr class="bg-amber-50 border-t border-amber-200">
                                        <td class="py-2 ps-4">
                                            <div class="flex items-center gap-2">
                                                <i class="ki-filled ki-chart-line text-amber-600"></i>
                                                <span class="font-medium text-amber-800">Current Year Net Income</span>
                                            </div>
                                        </td>
                                        <td class="text-end py-2 font-semibold text-amber-800">
                                            {{ formatCurrency(report?.equity?.current_year_net_income) }}
                                        </td>
                                        <td v-if="hasComparison" class="text-end py-2 font-semibold text-amber-800">
                                            {{ formatCurrency(comparison?.equity?.current_year_net_income) }}
                                        </td>
                                    </tr>

                                    <!-- Total Equity Row -->
                                    <tr class="bg-success/5 border-t-2 border-success">
                                        <td class="py-3 ps-4 font-bold text-success">
                                            TOTAL EQUITY
                                        </td>
                                        <td class="text-end py-3 font-bold text-success text-lg">
                                            {{ formatCurrency(report?.equity?.total) }}
                                        </td>
                                        <td v-if="hasComparison" class="text-end py-3 font-bold text-success text-lg">
                                            {{ formatCurrency(comparison?.equity?.total) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Spacer Row -->
                                <tr>
                                    <td colspan="3" class="py-2"></td>
                                </tr>

                                <!-- ==================== TOTAL LIABILITIES & EQUITY ==================== -->
                                <tr class="bg-gray-800 text-white">
                                    <td class="py-4 ps-4 font-bold text-lg">
                                        TOTAL LIABILITIES & EQUITY
                                    </td>
                                    <td class="text-end py-4 font-bold text-xl">
                                        {{ formatCurrency(report?.totals?.total_liabilities_equity) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end py-4 font-bold text-xl">
                                        {{ formatCurrency(comparison?.totals?.total_liabilities_equity) }}
                                    </td>
                                </tr>

                                <!-- Balance Check Row -->
                                <tr :class="isBalanced ? 'bg-success/10' : 'bg-danger/10'">
                                    <td class="py-3 ps-4">
                                        <div class="flex items-center gap-2">
                                            <i
                                                :class="isBalanced ? 'ki-filled ki-check-circle text-success' : 'ki-filled ki-information-2 text-danger'"
                                            ></i>
                                            <span
                                                class="font-medium"
                                                :class="isBalanced ? 'text-success' : 'text-danger'"
                                            >
                                                {{ isBalanced ? 'Balanced - Assets = Liabilities + Equity' : 'NOT Balanced - Please Review' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end py-3">
                                        <span
                                            class="font-semibold"
                                            :class="isBalanced ? 'text-success' : 'text-danger'"
                                        >
                                            {{ formatCurrency(report?.assets?.total) }}
                                        </span>
                                    </td>
                                    <td v-if="hasComparison" class="text-end py-3">
                                        <span
                                            class="font-semibold"
                                            :class="isBalanced ? 'text-success' : 'text-danger'"
                                        >
                                            {{ formatCurrency(comparison?.assets?.total) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Print-friendly styles */
@media print {
    .print\:hidden {
        display: none !important;
    }

    .card {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }

    .card-body {
        padding: 1rem !important;
    }

    table {
        font-size: 10pt !important;
    }

    th, td {
        padding: 0.5rem !important;
    }

    /* Ensure page breaks work correctly */
    tr {
        page-break-inside: avoid;
    }

    /* Keep section headers with their content */
    tr.bg-gray-100,
    tr.bg-gray-50 {
        page-break-after: avoid;
    }
}
</style>
