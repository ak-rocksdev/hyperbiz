<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Props from controller
const props = defineProps({
    report: {
        type: Object,
        default: () => ({
            as_of_date: null,
            period: null,
            accounts: [],
            totals: { debit: 0, credit: 0, is_balanced: true }
        })
    },
    fiscalPeriods: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({
            period_id: null,
            as_of_date: null
        })
    }
});

// Reactive filter state
const asOfDate = ref(props.filters?.as_of_date || '');
const selectedPeriodId = ref(props.filters?.period_id || '');

// Account type labels and order for grouping
const accountTypeConfig = {
    asset: { label: 'Assets', order: 1, icon: 'ki-filled ki-wallet' },
    liability: { label: 'Liabilities', order: 2, icon: 'ki-filled ki-bill' },
    equity: { label: 'Equity', order: 3, icon: 'ki-filled ki-chart-pie-3' },
    revenue: { label: 'Revenue', order: 4, icon: 'ki-filled ki-dollar' },
    cogs: { label: 'Cost of Goods Sold', order: 5, icon: 'ki-filled ki-basket' },
    expense: { label: 'Expenses', order: 6, icon: 'ki-filled ki-minus-circle' },
    other_income: { label: 'Other Income', order: 7, icon: 'ki-filled ki-plus-circle' },
    other_expense: { label: 'Other Expense', order: 8, icon: 'ki-filled ki-exit-left' }
};

// Fiscal period options for dropdown
const fiscalPeriodOptions = computed(() => {
    return [
        { value: '', label: 'Select Period...' },
        ...props.fiscalPeriods.map(period => ({
            value: period.id,
            label: period.name || `${period.start_date} - ${period.end_date}`,
            sublabel: period.status ? `Status: ${period.status}` : null
        }))
    ];
});

// Format currency helper (IDR)
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
    if (isNaN(date.getTime())) return dateStr;

    const options = { day: '2-digit', month: 'short', year: 'numeric' };
    return date.toLocaleDateString('en-GB', options);
};

// Group accounts by account_type with subtotals
const groupedAccounts = computed(() => {
    if (!props.report?.accounts || props.report.accounts.length === 0) {
        return [];
    }

    // Group accounts by type
    const groups = {};

    props.report.accounts.forEach(account => {
        const type = account.account_type || 'other';
        if (!groups[type]) {
            groups[type] = {
                type: type,
                label: accountTypeConfig[type]?.label || type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' '),
                icon: accountTypeConfig[type]?.icon || 'ki-filled ki-folder',
                order: accountTypeConfig[type]?.order || 99,
                accounts: [],
                subtotal_debit: 0,
                subtotal_credit: 0
            };
        }

        groups[type].accounts.push(account);
        groups[type].subtotal_debit += account.debit_balance || 0;
        groups[type].subtotal_credit += account.credit_balance || 0;
    });

    // Sort groups by order and return as array
    return Object.values(groups).sort((a, b) => a.order - b.order);
});

// Check if totals are balanced
const isBalanced = computed(() => {
    return props.report?.totals?.is_balanced ?? true;
});

// Get difference if unbalanced
const balanceDifference = computed(() => {
    if (!props.report?.totals) return 0;
    return Math.abs((props.report.totals.debit || 0) - (props.report.totals.credit || 0));
});

// Generate report with filters
const generateReport = () => {
    const params = {};

    // Use either period_id OR as_of_date, period takes priority
    if (selectedPeriodId.value) {
        params.period_id = selectedPeriodId.value;
        // Clear as_of_date when using period
    } else if (asOfDate.value) {
        params.as_of_date = asOfDate.value;
    }

    router.get('/finance/reports/trial-balance', params, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Clear period when date is selected and vice versa
const handleDateChange = () => {
    selectedPeriodId.value = '';
};

const handlePeriodChange = () => {
    asOfDate.value = '';
};

// Print report
const printReport = () => {
    window.print();
};

// Export to CSV
const exportToCsv = () => {
    if (!props.report?.accounts || props.report.accounts.length === 0) {
        return;
    }

    const headers = ['Account Code', 'Account Name', 'Account Type', 'Debit', 'Credit'];
    const rows = props.report.accounts.map(account => [
        account.account_code,
        account.account_name,
        account.account_type,
        account.debit_balance || 0,
        account.credit_balance || 0
    ]);

    // Add totals row
    rows.push(['', 'TOTAL', '', props.report.totals.debit, props.report.totals.credit]);

    const csvContent = [
        `Trial Balance Report - As of ${formatDate(props.report.as_of_date)}`,
        '',
        headers.join(','),
        ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `trial-balance-${props.report.as_of_date || 'report'}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <AppLayout title="Trial Balance">
        <!-- Container -->
        <div class="container-fixed py-5">
            <!-- Breadcrumb (hidden when printing) -->
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4 print:hidden">
                <Link href="/dashboard" class="hover:text-primary">
                    <i class="ki-filled ki-home text-sm"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Finance</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-500">Reports</span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Trial Balance</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Trial Balance</h1>
                    <p class="text-sm text-gray-500">
                        <span v-if="report?.as_of_date">As of {{ formatDate(report.as_of_date) }}</span>
                        <span v-else>Select a date or fiscal period to generate the report</span>
                    </p>
                </div>

                <!-- Balance Status Indicator -->
                <div v-if="report?.accounts?.length > 0" class="print:hidden">
                    <div
                        v-if="isBalanced"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-success/10 border border-success/20"
                    >
                        <i class="ki-filled ki-check-circle text-success text-xl"></i>
                        <div>
                            <span class="text-sm font-semibold text-success">Balanced</span>
                            <p class="text-xs text-gray-600">Debits equal Credits</p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-danger/10 border border-danger/20"
                    >
                        <i class="ki-filled ki-information-2 text-danger text-xl"></i>
                        <div>
                            <span class="text-sm font-semibold text-danger">Unbalanced</span>
                            <p class="text-xs text-gray-600">Difference: {{ formatCurrency(balanceDifference) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card (hidden when printing) -->
            <div class="card mb-5 print:hidden">
                <div class="card-body">
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- As of Date Filter -->
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">As of Date</label>
                            <DatePicker
                                v-model="asOfDate"
                                placeholder="Select date"
                                class="w-[180px]"
                                @change="handleDateChange"
                            />
                        </div>

                        <!-- OR Separator -->
                        <div class="flex items-center pb-2 text-sm text-gray-400 font-medium">
                            OR
                        </div>

                        <!-- Fiscal Period Filter -->
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Fiscal Period</label>
                            <SearchableSelect
                                v-model="selectedPeriodId"
                                :options="fiscalPeriodOptions"
                                placeholder="Select period"
                                class="w-[200px]"
                                @update:modelValue="handlePeriodChange"
                            />
                        </div>

                        <!-- Generate Button -->
                        <button
                            @click="generateReport"
                            class="btn btn-primary"
                        >
                            <i class="ki-filled ki-arrows-circle me-2"></i>
                            Generate Report
                        </button>

                        <!-- Spacer -->
                        <div class="flex-grow"></div>

                        <!-- Print Button -->
                        <button
                            v-if="report?.accounts?.length > 0"
                            @click="printReport"
                            class="btn btn-light"
                        >
                            <i class="ki-filled ki-printer me-2"></i>
                            Print
                        </button>

                        <!-- Export Button -->
                        <button
                            v-if="report?.accounts?.length > 0"
                            @click="exportToCsv"
                            class="btn btn-light"
                        >
                            <i class="ki-filled ki-exit-down me-2"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Print Header (only visible when printing) -->
            <div class="hidden print:block mb-6">
                <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">Trial Balance</h1>
                    <p class="text-sm text-gray-700 mt-1">
                        As of {{ formatDate(report?.as_of_date) }}
                    </p>
                    <p v-if="report?.period?.name" class="text-sm text-gray-600">
                        Period: {{ report.period.name }}
                    </p>
                </div>

                <!-- Balance Status for Print -->
                <div class="flex justify-end mb-4">
                    <div v-if="isBalanced" class="text-success font-semibold">
                        Status: BALANCED
                    </div>
                    <div v-else class="text-danger font-semibold">
                        Status: UNBALANCED (Difference: {{ formatCurrency(balanceDifference) }})
                    </div>
                </div>
            </div>

            <!-- Main Report Card -->
            <div class="card">
                <div class="card-header print:hidden">
                    <h3 class="card-title">Account Balances</h3>
                    <span v-if="report?.accounts?.length" class="text-sm text-gray-500">
                        {{ report.accounts.length }} accounts
                    </span>
                </div>

                <div class="card-body p-0">
                    <!-- Empty State -->
                    <div
                        v-if="!report?.accounts || report.accounts.length === 0"
                        class="flex flex-col items-center justify-center py-16 print:hidden"
                    >
                        <i class="ki-filled ki-chart text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Data Available</h4>
                        <p class="text-sm text-gray-500 mb-4">
                            Select a date or fiscal period and click "Generate Report" to view the trial balance.
                        </p>
                    </div>

                    <!-- Trial Balance Table -->
                    <div v-else class="scrollable-x-auto">
                        <table class="table table-auto table-border w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="w-[120px] text-left">Account Code</th>
                                    <th class="min-w-[250px] text-left">Account Name</th>
                                    <th class="w-[150px] text-end">Debit</th>
                                    <th class="w-[150px] text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Grouped Accounts -->
                                <template v-for="group in groupedAccounts" :key="group.type">
                                    <!-- Group Header -->
                                    <tr class="bg-gray-50">
                                        <td colspan="4" class="font-semibold text-gray-800 py-3">
                                            <div class="flex items-center gap-2">
                                                <i :class="group.icon" class="text-gray-600"></i>
                                                <span>{{ group.label }}</span>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Account Rows -->
                                    <tr
                                        v-for="account in group.accounts"
                                        :key="account.account_id"
                                        class="hover:bg-slate-50"
                                        :class="{
                                            'bg-danger/5': account.debit_balance > 0 && account.credit_balance > 0
                                        }"
                                    >
                                        <td class="font-mono text-sm text-gray-700 pl-8">
                                            {{ account.account_code }}
                                        </td>
                                        <td class="text-gray-900">
                                            {{ account.account_name }}
                                        </td>
                                        <td class="text-end font-medium">
                                            <span v-if="account.debit_balance > 0" class="text-gray-900">
                                                {{ formatCurrency(account.debit_balance) }}
                                            </span>
                                            <span v-else class="text-gray-300">-</span>
                                        </td>
                                        <td class="text-end font-medium">
                                            <span v-if="account.credit_balance > 0" class="text-gray-900">
                                                {{ formatCurrency(account.credit_balance) }}
                                            </span>
                                            <span v-else class="text-gray-300">-</span>
                                        </td>
                                    </tr>

                                    <!-- Group Subtotal Row -->
                                    <tr class="bg-gray-50/50 border-b-2 border-gray-200">
                                        <td class="pl-8 text-sm text-gray-600 italic">Subtotal</td>
                                        <td class="text-sm text-gray-600 italic">{{ group.label }}</td>
                                        <td class="text-end font-semibold text-gray-700">
                                            {{ formatCurrency(group.subtotal_debit) }}
                                        </td>
                                        <td class="text-end font-semibold text-gray-700">
                                            {{ formatCurrency(group.subtotal_credit) }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>

                            <!-- Table Footer with Totals -->
                            <tfoot>
                                <tr
                                    class="bg-gray-100 font-bold text-lg"
                                    :class="{
                                        'bg-success/10': isBalanced,
                                        'bg-danger/10': !isBalanced
                                    }"
                                >
                                    <td colspan="2" class="text-gray-900 py-4">
                                        <div class="flex items-center gap-2">
                                            <span>TOTAL</span>
                                            <i
                                                v-if="isBalanced"
                                                class="ki-filled ki-check-circle text-success"
                                                title="Balanced"
                                            ></i>
                                            <i
                                                v-else
                                                class="ki-filled ki-information-2 text-danger"
                                                title="Unbalanced"
                                            ></i>
                                        </div>
                                    </td>
                                    <td
                                        class="text-end py-4"
                                        :class="isBalanced ? 'text-success' : 'text-danger'"
                                    >
                                        {{ formatCurrency(report.totals?.debit) }}
                                    </td>
                                    <td
                                        class="text-end py-4"
                                        :class="isBalanced ? 'text-success' : 'text-danger'"
                                    >
                                        {{ formatCurrency(report.totals?.credit) }}
                                    </td>
                                </tr>

                                <!-- Difference Row (only shown when unbalanced) -->
                                <tr v-if="!isBalanced" class="bg-danger/5">
                                    <td colspan="2" class="text-danger font-semibold">
                                        <div class="flex items-center gap-2">
                                            <i class="ki-filled ki-information-2"></i>
                                            <span>Difference (Out of Balance)</span>
                                        </div>
                                    </td>
                                    <td colspan="2" class="text-end text-danger font-bold">
                                        {{ formatCurrency(balanceDifference) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Card Footer with Summary (hidden when printing) -->
                <div
                    v-if="report?.accounts?.length > 0"
                    class="card-footer print:hidden"
                >
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="text-sm text-gray-500">
                            Report generated as of {{ formatDate(report.as_of_date) }}
                            <span v-if="report.period?.name">
                                | Period: {{ report.period.name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-sm">
                                <span class="text-gray-500">Total Accounts:</span>
                                <span class="font-semibold text-gray-900 ml-1">{{ report.accounts.length }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="text-gray-500">Account Types:</span>
                                <span class="font-semibold text-gray-900 ml-1">{{ groupedAccounts.length }}</span>
                            </div>
                        </div>
                    </div>
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

    /* Adjust page layout */
    .container-fixed {
        max-width: 100% !important;
        padding: 0 !important;
    }

    /* Card styling for print */
    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .card-body {
        padding: 0 !important;
    }

    /* Table adjustments */
    table {
        font-size: 10pt !important;
    }

    th, td {
        padding: 4px 8px !important;
    }

    /* Ensure colors print */
    .text-success {
        color: #10b981 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .text-danger {
        color: #ef4444 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .bg-gray-50,
    .bg-gray-100 {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .bg-success\/10 {
        background-color: rgba(16, 185, 129, 0.1) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .bg-danger\/10 {
        background-color: rgba(239, 68, 68, 0.1) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Page break control */
    thead {
        display: table-header-group;
    }

    tr {
        page-break-inside: avoid;
    }
}
</style>