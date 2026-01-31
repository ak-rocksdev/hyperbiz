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
    entry: {
        type: Object,
        required: true
    },
    accounts: {
        type: Array,
        default: () => []
    },
    fiscalPeriods: {
        type: Array,
        default: () => []
    }
});

// Form state
const form = ref({
    entry_date: '',
    fiscal_period_id: null,
    currency_code: 'IDR',
    exchange_rate: 1,
    memo: '',
    lines: []
});

// Form state management
const errors = ref({});
const isSubmitting = ref(false);

// Currency options
const currencyOptions = [
    { value: 'IDR', label: 'Indonesian Rupiah (IDR)' },
    { value: 'USD', label: 'US Dollar (USD)' },
    { value: 'EUR', label: 'Euro (EUR)' },
    { value: 'SGD', label: 'Singapore Dollar (SGD)' },
    { value: 'MYR', label: 'Malaysian Ringgit (MYR)' },
];

// Computed: Account options for SearchableSelect
const accountOptions = computed(() => {
    return props.accounts.map(acc => ({
        value: acc.value,
        label: `${acc.account_code} - ${acc.account_name}`,
        sublabel: acc.account_type,
        account_code: acc.account_code,
        account_name: acc.account_name,
        account_type: acc.account_type,
        normal_balance: acc.normal_balance
    }));
});

// Computed: Fiscal period options for SearchableSelect
const fiscalPeriodOptions = computed(() => {
    return props.fiscalPeriods.map(fp => ({
        value: fp.value,
        label: fp.label,
        sublabel: `${fp.start_date} to ${fp.end_date}`
    }));
});

// Computed: Total debits
const totalDebits = computed(() => {
    return form.value.lines.reduce((sum, line) => {
        return sum + (parseFloat(line.debit_amount) || 0);
    }, 0);
});

// Computed: Total credits
const totalCredits = computed(() => {
    return form.value.lines.reduce((sum, line) => {
        return sum + (parseFloat(line.credit_amount) || 0);
    }, 0);
});

// Computed: Balance difference
const balanceDifference = computed(() => {
    return totalDebits.value - totalCredits.value;
});

// Computed: Is balanced
const isBalanced = computed(() => {
    return Math.abs(balanceDifference.value) < 0.01;
});

// Computed: Can remove row (minimum 2 lines required)
const canRemoveRow = computed(() => {
    return form.value.lines.length > 2;
});

// Format currency helper
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.value.currency_code || 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

// Get account display name
const getAccountDisplay = (accountId) => {
    const account = props.accounts.find(acc => acc.value === accountId);
    if (account) {
        return `${account.account_code} - ${account.account_name}`;
    }
    return '';
};

// Add new line
const addLine = () => {
    form.value.lines.push({
        id: null,
        account_id: null,
        description: '',
        debit_amount: 0,
        credit_amount: 0
    });
};

// Remove line
const removeLine = (index) => {
    if (canRemoveRow.value) {
        form.value.lines.splice(index, 1);
    } else {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'warning',
            title: 'Minimum Lines',
            text: 'Journal entry must have at least 2 lines.',
        });
    }
};

// Handle debit input - clear credit if debit has value
const handleDebitInput = (index) => {
    if (form.value.lines[index].debit_amount > 0) {
        form.value.lines[index].credit_amount = 0;
    }
};

// Handle credit input - clear debit if credit has value
const handleCreditInput = (index) => {
    if (form.value.lines[index].credit_amount > 0) {
        form.value.lines[index].debit_amount = 0;
    }
};

// Initialize form with entry data
onMounted(() => {
    form.value = {
        entry_date: props.entry.entry_date || '',
        fiscal_period_id: props.entry.fiscal_period_id || null,
        currency_code: props.entry.currency_code || 'IDR',
        exchange_rate: props.entry.exchange_rate || 1,
        memo: props.entry.memo || '',
        lines: (props.entry.lines || []).map(line => ({
            id: line.id || null,
            account_id: line.account_id || null,
            description: line.description || '',
            debit_amount: parseFloat(line.debit_amount) || 0,
            credit_amount: parseFloat(line.credit_amount) || 0
        }))
    };

    // Ensure minimum 2 lines
    while (form.value.lines.length < 2) {
        form.value.lines.push({
            id: null,
            account_id: null,
            description: '',
            debit_amount: 0,
            credit_amount: 0
        });
    }
});

// Validate form
const validateForm = () => {
    errors.value = {};

    if (!form.value.entry_date) {
        errors.value.entry_date = ['Entry date is required'];
    }

    if (!form.value.fiscal_period_id) {
        errors.value.fiscal_period_id = ['Fiscal period is required'];
    }

    // Validate lines
    let hasLineErrors = false;
    form.value.lines.forEach((line, index) => {
        if (!line.account_id) {
            errors.value[`lines.${index}.account_id`] = ['Account is required'];
            hasLineErrors = true;
        }
        if (line.debit_amount === 0 && line.credit_amount === 0) {
            errors.value[`lines.${index}.amount`] = ['Either debit or credit amount is required'];
            hasLineErrors = true;
        }
    });

    // Check if balanced
    if (!isBalanced.value) {
        errors.value.balance = ['Total debits must equal total credits'];
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
        const payload = {
            entry_date: form.value.entry_date,
            fiscal_period_id: form.value.fiscal_period_id,
            currency_code: form.value.currency_code,
            exchange_rate: form.value.exchange_rate,
            memo: form.value.memo,
            lines: form.value.lines.map(line => ({
                id: line.id,
                account_id: line.account_id,
                description: line.description,
                debit_amount: parseFloat(line.debit_amount) || 0,
                credit_amount: parseFloat(line.credit_amount) || 0
            }))
        };

        const response = await axios.put(`/finance/api/journal-entries/${props.entry.id}`, payload);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Journal entry updated successfully',
        });

        // Redirect to detail page
        router.visit(`/finance/journal-entries/${props.entry.id}`);

    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to update journal entry',
        });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Edit Journal Entry">
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
                <Link :href="`/finance/journal-entries/${entry.id}`" class="hover:text-primary">
                    {{ entry.entry_number }}
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Edit</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Edit Journal Entry</h1>
                    <p class="text-sm text-gray-500">{{ entry.entry_number }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/finance/journal-entries/${entry.id}`" class="btn btn-light">
                        <i class="ki-outline ki-arrow-left me-1"></i>
                        Cancel
                    </Link>
                    <Link href="/finance/journal-entries" class="btn btn-light">
                        <i class="ki-outline ki-notepad me-1"></i>
                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Validation Errors Summary -->
            <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded">
                <p class="font-bold mb-2">Please fix the following errors:</p>
                <ul class="list-disc pl-5 text-sm">
                    <li v-for="(messages, field) in errors" :key="field">
                        <span v-for="(message, index) in (Array.isArray(messages) ? messages : [messages])" :key="index">
                            {{ message }}
                        </span>
                    </li>
                </ul>
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
                                    <i class="ki-filled ki-information-3 me-2 text-gray-500"></i>
                                    Entry Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Entry Number (Readonly) -->
                                    <div>
                                        <label class="form-label text-gray-700">Entry Number</label>
                                        <input
                                            type="text"
                                            class="input w-full bg-gray-100"
                                            :value="entry.entry_number"
                                            readonly
                                            disabled
                                        />
                                    </div>

                                    <!-- Entry Date -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Entry Date <span class="text-danger">*</span>
                                        </label>
                                        <DatePicker
                                            v-model="form.entry_date"
                                            placeholder="Select date"
                                        />
                                        <span v-if="errors.entry_date" class="text-xs text-danger mt-1">
                                            {{ errors.entry_date[0] }}
                                        </span>
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

                                    <!-- Currency -->
                                    <div>
                                        <label class="form-label text-gray-700">Currency</label>
                                        <SearchableSelect
                                            v-model="form.currency_code"
                                            :options="currencyOptions"
                                            placeholder="Select currency"
                                            :searchable="false"
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
                                            placeholder="Enter memo or description for this journal entry..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Journal Lines Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-notepad-edit me-2 text-gray-500"></i>
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
                                <!-- Balance Warning -->
                                <div v-if="!isBalanced && form.lines.length > 0" class="bg-yellow-50 border-b border-yellow-200 px-5 py-3">
                                    <div class="flex items-center gap-2 text-yellow-700">
                                        <i class="ki-filled ki-information-2"></i>
                                        <span class="text-sm font-medium">
                                            Journal entry is not balanced. Difference: {{ formatCurrency(Math.abs(balanceDifference)) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Lines Table -->
                                <div class="overflow-x-auto">
                                    <table class="table table-border w-full">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="min-w-[250px]">Account <span class="text-danger">*</span></th>
                                                <th class="min-w-[180px]">Description</th>
                                                <th class="w-[140px] text-end">Debit</th>
                                                <th class="w-[140px] text-end">Credit</th>
                                                <th class="w-[60px] text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(line, index) in form.lines" :key="index">
                                                <td class="align-top py-3">
                                                    <SearchableSelect
                                                        v-model="line.account_id"
                                                        :options="accountOptions"
                                                        placeholder="Select account"
                                                        search-placeholder="Search accounts..."
                                                        clearable
                                                        size="sm"
                                                    />
                                                    <span v-if="errors[`lines.${index}.account_id`]" class="text-xs text-danger">
                                                        {{ errors[`lines.${index}.account_id`][0] }}
                                                    </span>
                                                </td>
                                                <td class="align-top py-3">
                                                    <input
                                                        type="text"
                                                        class="input input-sm w-full"
                                                        v-model="line.description"
                                                        placeholder="Line description"
                                                    />
                                                </td>
                                                <td class="align-top py-3">
                                                    <input
                                                        type="number"
                                                        class="input input-sm w-full text-end"
                                                        v-model.number="line.debit_amount"
                                                        @input="handleDebitInput(index)"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                    />
                                                </td>
                                                <td class="align-top py-3">
                                                    <input
                                                        type="number"
                                                        class="input input-sm w-full text-end"
                                                        v-model.number="line.credit_amount"
                                                        @input="handleCreditInput(index)"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                    />
                                                </td>
                                                <td class="align-top py-3 text-center">
                                                    <button
                                                        type="button"
                                                        @click="removeLine(index)"
                                                        class="btn btn-icon btn-xs btn-light hover:btn-danger"
                                                        :disabled="!canRemoveRow"
                                                        :title="canRemoveRow ? 'Remove row' : 'Minimum 2 lines required'"
                                                    >
                                                        <i class="ki-outline ki-trash text-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Empty State -->
                                            <tr v-if="form.lines.length === 0">
                                                <td colspan="5">
                                                    <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                                        <i class="ki-outline ki-notepad-edit text-5xl mb-3"></i>
                                                        <span>No journal lines yet</span>
                                                        <span class="text-sm">Click "Add Row" to add journal lines</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <!-- Totals Footer -->
                                        <tfoot class="bg-gray-50 border-t-2">
                                            <tr>
                                                <td colspan="2" class="text-end font-semibold text-gray-700 py-3">
                                                    Totals:
                                                </td>
                                                <td class="text-end font-semibold text-gray-900 py-3">
                                                    {{ formatCurrency(totalDebits) }}
                                                </td>
                                                <td class="text-end font-semibold text-gray-900 py-3">
                                                    {{ formatCurrency(totalCredits) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="text-end font-medium text-gray-600 py-2">
                                                    Difference:
                                                </td>
                                                <td colspan="2" class="text-center py-2">
                                                    <span
                                                        class="font-semibold"
                                                        :class="isBalanced ? 'text-success' : 'text-danger'"
                                                    >
                                                        {{ formatCurrency(Math.abs(balanceDifference)) }}
                                                        <i v-if="isBalanced" class="ki-filled ki-check-circle ms-1"></i>
                                                        <i v-else class="ki-filled ki-cross-circle ms-1"></i>
                                                    </span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Add Row Button (Bottom) -->
                                <div class="px-5 py-4 border-t">
                                    <button
                                        type="button"
                                        @click="addLine"
                                        class="btn btn-light btn-sm"
                                    >
                                        <i class="ki-filled ki-plus me-1"></i>
                                        Add Another Line
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Summary & Actions -->
                    <div class="lg:col-span-1">
                        <div class="card sticky top-5">
                            <div class="card-header">
                                <h3 class="card-title">Entry Summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <!-- Entry Number -->
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Entry #</span>
                                        <span class="font-medium text-gray-900">{{ entry.entry_number }}</span>
                                    </div>

                                    <!-- Date -->
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Date</span>
                                        <span class="font-medium text-gray-900">{{ form.entry_date || '-' }}</span>
                                    </div>

                                    <!-- Currency -->
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Currency</span>
                                        <span class="font-medium text-gray-900">{{ form.currency_code }}</span>
                                    </div>

                                    <!-- Lines Count -->
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Lines</span>
                                        <span class="font-medium text-gray-900">{{ form.lines.length }}</span>
                                    </div>

                                    <div class="border-t pt-3 space-y-2">
                                        <!-- Total Debits -->
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Total Debits</span>
                                            <span class="font-medium text-gray-900">{{ formatCurrency(totalDebits) }}</span>
                                        </div>

                                        <!-- Total Credits -->
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Total Credits</span>
                                            <span class="font-medium text-gray-900">{{ formatCurrency(totalCredits) }}</span>
                                        </div>
                                    </div>

                                    <!-- Balance Status -->
                                    <div class="border-t pt-3">
                                        <div
                                            class="p-3 rounded-lg"
                                            :class="isBalanced ? 'bg-success/10' : 'bg-danger/10'"
                                        >
                                            <div class="flex items-center gap-2">
                                                <i
                                                    class="text-lg"
                                                    :class="isBalanced ? 'ki-filled ki-check-circle text-success' : 'ki-filled ki-cross-circle text-danger'"
                                                ></i>
                                                <div>
                                                    <span
                                                        class="font-medium text-sm"
                                                        :class="isBalanced ? 'text-success' : 'text-danger'"
                                                    >
                                                        {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
                                                    </span>
                                                    <p v-if="!isBalanced" class="text-xs text-danger mt-0.5">
                                                        Difference: {{ formatCurrency(Math.abs(balanceDifference)) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 space-y-3">
                                    <button
                                        type="submit"
                                        class="btn btn-primary w-full"
                                        :disabled="isSubmitting || !isBalanced"
                                    >
                                        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-loading animate-spin"></i>
                                            Saving...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-check"></i>
                                            Save Changes
                                        </span>
                                    </button>
                                    <Link
                                        :href="`/finance/journal-entries/${entry.id}`"
                                        class="btn btn-light w-full"
                                    >
                                        <i class="ki-outline ki-arrow-left me-1"></i>
                                        Cancel
                                    </Link>
                                </div>

                                <!-- Help Info -->
                                <div class="mt-4 pt-4 border-t">
                                    <div class="p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-start gap-2 text-sm text-blue-700">
                                            <i class="ki-filled ki-information-2 mt-0.5"></i>
                                            <div>
                                                <p class="font-medium">Editing Draft Entry</p>
                                                <p class="text-xs mt-1">Only draft entries can be edited. The entry must be balanced before saving.</p>
                                            </div>
                                        </div>
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

<style scoped>
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
