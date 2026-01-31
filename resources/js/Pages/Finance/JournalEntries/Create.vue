<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    accounts: {
        type: Array,
        default: () => []
        // Each account: { value, label, account_code, account_name, account_type, normal_balance }
    },
    fiscalPeriods: {
        type: Array,
        default: () => []
        // Each period: { value, label, start_date, end_date }
    },
    currentPeriodId: {
        type: [Number, String, null],
        default: null
    },
    nextNumber: {
        type: String,
        default: ''
    }
});

// Form state
const form = ref({
    entry_number: props.nextNumber,
    entry_date: new Date().toISOString().split('T')[0],
    fiscal_period_id: props.currentPeriodId,
    currency_code: 'IDR',
    exchange_rate: 1,
    memo: '',
});

// Journal lines - start with 2 empty rows
const lines = ref([
    { id: generateLineId(), account_id: null, description: '', debit_amount: '', credit_amount: '' },
    { id: generateLineId(), account_id: null, description: '', debit_amount: '', credit_amount: '' },
]);

const errors = ref({});
const isSubmitting = ref(false);

// Generate unique ID for line items
function generateLineId() {
    return Date.now() + Math.random().toString(36).substr(2, 9);
}

// Account options formatted for SearchableSelect with code - name display
const accountOptions = computed(() => {
    return props.accounts.map(acc => ({
        value: acc.value,
        label: `${acc.account_code} - ${acc.account_name}`,
        sublabel: acc.account_type,
    }));
});

// Fiscal period options for SearchableSelect
const fiscalPeriodOptions = computed(() => {
    return props.fiscalPeriods.map(period => ({
        value: period.value,
        label: period.label,
        sublabel: `${period.start_date} - ${period.end_date}`,
        start_date: period.start_date,
        end_date: period.end_date,
    }));
});

// Get selected fiscal period for date validation
const selectedFiscalPeriod = computed(() => {
    if (!form.value.fiscal_period_id) return null;
    return props.fiscalPeriods.find(p => p.value === form.value.fiscal_period_id);
});

// Calculate totals
const totalDebit = computed(() => {
    return lines.value.reduce((sum, line) => {
        const amount = parseFloat(line.debit_amount) || 0;
        return sum + amount;
    }, 0);
});

const totalCredit = computed(() => {
    return lines.value.reduce((sum, line) => {
        const amount = parseFloat(line.credit_amount) || 0;
        return sum + amount;
    }, 0);
});

const difference = computed(() => {
    return Math.abs(totalDebit.value - totalCredit.value);
});

const isBalanced = computed(() => {
    return Math.abs(totalDebit.value - totalCredit.value) < 0.01;
});

// Format currency for display
const formatCurrency = (value) => {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.value.currency_code || 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(num);
};

// Format number for display in inputs
const formatNumber = (value) => {
    if (!value && value !== 0) return '';
    const num = parseFloat(value);
    if (isNaN(num)) return '';
    return num.toLocaleString('id-ID');
};

// Parse formatted number input
const parseNumber = (value) => {
    if (!value) return '';
    // Remove thousand separators and convert comma to period for decimals
    const cleaned = value.toString().replace(/\./g, '').replace(',', '.');
    const num = parseFloat(cleaned);
    return isNaN(num) ? '' : num;
};

// Handle debit input change - if debit has value, clear credit
const handleDebitInput = (index, event) => {
    const value = parseNumber(event.target.value);
    lines.value[index].debit_amount = value;
    if (value && value > 0) {
        lines.value[index].credit_amount = '';
    }
};

// Handle credit input change - if credit has value, clear debit
const handleCreditInput = (index, event) => {
    const value = parseNumber(event.target.value);
    lines.value[index].credit_amount = value;
    if (value && value > 0) {
        lines.value[index].debit_amount = '';
    }
};

// Add new line
const addLine = () => {
    lines.value.push({
        id: generateLineId(),
        account_id: null,
        description: '',
        debit_amount: '',
        credit_amount: '',
    });
};

// Remove line (keep minimum 2 rows)
const removeLine = (index) => {
    if (lines.value.length > 2) {
        lines.value.splice(index, 1);
    } else {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            icon: 'warning',
            title: 'Minimum 2 lines required',
        });
    }
};

// Validate entry date is within fiscal period
const isDateWithinPeriod = (date) => {
    if (!selectedFiscalPeriod.value || !date) return true;
    const entryDate = new Date(date);
    const startDate = new Date(selectedFiscalPeriod.value.start_date);
    const endDate = new Date(selectedFiscalPeriod.value.end_date);
    return entryDate >= startDate && entryDate <= endDate;
};

// Watch fiscal period change to validate date
watch(() => form.value.fiscal_period_id, () => {
    if (form.value.entry_date && selectedFiscalPeriod.value) {
        if (!isDateWithinPeriod(form.value.entry_date)) {
            // Reset date to start of selected period
            form.value.entry_date = selectedFiscalPeriod.value.start_date;
        }
    }
});

// Validate form
const validateForm = () => {
    errors.value = {};

    // Entry date required
    if (!form.value.entry_date) {
        errors.value.entry_date = ['Entry date is required'];
    } else if (!isDateWithinPeriod(form.value.entry_date)) {
        errors.value.entry_date = ['Entry date must be within the selected fiscal period'];
    }

    // Fiscal period required
    if (!form.value.fiscal_period_id) {
        errors.value.fiscal_period_id = ['Fiscal period is required'];
    }

    // Validate lines
    const validLines = lines.value.filter(line => {
        const hasAccount = line.account_id !== null;
        const hasAmount = (parseFloat(line.debit_amount) || 0) > 0 || (parseFloat(line.credit_amount) || 0) > 0;
        return hasAccount && hasAmount;
    });

    if (validLines.length < 2) {
        errors.value.lines = ['At least 2 lines with accounts and amounts are required'];
    }

    // Check each line has account if it has amount
    lines.value.forEach((line, index) => {
        const hasAmount = (parseFloat(line.debit_amount) || 0) > 0 || (parseFloat(line.credit_amount) || 0) > 0;
        if (hasAmount && !line.account_id) {
            errors.value[`lines.${index}.account_id`] = ['Account is required'];
        }
    });

    // Check balance
    if (!isBalanced.value) {
        errors.value.balance = ['Total debit must equal total credit'];
    }

    // Check memo length
    if (form.value.memo && form.value.memo.length > 255) {
        errors.value.memo = ['Memo cannot exceed 255 characters'];
    }

    return Object.keys(errors.value).length === 0;
};

// Submit form
const submitForm = async () => {
    if (!validateForm()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fix the errors in the form before submitting.',
        });
        return;
    }

    isSubmitting.value = true;

    try {
        // Prepare payload - filter out empty lines
        const validLines = lines.value
            .filter(line => {
                const hasAccount = line.account_id !== null;
                const hasAmount = (parseFloat(line.debit_amount) || 0) > 0 || (parseFloat(line.credit_amount) || 0) > 0;
                return hasAccount && hasAmount;
            })
            .map(line => ({
                account_id: line.account_id,
                description: line.description || '',
                debit_amount: parseFloat(line.debit_amount) || 0,
                credit_amount: parseFloat(line.credit_amount) || 0,
            }));

        const payload = {
            entry_date: form.value.entry_date,
            fiscal_period_id: form.value.fiscal_period_id,
            memo: form.value.memo || '',
            currency_code: form.value.currency_code,
            exchange_rate: form.value.exchange_rate,
            lines: validLines,
        };

        const response = await axios.post('/finance/api/journal-entries', payload);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Journal entry created successfully',
        });

        // Redirect to detail page if ID is returned, otherwise to list
        if (response.data.id) {
            router.visit(`/finance/journal-entries/${response.data.id}`);
        } else {
            router.visit('/finance/journal-entries');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to create journal entry',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Get account label by id
const getAccountLabel = (accountId) => {
    const account = props.accounts.find(a => a.value === accountId);
    return account ? `${account.account_code} - ${account.account_name}` : '';
};

// Count valid lines for summary
const validLinesCount = computed(() => {
    return lines.value.filter(line => {
        const hasAccount = line.account_id !== null;
        const hasAmount = (parseFloat(line.debit_amount) || 0) > 0 || (parseFloat(line.credit_amount) || 0) > 0;
        return hasAccount && hasAmount;
    }).length;
});
</script>

<template>
    <AppLayout title="Create Journal Entry">
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
                <Link href="/finance/journal-entries" class="hover:text-primary">Journal Entries</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Create</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Create Journal Entry</h1>
                    <p class="text-sm text-gray-500">Record a manual journal entry with debit and credit lines</p>
                </div>
                <Link href="/finance/journal-entries" class="btn btn-light">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Back to List
                </Link>
            </div>

            <!-- Validation Errors Summary -->
            <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded">
                <div class="flex items-start gap-3">
                    <i class="ki-filled ki-information-2 text-lg mt-0.5"></i>
                    <div>
                        <p class="font-semibold mb-2">Please fix the following errors:</p>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            <li v-for="(messages, field) in errors" :key="field">
                                <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Left Column: Main Form -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Entry Information Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-notepad me-2 text-gray-500"></i>
                                    Entry Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Entry Number -->
                                    <div>
                                        <label class="form-label text-gray-700">Entry Number</label>
                                        <input
                                            type="text"
                                            class="input w-full bg-gray-50"
                                            :value="props.nextNumber"
                                            readonly
                                        />
                                        <span class="text-xs text-gray-500 mt-1">Auto-generated</span>
                                    </div>

                                    <!-- Fiscal Period -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Fiscal Period <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.fiscal_period_id"
                                            :options="fiscalPeriodOptions"
                                            placeholder="Select fiscal period"
                                            search-placeholder="Search periods..."
                                            clearable
                                        />
                                        <span v-if="errors.fiscal_period_id" class="text-xs text-danger mt-1">
                                            {{ errors.fiscal_period_id[0] }}
                                        </span>
                                    </div>

                                    <!-- Entry Date -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Entry Date <span class="text-danger">*</span>
                                        </label>
                                        <DatePicker
                                            v-model="form.entry_date"
                                            placeholder="Select date"
                                            :minDate="selectedFiscalPeriod?.start_date"
                                            :maxDate="selectedFiscalPeriod?.end_date"
                                        />
                                        <span v-if="selectedFiscalPeriod" class="text-xs text-gray-500 mt-1">
                                            Period: {{ selectedFiscalPeriod.start_date }} to {{ selectedFiscalPeriod.end_date }}
                                        </span>
                                        <span v-if="errors.entry_date" class="text-xs text-danger mt-1 block">
                                            {{ errors.entry_date[0] }}
                                        </span>
                                    </div>

                                    <!-- Currency -->
                                    <div>
                                        <label class="form-label text-gray-700">Currency</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.currency_code"
                                            placeholder="IDR"
                                        />
                                    </div>

                                    <!-- Exchange Rate -->
                                    <div>
                                        <label class="form-label text-gray-700">Exchange Rate</label>
                                        <input
                                            type="number"
                                            class="input w-full"
                                            v-model.number="form.exchange_rate"
                                            step="0.000001"
                                            min="0"
                                        />
                                    </div>

                                    <!-- Memo -->
                                    <div class="md:col-span-2">
                                        <label class="form-label text-gray-700">Memo</label>
                                        <textarea
                                            class="textarea w-full"
                                            v-model="form.memo"
                                            rows="2"
                                            maxlength="255"
                                            placeholder="Brief description of the journal entry"
                                        ></textarea>
                                        <span class="text-xs text-gray-400">
                                            {{ (form.memo || '').length }}/255 characters
                                        </span>
                                        <span v-if="errors.memo" class="text-xs text-danger mt-1 block">
                                            {{ errors.memo[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Journal Lines Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-row-horizontal me-2 text-gray-500"></i>
                                    Journal Lines
                                </h3>
                                <button
                                    type="button"
                                    @click="addLine"
                                    class="btn btn-sm btn-light"
                                >
                                    <i class="ki-filled ki-plus me-1"></i>
                                    Add Row
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <!-- Lines Table -->
                                <div class="overflow-x-auto">
                                    <table class="table table-border w-full min-w-[700px]">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="min-w-[250px] px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                                    Account <span class="text-danger">*</span>
                                                </th>
                                                <th class="min-w-[180px] px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                                    Description
                                                </th>
                                                <th class="w-[140px] px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                                    Debit
                                                </th>
                                                <th class="w-[140px] px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                                    Credit
                                                </th>
                                                <th class="w-[60px] px-4 py-3 text-center text-sm font-semibold text-gray-700">

                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(line, index) in lines" :key="line.id" class="border-b border-gray-100 hover:bg-gray-50/50">
                                                <!-- Account -->
                                                <td class="px-4 py-3">
                                                    <SearchableSelect
                                                        v-model="line.account_id"
                                                        :options="accountOptions"
                                                        placeholder="Select account"
                                                        search-placeholder="Search accounts..."
                                                        size="sm"
                                                        clearable
                                                    />
                                                    <span v-if="errors[`lines.${index}.account_id`]" class="text-xs text-danger mt-1">
                                                        {{ errors[`lines.${index}.account_id`][0] }}
                                                    </span>
                                                </td>
                                                <!-- Description -->
                                                <td class="px-4 py-3">
                                                    <input
                                                        type="text"
                                                        class="input input-sm w-full"
                                                        v-model="line.description"
                                                        placeholder="Line description"
                                                    />
                                                </td>
                                                <!-- Debit -->
                                                <td class="px-4 py-3">
                                                    <input
                                                        type="text"
                                                        class="input input-sm w-full text-right"
                                                        :value="formatNumber(line.debit_amount)"
                                                        @input="handleDebitInput(index, $event)"
                                                        placeholder="0"
                                                    />
                                                </td>
                                                <!-- Credit -->
                                                <td class="px-4 py-3">
                                                    <input
                                                        type="text"
                                                        class="input input-sm w-full text-right"
                                                        :value="formatNumber(line.credit_amount)"
                                                        @input="handleCreditInput(index, $event)"
                                                        placeholder="0"
                                                    />
                                                </td>
                                                <!-- Actions -->
                                                <td class="px-4 py-3 text-center">
                                                    <button
                                                        type="button"
                                                        @click="removeLine(index)"
                                                        class="btn btn-icon btn-xs btn-light hover:btn-danger"
                                                        :class="{ 'opacity-50 cursor-not-allowed': lines.length <= 2 }"
                                                        :disabled="lines.length <= 2"
                                                        title="Remove line"
                                                    >
                                                        <i class="ki-outline ki-trash text-base"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <!-- Totals Footer -->
                                        <tfoot>
                                            <tr class="bg-gray-50 border-t-2 border-gray-200">
                                                <td colspan="2" class="px-4 py-3 text-right font-semibold text-gray-700">
                                                    Totals
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                                    {{ formatCurrency(totalDebit) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                                    {{ formatCurrency(totalCredit) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                            <tr class="bg-gray-50">
                                                <td colspan="2" class="px-4 py-3 text-right font-semibold text-gray-700">
                                                    Balance
                                                </td>
                                                <td colspan="2" class="px-4 py-3 text-center">
                                                    <span
                                                        v-if="isBalanced"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-success-light text-success"
                                                    >
                                                        <i class="ki-filled ki-check-circle text-sm"></i>
                                                        Balanced
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-danger-light text-danger"
                                                    >
                                                        <i class="ki-filled ki-information-2 text-sm"></i>
                                                        Difference: {{ formatCurrency(difference) }}
                                                    </span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Add Row Button (Mobile) -->
                                <div class="p-4 border-t border-gray-100">
                                    <button
                                        type="button"
                                        @click="addLine"
                                        class="btn btn-light w-full"
                                    >
                                        <i class="ki-filled ki-plus me-2"></i>
                                        Add Row
                                    </button>
                                </div>

                                <!-- Lines Error -->
                                <div v-if="errors.lines || errors.balance" class="px-4 pb-4">
                                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                                        <p v-if="errors.lines">{{ errors.lines[0] }}</p>
                                        <p v-if="errors.balance">{{ errors.balance[0] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Summary & Actions -->
                    <div class="lg:col-span-1">
                        <div class="card sticky top-5">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-chart-simple me-2 text-gray-500"></i>
                                    Summary
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Entry #</span>
                                        <span class="font-medium text-gray-900">{{ props.nextNumber || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Date</span>
                                        <span class="font-medium text-gray-900">{{ form.entry_date || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Currency</span>
                                        <span class="font-medium text-gray-900">{{ form.currency_code }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Lines</span>
                                        <span class="font-medium text-gray-900">{{ validLinesCount }} valid</span>
                                    </div>

                                    <div class="border-t pt-3 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Total Debit</span>
                                            <span class="font-semibold text-gray-900">{{ formatCurrency(totalDebit) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Total Credit</span>
                                            <span class="font-semibold text-gray-900">{{ formatCurrency(totalCredit) }}</span>
                                        </div>
                                    </div>

                                    <div class="border-t pt-3">
                                        <div class="flex justify-between items-center">
                                            <span class="font-semibold text-gray-900">Status</span>
                                            <span
                                                v-if="isBalanced && totalDebit > 0"
                                                class="badge badge-sm badge-success"
                                            >
                                                <i class="ki-filled ki-check text-xs me-1"></i>
                                                Balanced
                                            </span>
                                            <span
                                                v-else-if="totalDebit === 0 && totalCredit === 0"
                                                class="badge badge-sm badge-warning"
                                            >
                                                <i class="ki-filled ki-information text-xs me-1"></i>
                                                No Amounts
                                            </span>
                                            <span
                                                v-else
                                                class="badge badge-sm badge-danger"
                                            >
                                                <i class="ki-filled ki-cross text-xs me-1"></i>
                                                Unbalanced
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 space-y-3">
                                    <button
                                        type="submit"
                                        class="btn btn-primary w-full"
                                        :disabled="isSubmitting || !isBalanced || totalDebit === 0"
                                    >
                                        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-loading animate-spin"></i>
                                            Saving...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-file"></i>
                                            Save as Draft
                                        </span>
                                    </button>
                                    <Link
                                        href="/finance/journal-entries"
                                        class="btn btn-light w-full"
                                    >
                                        Cancel
                                    </Link>
                                </div>

                                <!-- Help Text -->
                                <div class="mt-4 pt-4 border-t">
                                    <div class="text-xs text-gray-500 space-y-1">
                                        <p><i class="ki-filled ki-information-2 me-1"></i> Entry will be saved as draft</p>
                                        <p><i class="ki-filled ki-check me-1"></i> Ensure debit equals credit</p>
                                        <p><i class="ki-filled ki-calendar me-1"></i> Date must be within fiscal period</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
