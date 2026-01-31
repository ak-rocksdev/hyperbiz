<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    accounts: {
        type: Array,
        default: () => []
    },
    paymentAccounts: {
        type: Array,
        default: () => []
    },
    suppliers: {
        type: Array,
        default: () => []
    },
    nextNumber: {
        type: String,
        default: ''
    }
});

// Form state
const form = ref({
    expense_date: new Date().toISOString().split('T')[0],
    category_id: null,
    account_id: null,
    paid_from_account_id: null,
    supplier_id: null,
    payee_name: '',
    currency_code: 'IDR',
    exchange_rate: 1,
    amount: 0,
    tax_amount: 0,
    payment_method: null,
    reference_number: '',
    description: '',
    notes: '',
    is_recurring: false,
    recurring_frequency: null,
});

const attachments = ref([]);
const payeeType = ref('manual'); // 'supplier' or 'manual'
const errors = ref({});
const isSubmitting = ref(false);

// Category options for SearchableSelect
const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        value: cat.value,
        label: cat.label,
        default_account_id: cat.default_account_id
    }));
});

// Account options for SearchableSelect
const accountOptions = computed(() => {
    return props.accounts.map(acc => ({
        value: acc.value,
        label: acc.label
    }));
});

// Payment account options for SearchableSelect
const paymentAccountOptions = computed(() => {
    return [
        { value: '', label: 'Select Payment Account' },
        ...props.paymentAccounts.map(acc => ({
            value: acc.value,
            label: acc.label
        }))
    ];
});

// Supplier options for SearchableSelect
const supplierOptions = computed(() => {
    return props.suppliers.map(sup => ({
        value: sup.value,
        label: sup.label
    }));
});

// Payment method options
const paymentMethodOptions = [
    { value: '', label: 'Select Payment Method' },
    { value: 'cash', label: 'Cash' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'credit_card', label: 'Credit Card' },
    { value: 'check', label: 'Check' },
    { value: 'other', label: 'Other' },
];

// Recurring frequency options
const recurringFrequencyOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'yearly', label: 'Yearly' },
];

// Computed total amount
const totalAmount = computed(() => {
    const amount = parseFloat(form.value.amount) || 0;
    const taxAmount = parseFloat(form.value.tax_amount) || 0;
    return amount + taxAmount;
});

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.value.currency_code || 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

// Watch category change to auto-set account_id
watch(() => form.value.category_id, (newCategoryId) => {
    if (newCategoryId) {
        const selectedCategory = props.categories.find(cat => cat.value === newCategoryId);
        if (selectedCategory && selectedCategory.default_account_id) {
            form.value.account_id = selectedCategory.default_account_id;
        }
    }
});

// Watch payee type to clear the other field
watch(payeeType, (newType) => {
    if (newType === 'supplier') {
        form.value.payee_name = '';
    } else {
        form.value.supplier_id = null;
    }
});

// Watch is_recurring to clear frequency if unchecked
watch(() => form.value.is_recurring, (isRecurring) => {
    if (!isRecurring) {
        form.value.recurring_frequency = null;
    }
});

// Handle file selection
const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    const allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    const maxSize = 10 * 1024 * 1024; // 10MB

    files.forEach(file => {
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'warning',
                title: 'Invalid File Type',
                text: `${file.name} is not a supported file type.`,
            });
            return;
        }

        if (file.size > maxSize) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'warning',
                title: 'File Too Large',
                text: `${file.name} exceeds the 10MB limit.`,
            });
            return;
        }

        attachments.value.push({
            id: Date.now() + Math.random(),
            file: file,
            name: file.name,
            size: file.size,
            type: file.type,
            preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
        });
    });

    // Reset input
    event.target.value = '';
};

// Remove attachment
const removeAttachment = (index) => {
    const attachment = attachments.value[index];
    if (attachment.preview) {
        URL.revokeObjectURL(attachment.preview);
    }
    attachments.value.splice(index, 1);
};

// Get file icon based on type
const getFileIcon = (type) => {
    if (type.startsWith('image/')) return 'ki-filled ki-picture';
    if (type === 'application/pdf') return 'ki-filled ki-file-sheet';
    if (type.includes('word')) return 'ki-filled ki-document';
    if (type.includes('excel') || type.includes('spreadsheet')) return 'ki-filled ki-chart-simple-2';
    return 'ki-filled ki-file';
};

// Format file size
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Validate form
const validateForm = () => {
    errors.value = {};

    if (!form.value.expense_date) {
        errors.value.expense_date = ['Expense date is required'];
    }

    if (!form.value.category_id) {
        errors.value.category_id = ['Category is required'];
    }

    if (!form.value.account_id) {
        errors.value.account_id = ['Expense account is required'];
    }

    if (!form.value.amount || form.value.amount <= 0) {
        errors.value.amount = ['Amount must be greater than 0'];
    }

    if (payeeType.value === 'supplier' && !form.value.supplier_id) {
        errors.value.supplier_id = ['Please select a supplier'];
    }

    if (payeeType.value === 'manual' && !form.value.payee_name.trim()) {
        errors.value.payee_name = ['Please enter payee name'];
    }

    if (form.value.is_recurring && !form.value.recurring_frequency) {
        errors.value.recurring_frequency = ['Please select a recurring frequency'];
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
        const formData = new FormData();

        // Append form fields
        formData.append('expense_number', props.nextNumber);
        formData.append('expense_date', form.value.expense_date);
        formData.append('category_id', form.value.category_id);
        formData.append('account_id', form.value.account_id);
        formData.append('currency_code', form.value.currency_code);
        formData.append('exchange_rate', form.value.exchange_rate);
        formData.append('amount', form.value.amount);
        formData.append('tax_amount', form.value.tax_amount || 0);
        formData.append('description', form.value.description || '');
        formData.append('notes', form.value.notes || '');
        formData.append('reference_number', form.value.reference_number || '');
        formData.append('is_recurring', form.value.is_recurring ? '1' : '0');

        if (form.value.recurring_frequency) {
            formData.append('recurring_frequency', form.value.recurring_frequency);
        }

        if (form.value.paid_from_account_id) {
            formData.append('paid_from_account_id', form.value.paid_from_account_id);
        }

        if (form.value.payment_method) {
            formData.append('payment_method', form.value.payment_method);
        }

        // Payee info
        if (payeeType.value === 'supplier' && form.value.supplier_id) {
            formData.append('supplier_id', form.value.supplier_id);
        } else if (form.value.payee_name) {
            formData.append('payee_name', form.value.payee_name);
        }

        // Append attachments
        attachments.value.forEach((attachment, index) => {
            formData.append(`attachments[${index}]`, attachment.file);
        });

        const response = await axios.post('/finance/api/expenses', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Expense created successfully',
        });

        router.visit('/finance/expenses');
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to create expense',
        });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Create Expense">
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
                <Link href="/finance/expenses" class="hover:text-primary">Expenses</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Create New</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Create New Expense</h1>
                    <p class="text-sm text-gray-500">Record a new expense transaction</p>
                </div>
                <Link href="/finance/expenses" class="btn btn-light">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Back to List
                </Link>
            </div>

            <!-- Validation Errors Summary -->
            <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 mb-5 rounded">
                <p class="font-bold mb-2">Please fix the following errors:</p>
                <ul class="list-disc pl-5 text-sm">
                    <li v-for="(messages, field) in errors" :key="field">
                        <span v-for="(message, index) in messages" :key="index">{{ message }}</span>
                    </li>
                </ul>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Left Column: Main Form -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Section 1: Basic Information -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-information-3 me-2 text-gray-500"></i>
                                    Basic Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Expense Number -->
                                    <div>
                                        <label class="form-label text-gray-700">Expense Number</label>
                                        <input
                                            type="text"
                                            class="input w-full bg-gray-50"
                                            :value="nextNumber"
                                            readonly
                                        />
                                        <span class="text-xs text-gray-500 mt-1">Auto-generated</span>
                                    </div>

                                    <!-- Expense Date -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Expense Date <span class="text-danger">*</span>
                                        </label>
                                        <DatePicker
                                            v-model="form.expense_date"
                                            placeholder="Select date"
                                        />
                                        <span v-if="errors.expense_date" class="text-xs text-danger mt-1">
                                            {{ errors.expense_date[0] }}
                                        </span>
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Category <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.category_id"
                                            :options="categoryOptions"
                                            placeholder="Select category"
                                            search-placeholder="Search categories..."
                                            clearable
                                        />
                                        <span v-if="errors.category_id" class="text-xs text-danger mt-1">
                                            {{ errors.category_id[0] }}
                                        </span>
                                    </div>

                                    <!-- Expense Account -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Expense Account <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.account_id"
                                            :options="accountOptions"
                                            placeholder="Select expense account"
                                            search-placeholder="Search accounts..."
                                            clearable
                                        />
                                        <span class="text-xs text-gray-500 mt-1">
                                            Auto-filled based on category if available
                                        </span>
                                        <span v-if="errors.account_id" class="text-xs text-danger mt-1 block">
                                            {{ errors.account_id[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Payee Information -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-user me-2 text-gray-500"></i>
                                    Payee Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Payee Type Toggle -->
                                <div class="mb-4">
                                    <label class="form-label text-gray-700 mb-3">Payee Type</label>
                                    <div class="flex gap-4">
                                        <label
                                            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all flex-1"
                                            :class="payeeType === 'manual' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300'"
                                        >
                                            <input
                                                type="radio"
                                                v-model="payeeType"
                                                value="manual"
                                                class="radio radio-sm"
                                            />
                                            <div>
                                                <span class="font-medium text-gray-900">Manual Entry</span>
                                                <p class="text-xs text-gray-500">Enter payee name manually</p>
                                            </div>
                                        </label>
                                        <label
                                            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all flex-1"
                                            :class="payeeType === 'supplier' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300'"
                                        >
                                            <input
                                                type="radio"
                                                v-model="payeeType"
                                                value="supplier"
                                                class="radio radio-sm"
                                            />
                                            <div>
                                                <span class="font-medium text-gray-900">Supplier</span>
                                                <p class="text-xs text-gray-500">Select from suppliers list</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Supplier Selection (shown if payeeType is supplier) -->
                                    <div v-if="payeeType === 'supplier'">
                                        <label class="form-label text-gray-700">
                                            Supplier <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.supplier_id"
                                            :options="supplierOptions"
                                            placeholder="Select supplier"
                                            search-placeholder="Search suppliers..."
                                            clearable
                                        />
                                        <span v-if="errors.supplier_id" class="text-xs text-danger mt-1">
                                            {{ errors.supplier_id[0] }}
                                        </span>
                                    </div>

                                    <!-- Payee Name (shown if payeeType is manual) -->
                                    <div v-if="payeeType === 'manual'">
                                        <label class="form-label text-gray-700">
                                            Payee Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.payee_name"
                                            placeholder="Enter payee name"
                                        />
                                        <span v-if="errors.payee_name" class="text-xs text-danger mt-1">
                                            {{ errors.payee_name[0] }}
                                        </span>
                                    </div>

                                    <!-- Reference Number -->
                                    <div>
                                        <label class="form-label text-gray-700">Reference Number</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.reference_number"
                                            placeholder="e.g., Invoice #, Receipt #"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Amount Details -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-dollar me-2 text-gray-500"></i>
                                    Amount Details
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Currency Code -->
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

                                    <!-- Amount -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Amount <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            class="input w-full"
                                            v-model.number="form.amount"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                        />
                                        <span v-if="errors.amount" class="text-xs text-danger mt-1">
                                            {{ errors.amount[0] }}
                                        </span>
                                    </div>

                                    <!-- Tax Amount -->
                                    <div>
                                        <label class="form-label text-gray-700">Tax Amount</label>
                                        <input
                                            type="number"
                                            class="input w-full"
                                            v-model.number="form.tax_amount"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <!-- Total Amount (readonly) -->
                                    <div class="md:col-span-2">
                                        <label class="form-label text-gray-700">Total Amount</label>
                                        <div class="input w-full bg-gray-50 flex items-center font-semibold text-lg text-primary">
                                            {{ formatCurrency(totalAmount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Payment Information -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-credit-cart me-2 text-gray-500"></i>
                                    Payment Information
                                </h3>
                                <span class="text-xs text-gray-500">(Optional)</span>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Payment Method -->
                                    <div>
                                        <label class="form-label text-gray-700">Payment Method</label>
                                        <SearchableSelect
                                            v-model="form.payment_method"
                                            :options="paymentMethodOptions"
                                            placeholder="Select payment method"
                                            clearable
                                        />
                                    </div>

                                    <!-- Paid From Account -->
                                    <div>
                                        <label class="form-label text-gray-700">Paid From Account</label>
                                        <SearchableSelect
                                            v-model="form.paid_from_account_id"
                                            :options="paymentAccountOptions"
                                            placeholder="Select payment account"
                                            search-placeholder="Search accounts..."
                                            clearable
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Additional Details -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-note me-2 text-gray-500"></i>
                                    Additional Details
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <!-- Description -->
                                    <div>
                                        <label class="form-label text-gray-700">Description</label>
                                        <textarea
                                            class="textarea w-full"
                                            v-model="form.description"
                                            rows="2"
                                            maxlength="255"
                                            placeholder="Brief description of the expense"
                                        ></textarea>
                                        <span class="text-xs text-gray-400">
                                            {{ form.description.length }}/255 characters
                                        </span>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="form-label text-gray-700">Internal Notes</label>
                                        <textarea
                                            class="textarea w-full"
                                            v-model="form.notes"
                                            rows="3"
                                            placeholder="Additional notes (internal use only)"
                                        ></textarea>
                                    </div>

                                    <!-- Recurring Options -->
                                    <div class="border-t pt-4">
                                        <div class="flex items-center gap-3 mb-3">
                                            <label class="switch switch-sm">
                                                <input
                                                    v-model="form.is_recurring"
                                                    type="checkbox"
                                                    class="order-2"
                                                />
                                                <span class="switch-label order-1">Recurring Expense</span>
                                            </label>
                                        </div>

                                        <!-- Recurring Frequency (shown if is_recurring) -->
                                        <div v-if="form.is_recurring" class="ml-6">
                                            <label class="form-label text-gray-700">
                                                Frequency <span class="text-danger">*</span>
                                            </label>
                                            <SearchableSelect
                                                v-model="form.recurring_frequency"
                                                :options="recurringFrequencyOptions"
                                                placeholder="Select frequency"
                                                :searchable="false"
                                            />
                                            <span v-if="errors.recurring_frequency" class="text-xs text-danger mt-1">
                                                {{ errors.recurring_frequency[0] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 6: Attachments -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-paper-clip me-2 text-gray-500"></i>
                                    Attachments
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Upload Area -->
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors">
                                    <input
                                        type="file"
                                        id="file-upload"
                                        multiple
                                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                                        class="hidden"
                                        @change="handleFileSelect"
                                    />
                                    <label for="file-upload" class="cursor-pointer">
                                        <i class="ki-filled ki-cloud-add text-4xl text-gray-400 mb-3"></i>
                                        <p class="text-gray-600 font-medium">Click to upload or drag and drop</p>
                                        <p class="text-sm text-gray-400 mt-1">
                                            Images, PDF, Word, Excel (Max 10MB each)
                                        </p>
                                    </label>
                                </div>

                                <!-- Attached Files List -->
                                <div v-if="attachments.length > 0" class="mt-4 space-y-2">
                                    <p class="text-sm font-medium text-gray-700 mb-2">
                                        {{ attachments.length }} file(s) selected
                                    </p>
                                    <div
                                        v-for="(attachment, index) in attachments"
                                        :key="attachment.id"
                                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                                    >
                                        <!-- Preview or Icon -->
                                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-200 flex items-center justify-center flex-shrink-0">
                                            <img
                                                v-if="attachment.preview"
                                                :src="attachment.preview"
                                                :alt="attachment.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <i
                                                v-else
                                                :class="[getFileIcon(attachment.type), 'text-gray-500 text-lg']"
                                            ></i>
                                        </div>

                                        <!-- File Info -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ attachment.name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ formatFileSize(attachment.size) }}
                                            </p>
                                        </div>

                                        <!-- Remove Button -->
                                        <button
                                            type="button"
                                            @click="removeAttachment(index)"
                                            class="btn btn-icon btn-xs btn-light hover:btn-danger"
                                        >
                                            <i class="ki-filled ki-cross"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Summary & Actions -->
                    <div class="lg:col-span-1">
                        <div class="card sticky top-5">
                            <div class="card-header">
                                <h3 class="card-title">Summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Expense #</span>
                                        <span class="font-medium text-gray-900">{{ nextNumber }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Date</span>
                                        <span class="font-medium text-gray-900">{{ form.expense_date || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Amount</span>
                                        <span class="font-medium text-gray-900">{{ formatCurrency(form.amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Tax</span>
                                        <span class="font-medium text-gray-900">{{ formatCurrency(form.tax_amount) }}</span>
                                    </div>
                                    <div class="border-t pt-3 flex justify-between">
                                        <span class="font-semibold text-gray-900">Total</span>
                                        <span class="font-bold text-lg text-primary">{{ formatCurrency(totalAmount) }}</span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 space-y-3">
                                    <button
                                        type="submit"
                                        class="btn btn-primary w-full"
                                        :disabled="isSubmitting"
                                    >
                                        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-loading animate-spin"></i>
                                            Saving...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-check"></i>
                                            Save Expense
                                        </span>
                                    </button>
                                    <Link
                                        href="/finance/expenses"
                                        class="btn btn-light w-full"
                                    >
                                        Cancel
                                    </Link>
                                </div>

                                <!-- Attachments Count -->
                                <div v-if="attachments.length > 0" class="mt-4 pt-4 border-t">
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <i class="ki-filled ki-paper-clip"></i>
                                        <span>{{ attachments.length }} attachment(s)</span>
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
