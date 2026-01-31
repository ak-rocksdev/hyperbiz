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
    expense: {
        type: Object,
        required: true
    },
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
    }
});

// Form state
const form = ref({
    expense_date: '',
    category_id: null,
    account_id: null,
    paid_from_account_id: null,
    supplier_id: null,
    payee_name: '',
    currency_code: 'IDR',
    exchange_rate: 1,
    amount: 0,
    tax_amount: 0,
    payment_method: '',
    reference_number: '',
    description: '',
    notes: '',
    is_recurring: false,
    recurring_frequency: '',
});

// Payee type selection (supplier or manual)
const payeeType = ref('manual');

// Attachment management
const existingAttachments = ref([]);
const removeAttachments = ref([]);
const newAttachments = ref([]);

// Form state
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

// Payment method options
const paymentMethodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'credit_card', label: 'Credit Card' },
    { value: 'debit_card', label: 'Debit Card' },
    { value: 'check', label: 'Check' },
    { value: 'other', label: 'Other' },
];

// Recurring frequency options
const recurringFrequencyOptions = [
    { value: '', label: 'Not Recurring' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'yearly', label: 'Yearly' },
];

// Watch category selection to set default account
watch(() => form.value.category_id, (newCategoryId) => {
    if (newCategoryId) {
        const category = props.categories.find(c => c.value === newCategoryId);
        if (category?.default_account_id && !form.value.account_id) {
            form.value.account_id = category.default_account_id;
        }
    }
});

// Clear supplier_id when payee type changes to manual
watch(payeeType, (newType) => {
    if (newType === 'manual') {
        form.value.supplier_id = null;
    } else {
        form.value.payee_name = '';
    }
});

// Computed: Total amount (amount + tax)
const totalAmount = computed(() => {
    return (parseFloat(form.value.amount) || 0) + (parseFloat(form.value.tax_amount) || 0);
});

// Format currency helper
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.value.currency_code || 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

// Format file size helper
const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Get file icon based on type
const getFileIcon = (fileType) => {
    if (!fileType) return 'ki-filled ki-file';
    if (fileType.includes('image')) return 'ki-filled ki-picture';
    if (fileType.includes('pdf')) return 'ki-filled ki-document';
    if (fileType.includes('word') || fileType.includes('doc')) return 'ki-filled ki-file-doc';
    if (fileType.includes('excel') || fileType.includes('sheet') || fileType.includes('xls')) return 'ki-filled ki-file-sheet';
    return 'ki-filled ki-file';
};

// Handle file selection for new attachments
const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({
                icon: 'warning',
                title: 'File Too Large',
                text: `${file.name} exceeds 10MB limit`,
            });
            return;
        }
        newAttachments.value.push(file);
    });
    // Reset input
    event.target.value = '';
};

// Remove new attachment (before upload)
const removeNewAttachment = (index) => {
    newAttachments.value.splice(index, 1);
};

// Toggle existing attachment for removal
const toggleRemoveExisting = (attachmentId) => {
    const index = removeAttachments.value.indexOf(attachmentId);
    if (index === -1) {
        removeAttachments.value.push(attachmentId);
    } else {
        removeAttachments.value.splice(index, 1);
    }
};

// Check if existing attachment is marked for removal
const isMarkedForRemoval = (attachmentId) => {
    return removeAttachments.value.includes(attachmentId);
};

// Initialize form with expense data
onMounted(() => {
    // Pre-populate form from props.expense
    form.value = {
        expense_date: props.expense.expense_date || '',
        category_id: props.expense.category_id || null,
        account_id: props.expense.account_id || null,
        paid_from_account_id: props.expense.paid_from_account_id || null,
        supplier_id: props.expense.supplier_id || null,
        payee_name: props.expense.payee_name || '',
        currency_code: props.expense.currency_code || 'IDR',
        exchange_rate: props.expense.exchange_rate || 1,
        amount: props.expense.amount || 0,
        tax_amount: props.expense.tax_amount || 0,
        payment_method: props.expense.payment_method || '',
        reference_number: props.expense.reference_number || '',
        description: props.expense.description || '',
        notes: props.expense.notes || '',
        is_recurring: props.expense.is_recurring || false,
        recurring_frequency: props.expense.recurring_frequency || '',
    };

    // Set payee type based on whether supplier_id or payee_name is set
    payeeType.value = props.expense.supplier_id ? 'supplier' : 'manual';

    // Load existing attachments
    existingAttachments.value = props.expense.attachments || [];
});

// Submit form
const submitForm = async () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    errors.value = {};

    try {
        // Create FormData for file upload
        const formData = new FormData();

        // Append form fields
        formData.append('expense_date', form.value.expense_date || '');
        formData.append('category_id', form.value.category_id || '');
        formData.append('account_id', form.value.account_id || '');
        formData.append('paid_from_account_id', form.value.paid_from_account_id || '');

        if (payeeType.value === 'supplier' && form.value.supplier_id) {
            formData.append('supplier_id', form.value.supplier_id);
        } else {
            formData.append('payee_name', form.value.payee_name || '');
        }

        formData.append('currency_code', form.value.currency_code || 'IDR');
        formData.append('exchange_rate', form.value.exchange_rate || 1);
        formData.append('amount', form.value.amount || 0);
        formData.append('tax_amount', form.value.tax_amount || 0);
        formData.append('payment_method', form.value.payment_method || '');
        formData.append('reference_number', form.value.reference_number || '');
        formData.append('description', form.value.description || '');
        formData.append('notes', form.value.notes || '');
        formData.append('is_recurring', form.value.is_recurring ? '1' : '0');
        formData.append('recurring_frequency', form.value.recurring_frequency || '');

        // Append IDs of attachments to remove
        removeAttachments.value.forEach((id, index) => {
            formData.append(`remove_attachments[${index}]`, id);
        });

        // Append new attachment files
        newAttachments.value.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });

        // Use _method field for PUT request with FormData
        formData.append('_method', 'PUT');

        const response = await axios.post(`/finance/api/expenses/${props.expense.id}`, formData, {
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
            text: response.data.message || 'Expense updated successfully',
        });

        // Redirect to expense detail page
        router.visit(`/finance/expenses/${props.expense.id}`);

    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to update expense',
        });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Edit Expense">
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
                <Link :href="`/finance/expenses/${expense.id}`" class="hover:text-primary">
                    {{ expense.expense_number }}
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Edit</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Edit Expense</h1>
                    <p class="text-sm text-gray-500">{{ expense.expense_number }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/finance/expenses/${expense.id}`" class="btn btn-light">
                        <i class="ki-outline ki-arrow-left me-1"></i>
                        Cancel
                    </Link>
                    <Link href="/finance/expenses" class="btn btn-light">
                        <i class="ki-outline ki-notepad me-1"></i>
                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Left Column: Main Form -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Basic Information Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Expense Information</h3>
                            </div>
                            <div class="card-body">
                                <!-- Error Display -->
                                <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 p-4 mb-5 rounded">
                                    <div class="flex">
                                        <div class="ml-3">
                                            <p class="text-sm text-red-700 font-medium">Please fix the following errors:</p>
                                            <ul class="mt-2 list-disc list-inside text-sm text-red-600">
                                                <li v-for="(messages, field) in errors" :key="field">
                                                    <span v-for="msg in messages" :key="msg">{{ msg }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expense Number (Read-only) -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">Expense Number</label>
                                    <input
                                        type="text"
                                        class="input w-full bg-gray-100"
                                        :value="expense.expense_number"
                                        readonly
                                        disabled
                                    />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                                    <!-- Expense Date -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Expense Date <span class="text-danger">*</span>
                                        </label>
                                        <DatePicker
                                            v-model="form.expense_date"
                                            placeholder="Select date"
                                        />
                                        <span v-if="errors.expense_date" class="text-xs text-danger">
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
                                            :options="categories"
                                            placeholder="Select category"
                                            search-placeholder="Search categories..."
                                            clearable
                                        />
                                        <span v-if="errors.category_id" class="text-xs text-danger">
                                            {{ errors.category_id[0] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                                    <!-- Expense Account -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Expense Account <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.account_id"
                                            :options="accounts"
                                            placeholder="Select expense account"
                                            search-placeholder="Search accounts..."
                                            clearable
                                        />
                                        <span v-if="errors.account_id" class="text-xs text-danger">
                                            {{ errors.account_id[0] }}
                                        </span>
                                    </div>

                                    <!-- Paid From Account -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Paid From Account <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.paid_from_account_id"
                                            :options="paymentAccounts"
                                            placeholder="Select payment account"
                                            search-placeholder="Search accounts..."
                                            clearable
                                        />
                                        <span v-if="errors.paid_from_account_id" class="text-xs text-danger">
                                            {{ errors.paid_from_account_id[0] }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Payee Section -->
                                <div class="border-t pt-5 mb-5">
                                    <label class="form-label text-gray-700 mb-3">Payee</label>

                                    <!-- Payee Type Toggle -->
                                    <div class="flex gap-4 mb-4">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                v-model="payeeType"
                                                value="supplier"
                                                class="radio radio-sm"
                                            />
                                            <span class="text-sm text-gray-700">Select Supplier</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                v-model="payeeType"
                                                value="manual"
                                                class="radio radio-sm"
                                            />
                                            <span class="text-sm text-gray-700">Enter Manually</span>
                                        </label>
                                    </div>

                                    <!-- Supplier Select -->
                                    <div v-if="payeeType === 'supplier'">
                                        <SearchableSelect
                                            v-model="form.supplier_id"
                                            :options="suppliers"
                                            placeholder="Select supplier"
                                            search-placeholder="Search suppliers..."
                                            clearable
                                        />
                                    </div>

                                    <!-- Manual Payee Name -->
                                    <div v-else>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.payee_name"
                                            placeholder="Enter payee name"
                                        />
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="form-label text-gray-700">
                                        Description <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="input w-full"
                                        v-model="form.description"
                                        placeholder="Brief description of the expense"
                                    />
                                    <span v-if="errors.description" class="text-xs text-danger">
                                        {{ errors.description[0] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Payment Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                                    <!-- Currency -->
                                    <div>
                                        <label class="form-label text-gray-700">Currency</label>
                                        <SearchableSelect
                                            v-model="form.currency_code"
                                            :options="currencyOptions"
                                            placeholder="Select currency"
                                        />
                                    </div>

                                    <!-- Exchange Rate -->
                                    <div>
                                        <label class="form-label text-gray-700">Exchange Rate</label>
                                        <input
                                            type="number"
                                            class="input w-full"
                                            v-model.number="form.exchange_rate"
                                            step="0.0001"
                                            min="0"
                                        />
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <label class="form-label text-gray-700">Payment Method</label>
                                        <SearchableSelect
                                            v-model="form.payment_method"
                                            :options="paymentMethodOptions"
                                            placeholder="Select method"
                                            clearable
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                                    <!-- Amount -->
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Amount <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="btn btn-input">{{ form.currency_code }}</span>
                                            <input
                                                type="number"
                                                class="input w-full"
                                                v-model.number="form.amount"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <span v-if="errors.amount" class="text-xs text-danger">
                                            {{ errors.amount[0] }}
                                        </span>
                                    </div>

                                    <!-- Tax Amount -->
                                    <div>
                                        <label class="form-label text-gray-700">Tax Amount</label>
                                        <div class="input-group">
                                            <span class="btn btn-input">{{ form.currency_code }}</span>
                                            <input
                                                type="number"
                                                class="input w-full"
                                                v-model.number="form.tax_amount"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                            />
                                        </div>
                                    </div>

                                    <!-- Reference Number -->
                                    <div>
                                        <label class="form-label text-gray-700">Reference Number</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.reference_number"
                                            placeholder="Invoice/Receipt number"
                                        />
                                    </div>
                                </div>

                                <!-- Recurring Options -->
                                <div class="border-t pt-5">
                                    <div class="flex items-center gap-4 mb-4">
                                        <label class="switch switch-sm">
                                            <input
                                                v-model="form.is_recurring"
                                                type="checkbox"
                                                class="order-2"
                                            />
                                            <span class="switch-label order-1">Recurring Expense</span>
                                        </label>
                                    </div>

                                    <div v-if="form.is_recurring" class="w-full md:w-1/3">
                                        <label class="form-label text-gray-700">Recurring Frequency</label>
                                        <SearchableSelect
                                            v-model="form.recurring_frequency"
                                            :options="recurringFrequencyOptions"
                                            placeholder="Select frequency"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Attachments Card -->
                        <div v-if="existingAttachments.length > 0" class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-folder me-2 text-gray-500"></i>
                                    Current Attachments
                                </h3>
                                <span class="badge badge-sm badge-light">{{ existingAttachments.length }} files</span>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <div
                                        v-for="attachment in existingAttachments"
                                        :key="attachment.id"
                                        class="flex items-center justify-between p-3 border rounded-lg"
                                        :class="isMarkedForRemoval(attachment.id) ? 'bg-red-50 border-red-200' : 'bg-gray-50'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                                <i :class="[getFileIcon(attachment.file_type), 'text-primary text-lg']"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900" :class="{ 'line-through': isMarkedForRemoval(attachment.id) }">
                                                    {{ attachment.file_name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ formatFileSize(attachment.file_size) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a
                                                :href="attachment.url"
                                                target="_blank"
                                                class="btn btn-sm btn-icon btn-light"
                                                title="Download"
                                            >
                                                <i class="ki-outline ki-cloud-download"></i>
                                            </a>
                                            <button
                                                type="button"
                                                @click="toggleRemoveExisting(attachment.id)"
                                                class="btn btn-sm btn-icon"
                                                :class="isMarkedForRemoval(attachment.id) ? 'btn-success' : 'btn-light-danger'"
                                                :title="isMarkedForRemoval(attachment.id) ? 'Undo remove' : 'Remove'"
                                            >
                                                <i :class="isMarkedForRemoval(attachment.id) ? 'ki-outline ki-arrow-circle-left' : 'ki-outline ki-trash'"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="removeAttachments.length > 0" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div class="flex items-center gap-2 text-sm text-yellow-700">
                                        <i class="ki-filled ki-information-2"></i>
                                        <span>{{ removeAttachments.length }} file(s) will be removed when you save.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New Attachments Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-filled ki-cloud-add me-2 text-gray-500"></i>
                                    Add New Attachments
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- File Upload Area -->
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors">
                                    <input
                                        type="file"
                                        multiple
                                        @change="handleFileSelect"
                                        class="hidden"
                                        id="file-upload"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                                    />
                                    <label for="file-upload" class="cursor-pointer">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
                                                <i class="ki-filled ki-cloud-add text-primary text-2xl"></i>
                                            </div>
                                            <div>
                                                <span class="text-primary font-medium">Click to upload</span>
                                                <span class="text-gray-500"> or drag and drop</span>
                                            </div>
                                            <p class="text-xs text-gray-400">
                                                PDF, DOC, XLS, JPG, PNG (Max 10MB each)
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Selected New Files Preview -->
                                <div v-if="newAttachments.length > 0" class="mt-4 space-y-2">
                                    <p class="text-sm font-medium text-gray-700">New files to upload:</p>
                                    <div
                                        v-for="(file, index) in newAttachments"
                                        :key="index"
                                        class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                                <i :class="[getFileIcon(file.type), 'text-green-600 text-lg']"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ file.name }}</div>
                                                <div class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeNewAttachment(index)"
                                            class="btn btn-sm btn-icon btn-light-danger"
                                            title="Remove"
                                        >
                                            <i class="ki-outline ki-cross"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Additional Notes</h3>
                            </div>
                            <div class="card-body">
                                <textarea
                                    class="textarea w-full"
                                    v-model="form.notes"
                                    rows="4"
                                    placeholder="Internal notes about this expense..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Summary -->
                    <div class="lg:col-span-1">
                        <div class="card sticky top-5">
                            <div class="card-header">
                                <h3 class="card-title">Expense Summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <!-- Amount -->
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Amount</span>
                                        <span class="font-medium">{{ formatCurrency(form.amount) }}</span>
                                    </div>

                                    <!-- Tax -->
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Tax</span>
                                        <span class="font-medium">{{ formatCurrency(form.tax_amount) }}</span>
                                    </div>

                                    <!-- Divider -->
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-semibold text-gray-900">Total</span>
                                            <span class="text-xl font-bold text-primary">{{ formatCurrency(totalAmount) }}</span>
                                        </div>
                                    </div>

                                    <!-- Currency Note -->
                                    <div v-if="form.currency_code !== 'IDR'" class="p-3 bg-blue-50 rounded-lg">
                                        <div class="text-xs text-blue-700">
                                            <i class="ki-filled ki-information-2 me-1"></i>
                                            Exchange rate: 1 {{ form.currency_code }} = {{ form.exchange_rate }} IDR
                                        </div>
                                    </div>

                                    <!-- Recurring Badge -->
                                    <div v-if="form.is_recurring" class="p-3 bg-purple-50 rounded-lg">
                                        <div class="flex items-center gap-2 text-sm text-purple-700">
                                            <i class="ki-filled ki-arrows-circle"></i>
                                            <span>Recurring {{ form.recurring_frequency }}</span>
                                        </div>
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
                                        <span v-else>
                                            <i class="ki-filled ki-check me-2"></i>
                                            Update Expense
                                        </span>
                                    </button>

                                    <Link
                                        :href="`/finance/expenses/${expense.id}`"
                                        class="btn btn-light w-full"
                                    >
                                        <i class="ki-outline ki-arrow-left me-2"></i>
                                        Cancel
                                    </Link>
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
.input-group .btn-input {
    border-color: #d8d8d8;
    background-color: #f9fafb;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
