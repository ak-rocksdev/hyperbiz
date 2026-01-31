<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onUnmounted } from 'vue';

// Props from controller
const props = defineProps({
    report: {
        type: Object,
        default: () => ({
            start_date: null,
            end_date: null,
            revenue: { accounts: [], total: 0 },
            cogs: { accounts: [], total: 0 },
            gross_profit: 0,
            expenses: { accounts: [], total: 0 },
            operating_income: 0,
            other_income: { accounts: [], total: 0 },
            other_expenses: { accounts: [], total: 0 },
            net_income: 0,
        })
    },
    comparison: {
        type: Object,
        default: null
    },
    fiscalYears: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: null,
            end_date: null,
            compare_mode: null
        })
    }
});

// Reactive filter state
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const compareMode = ref(props.filters?.compare_mode || null);
const activeQuickSelect = ref(null);
const isLoading = ref(false);
const showExportMenu = ref(false);

// Close export menu when clicking outside
const closeExportMenu = (event) => {
    if (!event.target.closest('.export-dropdown')) {
        showExportMenu.value = false;
    }
};

// Toggle export menu
const toggleExportMenu = () => {
    showExportMenu.value = !showExportMenu.value;
    if (showExportMenu.value) {
        setTimeout(() => {
            document.addEventListener('click', closeExportMenu);
        }, 0);
    } else {
        document.removeEventListener('click', closeExportMenu);
    }
};

// Cleanup event listener on unmount
onUnmounted(() => {
    document.removeEventListener('click', closeExportMenu);
});

// Compare mode options
const compareModeOptions = [
    { value: null, label: 'No Comparison' },
    { value: 'previous_period', label: 'Previous Period' },
    { value: 'previous_year', label: 'Previous Year' }
];

// Format currency helper (IDR)
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Format currency without symbol for table display
const formatAmount = (amount) => {
    const num = amount || 0;
    const formatted = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Math.abs(num));
    return num < 0 ? `(${formatted})` : formatted;
};

// Format percentage
const formatPercentage = (value) => {
    if (value === null || value === undefined || !isFinite(value)) return '-';
    const formatted = Math.abs(value).toFixed(1);
    return value >= 0 ? `+${formatted}%` : `-${formatted}%`;
};

// Calculate variance
const calculateVariance = (current, previous) => {
    if (!previous || previous === 0) return null;
    return ((current - previous) / Math.abs(previous)) * 100;
};

// Format date range for display
const formatDateRange = computed(() => {
    if (!props.report?.start_date || !props.report?.end_date) return '';
    const start = new Date(props.report.start_date);
    const end = new Date(props.report.end_date);
    const options = { day: 'numeric', month: 'short', year: 'numeric' };
    return `${start.toLocaleDateString('en-GB', options)} - ${end.toLocaleDateString('en-GB', options)}`;
});

// Check if comparison data exists
const hasComparison = computed(() => {
    return props.comparison !== null && props.comparison !== undefined;
});

// Quick date selection handlers
const setQuickDate = (period) => {
    activeQuickSelect.value = period;
    const today = new Date();
    let start, end;

    switch (period) {
        case 'this_month':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'this_quarter':
            const currentQuarter = Math.floor(today.getMonth() / 3);
            start = new Date(today.getFullYear(), currentQuarter * 3, 1);
            end = new Date(today.getFullYear(), (currentQuarter + 1) * 3, 0);
            break;
        case 'this_year':
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
            break;
        case 'custom':
            // Do not auto-set dates for custom
            return;
        default:
            return;
    }

    startDate.value = formatDateForInput(start);
    endDate.value = formatDateForInput(end);
};

// Format date for input (YYYY-MM-DD)
const formatDateForInput = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Watch for custom date changes
watch([startDate, endDate], () => {
    // If dates change manually, switch to custom
    if (activeQuickSelect.value !== 'custom') {
        activeQuickSelect.value = 'custom';
    }
});

// Generate report (apply filters)
const generateReport = () => {
    isLoading.value = true;
    const params = {
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
        compare_mode: compareMode.value || undefined,
    };

    router.get(window.location.pathname, params, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

// Print report
const printReport = () => {
    window.print();
};

// Export to PDF (placeholder - would integrate with actual export functionality)
const exportToPdf = () => {
    // In a real implementation, this would trigger a PDF export
    const params = new URLSearchParams({
        start_date: startDate.value || '',
        end_date: endDate.value || '',
        compare_mode: compareMode.value || '',
        export: 'pdf'
    });
    window.open(`${window.location.pathname}?${params.toString()}`, '_blank');
};

// Export to Excel (placeholder)
const exportToExcel = () => {
    const params = new URLSearchParams({
        start_date: startDate.value || '',
        end_date: endDate.value || '',
        compare_mode: compareMode.value || '',
        export: 'excel'
    });
    window.open(`${window.location.pathname}?${params.toString()}`, '_blank');
};

// Get color class for amount
const getAmountColorClass = (amount, isProfit = false) => {
    if (isProfit) {
        return amount >= 0 ? 'text-success' : 'text-danger';
    }
    return 'text-gray-900';
};

// Get variance color class
const getVarianceColorClass = (variance) => {
    if (variance === null || variance === undefined) return 'text-gray-400';
    return variance >= 0 ? 'text-success' : 'text-danger';
};

// Computed properties for summary cards
const revenueChange = computed(() => {
    if (!hasComparison.value) return null;
    return calculateVariance(props.report?.revenue?.total, props.comparison?.revenue?.total);
});

const grossProfitChange = computed(() => {
    if (!hasComparison.value) return null;
    return calculateVariance(props.report?.gross_profit, props.comparison?.gross_profit);
});

const netIncomeChange = computed(() => {
    if (!hasComparison.value) return null;
    return calculateVariance(props.report?.net_income, props.comparison?.net_income);
});

// Gross profit margin
const grossProfitMargin = computed(() => {
    const revenue = props.report?.revenue?.total || 0;
    if (revenue === 0) return 0;
    return ((props.report?.gross_profit || 0) / revenue) * 100;
});

// Net profit margin
const netProfitMargin = computed(() => {
    const revenue = props.report?.revenue?.total || 0;
    if (revenue === 0) return 0;
    return ((props.report?.net_income || 0) / revenue) * 100;
});
</script>

<template>
    <AppLayout title="Profit & Loss Statement">
        <!-- Container -->
        <div class="container-fixed py-5">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4 print:hidden">
                <Link href="/dashboard" class="hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Reports</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Profit & Loss</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Profit & Loss Statement</h1>
                    <p class="text-sm text-gray-500">
                        Income statement for period: {{ formatDateRange || 'Select date range' }}
                    </p>
                </div>
                <!-- Print/Export Buttons -->
                <div class="flex items-center gap-2 print:hidden">
                    <button @click="printReport" class="btn btn-light btn-sm">
                        <i class="ki-filled ki-printer me-2"></i>
                        Print
                    </button>
                    <!-- Export Dropdown -->
                    <div class="relative export-dropdown">
                        <button @click="toggleExportMenu" class="btn btn-light btn-sm">
                            <i class="ki-filled ki-file-down me-2"></i>
                            Export
                            <i class="ki-filled ki-down text-2xs ms-1"></i>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <div
                                v-show="showExportMenu"
                                class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1.5 z-50"
                            >
                                <button
                                    @click="exportToPdf(); showExportMenu = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    <i class="ki-filled ki-document text-danger"></i>
                                    Export PDF
                                </button>
                                <button
                                    @click="exportToExcel(); showExportMenu = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    <i class="ki-filled ki-notepad text-success"></i>
                                    Export Excel
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-5 print:hidden">
                <div class="card-body">
                    <div class="flex flex-col gap-4">
                        <!-- Quick Select Buttons -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 me-2">Quick Select:</span>
                            <button
                                @click="setQuickDate('this_month')"
                                class="btn btn-sm"
                                :class="activeQuickSelect === 'this_month' ? 'btn-primary' : 'btn-light'"
                            >
                                This Month
                            </button>
                            <button
                                @click="setQuickDate('last_month')"
                                class="btn btn-sm"
                                :class="activeQuickSelect === 'last_month' ? 'btn-primary' : 'btn-light'"
                            >
                                Last Month
                            </button>
                            <button
                                @click="setQuickDate('this_quarter')"
                                class="btn btn-sm"
                                :class="activeQuickSelect === 'this_quarter' ? 'btn-primary' : 'btn-light'"
                            >
                                This Quarter
                            </button>
                            <button
                                @click="setQuickDate('this_year')"
                                class="btn btn-sm"
                                :class="activeQuickSelect === 'this_year' ? 'btn-primary' : 'btn-light'"
                            >
                                This Year
                            </button>
                            <button
                                @click="activeQuickSelect = 'custom'"
                                class="btn btn-sm"
                                :class="activeQuickSelect === 'custom' ? 'btn-primary' : 'btn-light'"
                            >
                                Custom
                            </button>
                        </div>

                        <!-- Date Range and Compare Mode -->
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Start Date</label>
                                <DatePicker
                                    v-model="startDate"
                                    placeholder="Select start date"
                                    size="sm"
                                    class="w-[180px]"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">End Date</label>
                                <DatePicker
                                    v-model="endDate"
                                    placeholder="Select end date"
                                    size="sm"
                                    class="w-[180px]"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Compare With</label>
                                <SearchableSelect
                                    v-model="compareMode"
                                    :options="compareModeOptions"
                                    placeholder="No Comparison"
                                    :searchable="false"
                                    :clearable="true"
                                    size="sm"
                                    class="w-[180px]"
                                />
                            </div>
                            <button
                                @click="generateReport"
                                class="btn btn-primary btn-sm"
                                :disabled="isLoading"
                            >
                                <i class="ki-filled ki-chart-simple me-2" v-if="!isLoading"></i>
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Total Revenue Card -->
                <div class="card">
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-500 mb-1">Total Revenue</span>
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ formatCurrency(report?.revenue?.total) }}
                                </span>
                                <div v-if="hasComparison" class="flex items-center gap-1 mt-2">
                                    <span
                                        class="text-sm font-medium"
                                        :class="getVarianceColorClass(revenueChange)"
                                    >
                                        <i
                                            class="ki-filled text-xs me-1"
                                            :class="revenueChange >= 0 ? 'ki-arrow-up' : 'ki-arrow-down'"
                                        ></i>
                                        {{ formatPercentage(revenueChange) }}
                                    </span>
                                    <span class="text-xs text-gray-400">vs prev period</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-chart-line-up-2 text-primary text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gross Profit Card -->
                <div class="card">
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-500 mb-1">Gross Profit</span>
                                <span
                                    class="text-2xl font-bold"
                                    :class="getAmountColorClass(report?.gross_profit, true)"
                                >
                                    {{ formatCurrency(report?.gross_profit) }}
                                </span>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="badge badge-sm badge-outline badge-info">
                                        {{ grossProfitMargin.toFixed(1) }}% margin
                                    </span>
                                    <span
                                        v-if="hasComparison"
                                        class="text-sm font-medium"
                                        :class="getVarianceColorClass(grossProfitChange)"
                                    >
                                        {{ formatPercentage(grossProfitChange) }}
                                    </span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-graph-up text-success text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Income Card -->
                <div class="card">
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-500 mb-1">Net Income</span>
                                <span
                                    class="text-2xl font-bold"
                                    :class="getAmountColorClass(report?.net_income, true)"
                                >
                                    {{ formatCurrency(report?.net_income) }}
                                </span>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="badge badge-sm badge-outline badge-primary">
                                        {{ netProfitMargin.toFixed(1) }}% margin
                                    </span>
                                    <span
                                        v-if="hasComparison"
                                        class="text-sm font-medium"
                                        :class="getVarianceColorClass(netIncomeChange)"
                                    >
                                        {{ formatPercentage(netIncomeChange) }}
                                    </span>
                                </div>
                            </div>
                            <div
                                class="w-12 h-12 rounded-lg flex items-center justify-center"
                                :class="report?.net_income >= 0 ? 'bg-success/10' : 'bg-danger/10'"
                            >
                                <i
                                    class="ki-filled text-xl"
                                    :class="report?.net_income >= 0 ? 'ki-dollar text-success' : 'ki-minus-circle text-danger'"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Income Statement Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Income Statement</h3>
                    <span class="text-sm text-gray-500">{{ formatDateRange }}</span>
                </div>

                <div class="card-body p-0">
                    <div class="overflow-hidden">
                        <table class="table table-auto w-full">
                            <!-- Table Header -->
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-start min-w-[300px] px-5 py-3">Account</th>
                                    <th class="text-end w-[180px] px-5 py-3">
                                        Current Period
                                    </th>
                                    <th v-if="hasComparison" class="text-end w-[180px] px-5 py-3">
                                        Previous Period
                                    </th>
                                    <th v-if="hasComparison" class="text-end w-[120px] px-5 py-3">
                                        Variance
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- REVENUE SECTION -->
                                <tr class="bg-blue-50/50">
                                    <td colspan="4" class="px-5 py-3">
                                        <span class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
                                            Revenue
                                        </span>
                                    </td>
                                </tr>

                                <!-- Revenue Accounts -->
                                <tr
                                    v-for="account in report?.revenue?.accounts"
                                    :key="`rev-${account.account_id}`"
                                    class="hover:bg-slate-50 border-b border-gray-100"
                                >
                                    <td class="px-5 py-3 ps-10">
                                        <span class="text-gray-600 text-xs me-2">{{ account.account_code }}</span>
                                        <span class="text-gray-900">{{ account.account_name }}</span>
                                    </td>
                                    <td class="text-end px-5 py-3 font-medium text-gray-900">
                                        {{ formatAmount(account.balance) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 text-gray-600">
                                        {{ formatAmount(comparison?.revenue?.accounts?.find(a => a.account_id === account.account_id)?.balance) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span :class="getVarianceColorClass(calculateVariance(account.balance, comparison?.revenue?.accounts?.find(a => a.account_id === account.account_id)?.balance))">
                                            {{ formatPercentage(calculateVariance(account.balance, comparison?.revenue?.accounts?.find(a => a.account_id === account.account_id)?.balance)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Revenue Subtotal -->
                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                    <td class="px-5 py-3 font-semibold text-gray-900">
                                        Total Revenue
                                    </td>
                                    <td class="text-end px-5 py-3 font-bold text-gray-900">
                                        {{ formatAmount(report?.revenue?.total) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 font-semibold text-gray-600">
                                        {{ formatAmount(comparison?.revenue?.total) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span class="font-semibold" :class="getVarianceColorClass(revenueChange)">
                                            {{ formatPercentage(revenueChange) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- COST OF GOODS SOLD SECTION -->
                                <tr class="bg-orange-50/50">
                                    <td colspan="4" class="px-5 py-3">
                                        <span class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
                                            Cost of Goods Sold
                                        </span>
                                    </td>
                                </tr>

                                <!-- COGS Accounts -->
                                <tr
                                    v-for="account in report?.cogs?.accounts"
                                    :key="`cogs-${account.account_id}`"
                                    class="hover:bg-slate-50 border-b border-gray-100"
                                >
                                    <td class="px-5 py-3 ps-10">
                                        <span class="text-gray-600 text-xs me-2">{{ account.account_code }}</span>
                                        <span class="text-gray-900">{{ account.account_name }}</span>
                                    </td>
                                    <td class="text-end px-5 py-3 font-medium text-gray-900">
                                        ({{ formatAmount(Math.abs(account.balance)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 text-gray-600">
                                        ({{ formatAmount(Math.abs(comparison?.cogs?.accounts?.find(a => a.account_id === account.account_id)?.balance || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span :class="getVarianceColorClass(-calculateVariance(account.balance, comparison?.cogs?.accounts?.find(a => a.account_id === account.account_id)?.balance))">
                                            {{ formatPercentage(calculateVariance(account.balance, comparison?.cogs?.accounts?.find(a => a.account_id === account.account_id)?.balance)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- COGS Subtotal -->
                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                    <td class="px-5 py-3 font-semibold text-gray-900">
                                        Total Cost of Goods Sold
                                    </td>
                                    <td class="text-end px-5 py-3 font-bold text-gray-900">
                                        ({{ formatAmount(Math.abs(report?.cogs?.total || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 font-semibold text-gray-600">
                                        ({{ formatAmount(Math.abs(comparison?.cogs?.total || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span class="font-semibold" :class="getVarianceColorClass(-calculateVariance(report?.cogs?.total, comparison?.cogs?.total))">
                                            {{ formatPercentage(calculateVariance(report?.cogs?.total, comparison?.cogs?.total)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- GROSS PROFIT -->
                                <tr class="bg-green-50 border-b-2 border-green-200">
                                    <td class="px-5 py-4 font-bold text-gray-900 text-base">
                                        <i class="ki-filled ki-chart-simple me-2 text-success"></i>
                                        Gross Profit
                                    </td>
                                    <td class="text-end px-5 py-4">
                                        <span class="font-bold text-lg" :class="getAmountColorClass(report?.gross_profit, true)">
                                            {{ formatAmount(report?.gross_profit) }}
                                        </span>
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-4 font-bold text-gray-600">
                                        {{ formatAmount(comparison?.gross_profit) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-4">
                                        <span class="font-bold" :class="getVarianceColorClass(grossProfitChange)">
                                            {{ formatPercentage(grossProfitChange) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- OPERATING EXPENSES SECTION -->
                                <tr class="bg-red-50/50">
                                    <td colspan="4" class="px-5 py-3">
                                        <span class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
                                            Operating Expenses
                                        </span>
                                    </td>
                                </tr>

                                <!-- Expense Accounts -->
                                <tr
                                    v-for="account in report?.expenses?.accounts"
                                    :key="`exp-${account.account_id}`"
                                    class="hover:bg-slate-50 border-b border-gray-100"
                                >
                                    <td class="px-5 py-3 ps-10">
                                        <span class="text-gray-600 text-xs me-2">{{ account.account_code }}</span>
                                        <span class="text-gray-900">{{ account.account_name }}</span>
                                    </td>
                                    <td class="text-end px-5 py-3 font-medium text-gray-900">
                                        ({{ formatAmount(Math.abs(account.balance)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 text-gray-600">
                                        ({{ formatAmount(Math.abs(comparison?.expenses?.accounts?.find(a => a.account_id === account.account_id)?.balance || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span :class="getVarianceColorClass(-calculateVariance(account.balance, comparison?.expenses?.accounts?.find(a => a.account_id === account.account_id)?.balance))">
                                            {{ formatPercentage(calculateVariance(account.balance, comparison?.expenses?.accounts?.find(a => a.account_id === account.account_id)?.balance)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Expenses Subtotal -->
                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                    <td class="px-5 py-3 font-semibold text-gray-900">
                                        Total Operating Expenses
                                    </td>
                                    <td class="text-end px-5 py-3 font-bold text-gray-900">
                                        ({{ formatAmount(Math.abs(report?.expenses?.total || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 font-semibold text-gray-600">
                                        ({{ formatAmount(Math.abs(comparison?.expenses?.total || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span class="font-semibold" :class="getVarianceColorClass(-calculateVariance(report?.expenses?.total, comparison?.expenses?.total))">
                                            {{ formatPercentage(calculateVariance(report?.expenses?.total, comparison?.expenses?.total)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- OPERATING INCOME -->
                                <tr class="bg-blue-50 border-b-2 border-blue-200">
                                    <td class="px-5 py-4 font-bold text-gray-900 text-base">
                                        <i class="ki-filled ki-abstract-26 me-2 text-primary"></i>
                                        Operating Income
                                    </td>
                                    <td class="text-end px-5 py-4">
                                        <span class="font-bold text-lg" :class="getAmountColorClass(report?.operating_income, true)">
                                            {{ formatAmount(report?.operating_income) }}
                                        </span>
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-4 font-bold text-gray-600">
                                        {{ formatAmount(comparison?.operating_income) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-4">
                                        <span class="font-bold" :class="getVarianceColorClass(calculateVariance(report?.operating_income, comparison?.operating_income))">
                                            {{ formatPercentage(calculateVariance(report?.operating_income, comparison?.operating_income)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- OTHER INCOME SECTION -->
                                <tr v-if="report?.other_income?.accounts?.length > 0" class="bg-emerald-50/50">
                                    <td colspan="4" class="px-5 py-3">
                                        <span class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
                                            Other Income
                                        </span>
                                    </td>
                                </tr>

                                <!-- Other Income Accounts -->
                                <tr
                                    v-for="account in report?.other_income?.accounts"
                                    :key="`oi-${account.account_id}`"
                                    class="hover:bg-slate-50 border-b border-gray-100"
                                >
                                    <td class="px-5 py-3 ps-10">
                                        <span class="text-gray-600 text-xs me-2">{{ account.account_code }}</span>
                                        <span class="text-gray-900">{{ account.account_name }}</span>
                                    </td>
                                    <td class="text-end px-5 py-3 font-medium text-success">
                                        {{ formatAmount(account.balance) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 text-gray-600">
                                        {{ formatAmount(comparison?.other_income?.accounts?.find(a => a.account_id === account.account_id)?.balance) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span :class="getVarianceColorClass(calculateVariance(account.balance, comparison?.other_income?.accounts?.find(a => a.account_id === account.account_id)?.balance))">
                                            {{ formatPercentage(calculateVariance(account.balance, comparison?.other_income?.accounts?.find(a => a.account_id === account.account_id)?.balance)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Other Income Subtotal -->
                                <tr v-if="report?.other_income?.accounts?.length > 0" class="bg-gray-50 border-b border-gray-200">
                                    <td class="px-5 py-3 font-semibold text-gray-900">
                                        Total Other Income
                                    </td>
                                    <td class="text-end px-5 py-3 font-bold text-success">
                                        {{ formatAmount(report?.other_income?.total) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 font-semibold text-gray-600">
                                        {{ formatAmount(comparison?.other_income?.total) }}
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span class="font-semibold" :class="getVarianceColorClass(calculateVariance(report?.other_income?.total, comparison?.other_income?.total))">
                                            {{ formatPercentage(calculateVariance(report?.other_income?.total, comparison?.other_income?.total)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- OTHER EXPENSES SECTION -->
                                <tr v-if="report?.other_expenses?.accounts?.length > 0" class="bg-rose-50/50">
                                    <td colspan="4" class="px-5 py-3">
                                        <span class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
                                            Other Expenses
                                        </span>
                                    </td>
                                </tr>

                                <!-- Other Expenses Accounts -->
                                <tr
                                    v-for="account in report?.other_expenses?.accounts"
                                    :key="`oe-${account.account_id}`"
                                    class="hover:bg-slate-50 border-b border-gray-100"
                                >
                                    <td class="px-5 py-3 ps-10">
                                        <span class="text-gray-600 text-xs me-2">{{ account.account_code }}</span>
                                        <span class="text-gray-900">{{ account.account_name }}</span>
                                    </td>
                                    <td class="text-end px-5 py-3 font-medium text-danger">
                                        ({{ formatAmount(Math.abs(account.balance)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 text-gray-600">
                                        ({{ formatAmount(Math.abs(comparison?.other_expenses?.accounts?.find(a => a.account_id === account.account_id)?.balance || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span :class="getVarianceColorClass(-calculateVariance(account.balance, comparison?.other_expenses?.accounts?.find(a => a.account_id === account.account_id)?.balance))">
                                            {{ formatPercentage(calculateVariance(account.balance, comparison?.other_expenses?.accounts?.find(a => a.account_id === account.account_id)?.balance)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Other Expenses Subtotal -->
                                <tr v-if="report?.other_expenses?.accounts?.length > 0" class="bg-gray-50 border-b-2 border-gray-200">
                                    <td class="px-5 py-3 font-semibold text-gray-900">
                                        Total Other Expenses
                                    </td>
                                    <td class="text-end px-5 py-3 font-bold text-danger">
                                        ({{ formatAmount(Math.abs(report?.other_expenses?.total || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3 font-semibold text-gray-600">
                                        ({{ formatAmount(Math.abs(comparison?.other_expenses?.total || 0)) }})
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-3">
                                        <span class="font-semibold" :class="getVarianceColorClass(-calculateVariance(report?.other_expenses?.total, comparison?.other_expenses?.total))">
                                            {{ formatPercentage(calculateVariance(report?.other_expenses?.total, comparison?.other_expenses?.total)) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- NET INCOME (Final Row) -->
                                <tr class="bg-gradient-to-r from-primary/10 to-success/10 border-t-4 border-primary">
                                    <td class="px-5 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center">
                                                <i class="ki-filled ki-dollar text-white text-lg"></i>
                                            </div>
                                            <span class="font-bold text-gray-900 text-lg">Net Income</span>
                                        </div>
                                    </td>
                                    <td class="text-end px-5 py-5">
                                        <span
                                            class="font-bold text-2xl"
                                            :class="getAmountColorClass(report?.net_income, true)"
                                        >
                                            {{ formatCurrency(report?.net_income) }}
                                        </span>
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-5">
                                        <span class="font-bold text-lg text-gray-600">
                                            {{ formatCurrency(comparison?.net_income) }}
                                        </span>
                                    </td>
                                    <td v-if="hasComparison" class="text-end px-5 py-5">
                                        <div class="flex flex-col items-end">
                                            <span
                                                class="font-bold text-lg"
                                                :class="getVarianceColorClass(netIncomeChange)"
                                            >
                                                {{ formatPercentage(netIncomeChange) }}
                                            </span>
                                            <span
                                                class="text-xs"
                                                :class="getVarianceColorClass(netIncomeChange)"
                                            >
                                                {{ formatCurrency((report?.net_income || 0) - (comparison?.net_income || 0)) }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="!report?.revenue?.accounts?.length && !report?.expenses?.accounts?.length"
                        class="flex flex-col items-center justify-center py-16"
                    >
                        <i class="ki-filled ki-chart-pie-4 text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Data Available</h4>
                        <p class="text-sm text-gray-500 text-center max-w-md">
                            Select a date range and click "Generate Report" to view the Profit & Loss statement for the selected period.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Print Header (only visible when printing) -->
            <div class="hidden print:block mb-6">
                <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">Profit & Loss Statement</h1>
                    <p class="text-gray-600 mt-1">For the period {{ formatDateRange }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Print-specific styles */
@media print {
    /* Hide non-essential elements */
    .print\:hidden {
        display: none !important;
    }

    /* Show print-only elements */
    .print\:block {
        display: block !important;
    }

    /* Reset body styles for print */
    body {
        background: white !important;
        font-size: 11pt !important;
    }

    /* Card styles for print */
    .card {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
        break-inside: avoid;
    }

    .card-header {
        background: #f9fafb !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    /* Table styles for print */
    table {
        font-size: 10pt !important;
    }

    th, td {
        padding: 8px 12px !important;
    }

    /* Ensure colors print correctly */
    .text-success {
        color: #16a34a !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .text-danger {
        color: #dc2626 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .text-primary {
        color: #2563eb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Background colors for sections */
    .bg-blue-50\/50,
    .bg-orange-50\/50,
    .bg-red-50\/50,
    .bg-green-50,
    .bg-emerald-50\/50,
    .bg-rose-50\/50 {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Page breaks */
    .page-break-before {
        page-break-before: always;
    }

    .page-break-after {
        page-break-after: always;
    }

    /* Avoid breaking inside rows */
    tr {
        page-break-inside: avoid;
    }
}
</style>
