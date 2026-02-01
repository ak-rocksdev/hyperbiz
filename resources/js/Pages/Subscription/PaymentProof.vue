<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/Metronic/DatePicker.vue';
import CurrencyInput from '@/Components/Metronic/CurrencyInput.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Lightbox modal state
const showLightbox = ref(false);
const lightboxImage = ref(null);
const lightboxTitle = ref('');

// Open lightbox modal
const openLightbox = (proof) => {
    if (proof.file_url && isImageFile(proof.file_type)) {
        lightboxImage.value = proof.file_url;
        lightboxTitle.value = proof.file_name || 'Payment Proof';
        showLightbox.value = true;
    }
};

// Close lightbox modal
const closeLightbox = () => {
    showLightbox.value = false;
    lightboxImage.value = null;
    lightboxTitle.value = '';
};

// Check if file is an image (file_type stores extension: jpg, jpeg, png, pdf)
const isImageFile = (fileType) => {
    if (!fileType) return false;
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return imageExtensions.includes(fileType.toLowerCase());
};

// Props from controller
const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    bankAccounts: {
        type: Array,
        default: () => [],
    },
    existingProofs: {
        type: Array,
        default: () => [],
    },
    maxFileSize: {
        type: Number,
        default: 5120, // 5MB in KB
    },
    allowedTypes: {
        type: Array,
        default: () => ['image/jpeg', 'image/png', 'application/pdf'],
    },
});

// Form state
const form = ref({
    bank_name: '',
    account_name: '',
    account_number: '',
    transfer_date: new Date().toISOString().split('T')[0],
    transfer_amount: null,
    notes: '',
});

const selectedFile = ref(null);
const filePreview = ref(null);
const isDragging = ref(false);
const errors = ref({});
const isSubmitting = ref(false);

// Bank options for select (from bank accounts + custom option)
const bankOptions = computed(() => {
    const banks = props.bankAccounts.map(acc => acc.bank_name);
    const uniqueBanks = [...new Set(banks)];
    return uniqueBanks;
});

// Get invoice status badge class
const getStatusBadgeClass = (status) => {
    const classes = {
        paid: 'badge-success',
        pending: 'badge-warning',
        overdue: 'badge-danger',
        cancelled: 'badge-secondary',
        processing: 'badge-info',
    };
    return classes[status?.toLowerCase()] || 'badge-secondary';
};

// Get proof status badge class
const getProofStatusBadgeClass = (status) => {
    const classes = {
        approved: 'badge-success',
        pending: 'badge-warning',
        rejected: 'badge-danger',
    };
    return classes[status?.toLowerCase()] || 'badge-secondary';
};

// Format currency
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Format file size
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Map of file extensions to MIME types
const mimeTypeMap = {
    'jpg': 'image/jpeg',
    'jpeg': 'image/jpeg',
    'png': 'image/png',
    'pdf': 'application/pdf',
};

// Get allowed MIME types from extensions
const allowedMimeTypes = computed(() => {
    return props.allowedTypes.map(ext => mimeTypeMap[ext.toLowerCase()] || ext).filter(Boolean);
});

// Get allowed file extensions for display
const allowedExtensions = computed(() => {
    return props.allowedTypes.map(ext => ext.toUpperCase()).join(', ');
});

// Get max file size in MB
const maxFileSizeMB = computed(() => {
    return (props.maxFileSize / 1024).toFixed(0);
});

// Copy to clipboard
const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            icon: 'success',
            title: 'Copied to clipboard',
        });
    } catch (err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            icon: 'success',
            title: 'Copied to clipboard',
        });
    }
};

// Handle file selection
const handleFileSelect = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        processFile(files[0]);
    }
    // Reset input
    event.target.value = '';
};

// Handle drag events
const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        processFile(files[0]);
    }
};

// Process the selected file
const processFile = (file) => {
    // Get file extension from filename
    const fileExtension = file.name.split('.').pop()?.toLowerCase();

    // Validate file type by both MIME type and extension
    const isValidMimeType = allowedMimeTypes.value.includes(file.type);
    const isValidExtension = props.allowedTypes.map(t => t.toLowerCase()).includes(fileExtension);

    if (!isValidMimeType && !isValidExtension) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid File Type',
            text: `Please upload a file of type: ${allowedExtensions.value.toLowerCase()}`,
        });
        return;
    }

    // Validate file size
    const maxSizeBytes = props.maxFileSize * 1024;
    if (file.size > maxSizeBytes) {
        Swal.fire({
            icon: 'error',
            title: 'File Too Large',
            text: `Maximum file size is ${maxFileSizeMB.value}MB`,
        });
        return;
    }

    selectedFile.value = file;

    // Create preview for images
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        filePreview.value = null;
    }

    errors.value.file = null;
};

// Remove selected file
const removeFile = () => {
    if (filePreview.value) {
        URL.revokeObjectURL(filePreview.value);
    }
    selectedFile.value = null;
    filePreview.value = null;
};

// Get file icon based on type (handles both MIME types and extensions)
const getFileIcon = (type) => {
    if (!type) return 'ki-filled ki-file';
    const lowerType = type.toLowerCase();
    // Check for MIME types (for file upload preview)
    if (lowerType.startsWith('image/')) return 'ki-filled ki-picture';
    if (lowerType === 'application/pdf') return 'ki-filled ki-file-sheet';
    // Check for file extensions (for existing proofs from database)
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (imageExtensions.includes(lowerType)) return 'ki-filled ki-picture';
    if (lowerType === 'pdf') return 'ki-filled ki-file-sheet';
    return 'ki-filled ki-file';
};

// Validate form
const validateForm = () => {
    errors.value = {};

    if (!selectedFile.value) {
        errors.value.file = ['Please upload payment proof'];
    }

    if (!form.value.bank_name || !form.value.bank_name.trim()) {
        errors.value.bank_name = ['Bank name is required'];
    }

    if (!form.value.account_name || !form.value.account_name.trim()) {
        errors.value.account_name = ['Account holder name is required'];
    }

    if (!form.value.account_number || !form.value.account_number.trim()) {
        errors.value.account_number = ['Account number is required'];
    }

    if (!form.value.transfer_date) {
        errors.value.transfer_date = ['Transfer date is required'];
    }

    if (form.value.transfer_amount && form.value.transfer_amount < 0) {
        errors.value.transfer_amount = ['Transfer amount cannot be negative'];
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
        formData.append('file', selectedFile.value);
        formData.append('bank_name', form.value.bank_name);
        formData.append('account_name', form.value.account_name);
        formData.append('account_number', form.value.account_number);
        formData.append('transfer_date', form.value.transfer_date);
        if (form.value.transfer_amount) {
            formData.append('transfer_amount', form.value.transfer_amount);
        }
        if (form.value.notes) {
            formData.append('notes', form.value.notes);
        }

        const response = await axios.post(`/subscription/payment-proof/${props.invoice.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        Swal.fire({
            icon: 'success',
            title: 'Payment Proof Uploaded',
            text: response.data.message || 'Your payment proof has been submitted for verification.',
        }).then(() => {
            // Refresh the page to show the new proof
            router.reload();
        });

        // Reset form
        removeFile();
        form.value = {
            bank_name: '',
            account_name: '',
            account_number: '',
            transfer_date: new Date().toISOString().split('T')[0],
            transfer_amount: null,
            notes: '',
        };
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: error.response?.data?.message || 'Failed to upload payment proof. Please try again.',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Check if can upload new proof (no pending proof exists)
const canUploadNewProof = computed(() => {
    if (!props.existingProofs || props.existingProofs.length === 0) return true;
    // Can upload if no pending proofs and invoice is not paid
    const hasPendingProof = props.existingProofs.some(p => p.status?.toLowerCase() === 'pending');
    const hasApprovedProof = props.existingProofs.some(p => p.status?.toLowerCase() === 'approved');
    return !hasPendingProof && !hasApprovedProof && props.invoice.status?.toLowerCase() !== 'paid';
});

// Check if has rejected proofs
const hasRejectedProofs = computed(() => {
    return props.existingProofs.some(p => p.status?.toLowerCase() === 'rejected');
});
</script>

<template>
    <AppLayout title="Upload Payment Proof">
        <div class="container-fixed py-5">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 text-sm mb-6">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <Link href="/subscription" class="text-gray-500 hover:text-primary transition-colors">
                    Subscription
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Payment Proof</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Upload Payment Proof</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Submit your bank transfer proof for verification
                    </p>
                </div>
                <Link href="/subscription" class="btn btn-light">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Back to Subscription
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Invoice Summary Card -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-document text-gray-500"></i>
                                Invoice Summary
                            </h3>
                            <span
                                class="badge badge-sm"
                                :class="getStatusBadgeClass(invoice.status)"
                            >
                                {{ invoice.status_label || invoice.status }}
                            </span>
                        </div>
                        <div class="card-body p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Invoice Number</span>
                                    <p class="font-semibold text-gray-900 mt-1">{{ invoice.invoice_number }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Plan</span>
                                    <p class="font-semibold text-gray-900 mt-1">{{ invoice.plan_name }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Amount</span>
                                    <p class="font-bold text-primary text-lg mt-1">{{ invoice.formatted_amount || formatCurrency(invoice.amount) }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Due Date</span>
                                    <p class="font-semibold mt-1" :class="new Date(invoice.due_date) < new Date() && invoice.status !== 'paid' ? 'text-danger' : 'text-gray-900'">
                                        {{ formatDate(invoice.due_date) }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="invoice.billing_cycle" class="mt-4 pt-4 border-t border-gray-100">
                                <span class="badge badge-sm badge-light">
                                    {{ invoice.billing_cycle === 'yearly' ? 'Yearly' : 'Monthly' }} Billing
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Account Details -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-bank text-gray-500"></i>
                                Transfer to Bank Account
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <p class="text-sm text-gray-600 mb-4">
                                Please transfer the exact amount to one of the following bank accounts:
                            </p>
                            <div class="space-y-4">
                                <div
                                    v-for="(account, index) in bankAccounts"
                                    :key="index"
                                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                                >
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Bank Name</span>
                                            <p class="font-semibold text-gray-900">{{ account.bank_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Account Name</span>
                                            <p class="font-semibold text-gray-900">{{ account.account_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Account Number</span>
                                            <div class="flex items-center gap-2">
                                                <p class="font-semibold text-gray-900 font-mono">{{ account.account_number }}</p>
                                                <button
                                                    type="button"
                                                    @click="copyToClipboard(account.account_number)"
                                                    class="btn btn-icon btn-xs btn-light hover:btn-primary"
                                                    title="Copy account number"
                                                >
                                                    <i class="ki-filled ki-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Important Notes -->
                            <div class="mt-4 p-4 bg-info/10 border border-info/20 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <i class="ki-filled ki-information-2 text-info mt-0.5"></i>
                                    <div class="text-sm text-info">
                                        <p class="font-semibold mb-1">Important:</p>
                                        <ul class="list-disc list-inside space-y-1 text-info/80">
                                            <li>Transfer the exact amount: <strong>{{ invoice.formatted_amount || formatCurrency(invoice.amount) }}</strong></li>
                                            <li>Upload your payment proof after completing the transfer</li>
                                            <li>Verification may take 1-2 business days</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Proofs Section -->
                    <div v-if="existingProofs && existingProofs.length > 0" class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-folder text-gray-500"></i>
                                Submitted Payment Proofs
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table table-border">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="min-w-[200px]">File</th>
                                            <th class="w-[120px] text-center">Transfer Date</th>
                                            <th class="w-[140px] text-end">Amount</th>
                                            <th class="w-[100px] text-center">Status</th>
                                            <th class="w-[120px] text-center">Uploaded</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="proof in existingProofs" :key="proof.id" class="hover:bg-gray-50/50">
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <!-- Image thumbnail or file icon -->
                                                    <div
                                                        class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0 cursor-pointer hover:ring-2 hover:ring-primary/50 transition-all"
                                                        :class="{ 'cursor-pointer': isImageFile(proof.file_type) }"
                                                        @click="isImageFile(proof.file_type) ? openLightbox(proof) : null"
                                                        :title="isImageFile(proof.file_type) ? 'Click to view larger' : ''"
                                                    >
                                                        <!-- Show actual image if it's an image file -->
                                                        <img
                                                            v-if="isImageFile(proof.file_type) && proof.file_url"
                                                            :src="proof.file_url"
                                                            :alt="proof.file_name"
                                                            class="w-full h-full object-cover"
                                                        />
                                                        <!-- Show icon for non-image files -->
                                                        <i v-else :class="[getFileIcon(proof.file_type), 'text-gray-500 text-lg']"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <!-- Clickable filename for images (opens lightbox), link for others -->
                                                        <button
                                                            v-if="isImageFile(proof.file_type) && proof.file_url"
                                                            type="button"
                                                            @click="openLightbox(proof)"
                                                            class="text-sm font-medium text-primary hover:underline truncate block max-w-[200px] text-left"
                                                        >
                                                            {{ proof.file_name }}
                                                        </button>
                                                        <a
                                                            v-else-if="proof.file_url"
                                                            :href="proof.file_url"
                                                            target="_blank"
                                                            class="text-sm font-medium text-primary hover:underline truncate block max-w-[200px]"
                                                        >
                                                            {{ proof.file_name }}
                                                        </a>
                                                        <span v-else class="text-sm font-medium text-gray-900 truncate block max-w-[200px]">
                                                            {{ proof.file_name }}
                                                        </span>
                                                        <!-- Rejection reason -->
                                                        <p
                                                            v-if="proof.status?.toLowerCase() === 'rejected' && proof.rejection_reason"
                                                            class="text-xs text-danger mt-1"
                                                        >
                                                            <i class="ki-filled ki-information-2 me-1"></i>
                                                            {{ proof.rejection_reason }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-sm text-gray-600">{{ formatDate(proof.transfer_date) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-sm font-semibold text-gray-900">
                                                    {{ proof.formatted_transfer_amount || formatCurrency(proof.transfer_amount) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge badge-sm"
                                                    :class="proof.status_badge || getProofStatusBadgeClass(proof.status)"
                                                >
                                                    {{ proof.status_label || proof.status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-sm text-gray-500">{{ formatDate(proof.created_at) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form Section -->
                    <div v-if="canUploadNewProof" class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-cloud-add text-gray-500"></i>
                                {{ hasRejectedProofs ? 'Re-upload Payment Proof' : 'Upload Payment Proof' }}
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <form @submit.prevent="submitForm">
                                <!-- File Upload Area -->
                                <div class="mb-6">
                                    <label class="form-label text-gray-700 mb-3">
                                        Payment Proof File <span class="text-danger">*</span>
                                    </label>

                                    <!-- Upload Dropzone -->
                                    <div
                                        v-if="!selectedFile"
                                        @dragover="handleDragOver"
                                        @dragleave="handleDragLeave"
                                        @drop="handleDrop"
                                        class="border-2 border-dashed rounded-lg p-8 text-center transition-all cursor-pointer"
                                        :class="[
                                            isDragging
                                                ? 'border-primary bg-primary/5'
                                                : errors.file
                                                    ? 'border-danger bg-danger/5'
                                                    : 'border-gray-300 hover:border-primary hover:bg-gray-50'
                                        ]"
                                    >
                                        <input
                                            type="file"
                                            id="file-upload"
                                            :accept="allowedMimeTypes.join(',')"
                                            class="hidden"
                                            @change="handleFileSelect"
                                        />
                                        <label for="file-upload" class="cursor-pointer">
                                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                                <i class="ki-filled ki-cloud-add text-4xl text-gray-400"></i>
                                            </div>
                                            <p class="text-gray-700 font-medium mb-1">
                                                Click to upload or drag and drop
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ allowedExtensions }} (Max {{ maxFileSizeMB }}MB)
                                            </p>
                                        </label>
                                    </div>

                                    <!-- File Preview -->
                                    <div v-else class="border rounded-lg p-4 bg-gray-50">
                                        <div class="flex items-start gap-4">
                                            <!-- Image Preview or File Icon -->
                                            <div class="flex-shrink-0">
                                                <div
                                                    v-if="filePreview"
                                                    class="w-24 h-24 rounded-lg overflow-hidden border border-gray-200 bg-white"
                                                >
                                                    <img
                                                        :src="filePreview"
                                                        alt="Preview"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <div
                                                    v-else
                                                    class="w-24 h-24 rounded-lg bg-gray-200 flex items-center justify-center"
                                                >
                                                    <i :class="[getFileIcon(selectedFile?.type), 'text-gray-500 text-3xl']"></i>
                                                </div>
                                            </div>

                                            <!-- File Info -->
                                            <div class="flex-grow min-w-0">
                                                <p class="font-medium text-gray-900 truncate">
                                                    {{ selectedFile.name }}
                                                </p>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    {{ formatFileSize(selectedFile.size) }}
                                                </p>
                                                <div class="flex items-center gap-2 mt-3">
                                                    <span class="badge badge-sm badge-success">
                                                        <i class="ki-filled ki-check me-1"></i>
                                                        Ready to upload
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Remove Button -->
                                            <button
                                                type="button"
                                                @click="removeFile"
                                                class="btn btn-icon btn-sm btn-light hover:btn-danger"
                                                title="Remove file"
                                            >
                                                <i class="ki-filled ki-cross"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <span v-if="errors.file" class="text-xs text-danger mt-2 block">
                                        {{ errors.file[0] }}
                                    </span>
                                </div>

                                <!-- Transfer Details Form -->
                                <div class="border-t border-gray-200 pt-6">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">
                                        <i class="ki-filled ki-notepad-edit me-2 text-gray-500"></i>
                                        Bank Transfer Details
                                    </h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Bank Name -->
                                        <div>
                                            <label class="form-label text-gray-700">
                                                Bank Name <span class="text-danger">*</span>
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    class="input w-full"
                                                    v-model="form.bank_name"
                                                    placeholder="e.g., BCA, Mandiri, BNI"
                                                    list="bank-suggestions"
                                                />
                                                <datalist id="bank-suggestions">
                                                    <option v-for="bank in bankOptions" :key="bank" :value="bank"></option>
                                                </datalist>
                                            </div>
                                            <span v-if="errors.bank_name" class="text-xs text-danger mt-1">
                                                {{ errors.bank_name[0] }}
                                            </span>
                                        </div>

                                        <!-- Account Holder Name -->
                                        <div>
                                            <label class="form-label text-gray-700">
                                                Account Holder Name <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                class="input w-full"
                                                v-model="form.account_name"
                                                placeholder="Your name on bank account"
                                            />
                                            <span v-if="errors.account_name" class="text-xs text-danger mt-1">
                                                {{ errors.account_name[0] }}
                                            </span>
                                        </div>

                                        <!-- Account Number -->
                                        <div>
                                            <label class="form-label text-gray-700">
                                                Account Number <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                class="input w-full"
                                                v-model="form.account_number"
                                                placeholder="Your bank account number"
                                            />
                                            <span v-if="errors.account_number" class="text-xs text-danger mt-1">
                                                {{ errors.account_number[0] }}
                                            </span>
                                        </div>

                                        <!-- Transfer Date -->
                                        <div>
                                            <label class="form-label text-gray-700">
                                                Transfer Date <span class="text-danger">*</span>
                                            </label>
                                            <DatePicker
                                                v-model="form.transfer_date"
                                                placeholder="Select transfer date"
                                                :maxDate="new Date().toISOString().split('T')[0]"
                                            />
                                            <span v-if="errors.transfer_date" class="text-xs text-danger mt-1">
                                                {{ errors.transfer_date[0] }}
                                            </span>
                                        </div>

                                        <!-- Transfer Amount -->
                                        <div class="md:col-span-2">
                                            <label class="form-label text-gray-700">
                                                Transfer Amount <span class="text-gray-400">(optional)</span>
                                            </label>
                                            <CurrencyInput
                                                v-model="form.transfer_amount"
                                                prefix="Rp"
                                                :min="0"
                                                placeholder="0"
                                            />
                                            <span class="text-xs text-gray-500 mt-1">
                                                Invoice amount: {{ invoice.formatted_amount || formatCurrency(invoice.amount) }}
                                            </span>
                                            <span v-if="errors.transfer_amount" class="text-xs text-danger mt-1 block">
                                                {{ errors.transfer_amount[0] }}
                                            </span>
                                        </div>

                                        <!-- Notes -->
                                        <div class="md:col-span-2">
                                            <label class="form-label text-gray-700">
                                                Notes <span class="text-gray-400">(optional)</span>
                                            </label>
                                            <textarea
                                                class="textarea w-full"
                                                v-model="form.notes"
                                                rows="2"
                                                placeholder="Additional notes or reference numbers"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <button
                                        type="submit"
                                        class="btn btn-primary w-full md:w-auto"
                                        :disabled="isSubmitting"
                                    >
                                        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-loading animate-spin"></i>
                                            Uploading...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <i class="ki-filled ki-cloud-add"></i>
                                            Submit Payment Proof
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Cannot Upload Message -->
                    <div v-else class="card border-info/30">
                        <div class="card-body p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full bg-info/20 flex items-center justify-center">
                                        <i class="ki-filled ki-information-2 text-info text-2xl"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900 mb-1">
                                        {{ invoice.status === 'paid' ? 'Invoice Already Paid' : 'Payment Proof Under Review' }}
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        {{ invoice.status === 'paid'
                                            ? 'This invoice has been paid. Thank you for your payment!'
                                            : 'Your payment proof is currently being reviewed. We will notify you once it has been verified.'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="card sticky top-5">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title">Payment Summary</h3>
                        </div>
                        <div class="card-body p-6">
                            <!-- Invoice Details -->
                            <div class="space-y-4 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Invoice</span>
                                    <span class="font-medium text-gray-900">{{ invoice.invoice_number }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Plan</span>
                                    <span class="font-medium text-gray-900">{{ invoice.plan_name }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Due Date</span>
                                    <span class="font-medium" :class="new Date(invoice.due_date) < new Date() && invoice.status !== 'paid' ? 'text-danger' : 'text-gray-900'">
                                        {{ formatDate(invoice.due_date) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Status</span>
                                    <span
                                        class="badge badge-sm"
                                        :class="getStatusBadgeClass(invoice.status)"
                                    >
                                        {{ invoice.status_label || invoice.status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Total Amount -->
                            <div class="border-t border-gray-200 pt-4 mb-6">
                                <div class="flex items-center justify-between">
                                    <span class="text-base font-semibold text-gray-900">Amount Due</span>
                                    <span class="text-2xl font-bold text-primary">
                                        {{ invoice.formatted_amount || formatCurrency(invoice.amount) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Status Progress -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="invoice.status === 'paid' ? 'bg-success/20' : 'bg-primary/20'"
                                    >
                                        <i class="ki-filled ki-check text-success" v-if="invoice.status === 'paid'"></i>
                                        <span v-else class="text-primary font-semibold text-sm">1</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Invoice Created</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(invoice.created_at) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="existingProofs?.some(p => p.status?.toLowerCase() === 'pending' || p.status?.toLowerCase() === 'approved') ? 'bg-success/20' : 'bg-gray-100'"
                                    >
                                        <i class="ki-filled ki-check text-success" v-if="existingProofs?.some(p => p.status?.toLowerCase() === 'pending' || p.status?.toLowerCase() === 'approved')"></i>
                                        <span v-else class="text-gray-400 font-semibold text-sm">2</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium" :class="existingProofs?.length > 0 ? 'text-gray-900' : 'text-gray-400'">
                                            Payment Proof Uploaded
                                        </p>
                                        <p class="text-xs text-gray-500">Upload your transfer receipt</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="invoice.status === 'paid' ? 'bg-success/20' : 'bg-gray-100'"
                                    >
                                        <i class="ki-filled ki-check text-success" v-if="invoice.status === 'paid'"></i>
                                        <span v-else class="text-gray-400 font-semibold text-sm">3</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium" :class="invoice.status === 'paid' ? 'text-gray-900' : 'text-gray-400'">
                                            Payment Verified
                                        </p>
                                        <p class="text-xs text-gray-500">1-2 business days</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Section -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <p class="text-sm text-gray-500 mb-3">Need assistance?</p>
                                <a
                                    href="mailto:support@hyperbiz.com"
                                    class="btn btn-light w-full"
                                >
                                    <i class="ki-filled ki-message-text me-2"></i>
                                    Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox Modal for Image Preview -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showLightbox"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                    @click.self="closeLightbox"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

                    <!-- Modal Content -->
                    <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <h3 class="text-white font-medium text-lg truncate pr-4">
                                {{ lightboxTitle }}
                            </h3>
                            <button
                                type="button"
                                @click="closeLightbox"
                                class="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
                            >
                                <i class="ki-filled ki-cross text-xl"></i>
                            </button>
                        </div>

                        <!-- Image Container -->
                        <div class="relative flex-1 overflow-hidden rounded-lg bg-black/50 flex items-center justify-center">
                            <img
                                v-if="lightboxImage"
                                :src="lightboxImage"
                                :alt="lightboxTitle"
                                class="max-w-full max-h-[75vh] object-contain rounded-lg shadow-2xl"
                            />
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-center gap-3 mt-4 relative z-10">
                            <a
                                :href="lightboxImage"
                                target="_blank"
                                class="btn btn-sm btn-light"
                            >
                                <i class="ki-filled ki-maximize me-2"></i>
                                Open in New Tab
                            </a>
                            <a
                                :href="lightboxImage"
                                download
                                class="btn btn-sm btn-primary"
                            >
                                <i class="ki-filled ki-file-down me-2"></i>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
