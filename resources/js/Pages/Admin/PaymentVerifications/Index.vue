<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import debounce from 'lodash/debounce';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    proofs: {
        type: Object,
        default: () => ({
            data: [],
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
            links: [],
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            pending: 0,
            approved: 0,
            rejected: 0,
            total: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Filter state
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// Status dropdown state
const showStatusDropdown = ref(false);

// Get current status label
const currentStatusLabel = computed(() => {
    const option = statusOptions.find(opt => opt.value === statusFilter.value);
    return option ? option.label : 'All Status';
});

// Get current status icon and color
const currentStatusStyle = computed(() => {
    const styles = {
        '': { icon: 'ki-filled ki-element-11', color: 'text-gray-600' },
        'all': { icon: 'ki-filled ki-element-11', color: 'text-gray-600' },
        'pending': { icon: 'ki-filled ki-time', color: 'text-warning' },
        'approved': { icon: 'ki-filled ki-check-circle', color: 'text-success' },
        'rejected': { icon: 'ki-filled ki-cross-circle', color: 'text-danger' },
    };
    return styles[statusFilter.value] || styles[''];
});

// Toggle status dropdown
const toggleStatusDropdown = () => {
    showStatusDropdown.value = !showStatusDropdown.value;
};

// Select status option
const selectStatus = (value) => {
    statusFilter.value = value;
    showStatusDropdown.value = false;
};

// Close dropdown when clicking outside
const closeStatusDropdown = () => {
    showStatusDropdown.value = false;
};

// Modal state
const showViewModal = ref(false);
const showRejectModal = ref(false);
const selectedProof = ref(null);
const proofDetails = ref(null);
const loadingDetails = ref(false);

// Reject form state
const rejectReason = ref('');
const rejectErrors = ref({});
const rejecting = ref(false);

// Approving state
const approving = ref(false);

// Debounced search
const performSearch = debounce(() => {
    router.get(route('admin.payment-verifications.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch for filter changes
watch([search, statusFilter], () => {
    performSearch();
});

// Clear filters
const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
};

// Check if filters are active (beyond default)
const hasActiveFilters = computed(() => search.value || statusFilter.value);

// Get status badge class
const getStatusClass = (status) => {
    const classes = {
        'pending': 'badge-warning',
        'approved': 'badge-success',
        'rejected': 'badge-danger',
    };
    return classes[status] || 'badge-light';
};

// Get status icon
const getStatusIcon = (status) => {
    const icons = {
        'pending': 'ki-filled ki-time',
        'approved': 'ki-filled ki-check-circle',
        'rejected': 'ki-filled ki-cross-circle',
    };
    return icons[status] || 'ki-filled ki-information-2';
};

// Status options for filter
const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
];

// Custom directive for clicking outside
const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        document.addEventListener('click', el._clickOutside);
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutside);
    },
};

// Format currency (IDR)
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// View proof details
const viewProof = async (proof) => {
    selectedProof.value = proof;
    showViewModal.value = true;
    loadingDetails.value = true;
    proofDetails.value = null;

    try {
        const response = await axios.get(route('admin.payment-verifications.show', proof.id));
        proofDetails.value = response.data.proof;
    } catch (error) {
        console.error('Error loading proof details:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load payment proof details',
        });
        closeViewModal();
    } finally {
        loadingDetails.value = false;
    }
};

// Close view modal
const closeViewModal = () => {
    showViewModal.value = false;
    selectedProof.value = null;
    proofDetails.value = null;
};

// Open reject modal
const openRejectModal = (proof = null) => {
    if (proof) {
        selectedProof.value = proof;
    }
    showRejectModal.value = true;
    rejectReason.value = '';
    rejectErrors.value = {};
};

// Close reject modal
const closeRejectModal = () => {
    showRejectModal.value = false;
    rejectReason.value = '';
    rejectErrors.value = {};
    if (!showViewModal.value) {
        selectedProof.value = null;
    }
};

// Approve payment proof
const approveProof = async (proof = null) => {
    const proofToApprove = proof || selectedProof.value;
    if (!proofToApprove) return;

    // Use detailed proof data if available, fallback to list data
    const proofData = proofDetails.value || proofToApprove;
    const invoiceAmount = proofData.invoice?.amount || 0;
    const transferAmount = proofData.transfer_amount;
    const formattedInvoiceAmount = proofData.invoice?.formatted_amount || formatCurrency(invoiceAmount);
    const formattedTransferAmount = proofData.formatted_transfer_amount || (transferAmount ? formatCurrency(transferAmount) : null);

    // Check for amount mismatch
    const hasAmountMismatch = transferAmount && Number(transferAmount) !== Number(invoiceAmount);
    const noAmountProvided = !transferAmount;

    const mismatchWarning = hasAmountMismatch
        ? `<div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
               <div class="flex items-center gap-2 text-red-700 font-semibold text-sm mb-1">
                   <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                   Amount Mismatch
               </div>
               <p class="text-sm text-red-600">Transfer amount (${formattedTransferAmount}) does not match invoice amount (${formattedInvoiceAmount})</p>
           </div>`
        : '';

    const amountNote = noAmountProvided
        ? `<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
               <p class="text-sm text-blue-700">No transfer amount was provided by the tenant. Please verify the full amount from the proof image and your bank account.</p>
           </div>`
        : '';

    // Close the view modal so SweetAlert appears on top (dialog top-layer issue)
    const wasModalOpen = showViewModal.value;
    if (wasModalOpen) {
        showViewModal.value = false;
    }

    const result = await Swal.fire({
        title: 'Approve Payment?',
        html: `
            <div class="text-left">
                ${mismatchWarning}
                ${amountNote}
                <div class="bg-gray-50 rounded-lg p-3 text-sm mb-3">
                    <p><strong>Company:</strong> ${proofToApprove.company?.name}</p>
                    <p><strong>Invoice:</strong> ${proofToApprove.invoice?.invoice_number}</p>
                    <p><strong>Invoice Amount:</strong> ${formattedInvoiceAmount}</p>
                    ${formattedTransferAmount ? `<p><strong>Transfer Amount:</strong> ${formattedTransferAmount}</p>` : ''}
                </div>
                <p class="text-sm text-gray-600 mb-3">This will activate the company's subscription.</p>
                <label class="flex items-start gap-2 cursor-pointer p-3 bg-green-50 border border-green-200 rounded-lg">
                    <input type="checkbox" id="swal-verify-checkbox" class="mt-0.5 rounded border-gray-300">
                    <span class="text-sm text-gray-700">I have verified that the payment of <strong>${formattedInvoiceAmount}</strong> has been received in the bank account.</span>
                </label>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const checkbox = document.getElementById('swal-verify-checkbox');
            if (!checkbox || !checkbox.checked) {
                Swal.showValidationMessage('Please confirm that you have verified the payment');
                return false;
            }
            return true;
        },
    });

    if (!result.isConfirmed) {
        // Reopen modal if cancelled
        if (wasModalOpen) {
            showViewModal.value = true;
        }
        return;
    }

    approving.value = true;

    try {
        const response = await axios.post(route('admin.payment-verifications.approve', proofToApprove.id));

        if (response.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Approved!',
                text: response.data.message,
            });

            // Close modal and reload
            selectedProof.value = null;
            proofDetails.value = null;
            router.reload({ only: ['proofs', 'stats'] });
        }
    } catch (error) {
        console.error('Error approving proof:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to approve payment proof',
        });
    } finally {
        approving.value = false;
    }
};

// Reject payment proof
const rejectProof = async () => {
    if (!selectedProof.value) return;

    rejectErrors.value = {};

    if (!rejectReason.value.trim()) {
        rejectErrors.value.reason = ['Rejection reason is required'];
        return;
    }

    rejecting.value = true;

    try {
        const response = await axios.post(route('admin.payment-verifications.reject', selectedProof.value.id), {
            reason: rejectReason.value,
        });

        if (response.data.success) {
            // Close modals before showing toast (dialog top-layer issue)
            closeRejectModal();
            closeViewModal();

            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Rejected',
                text: response.data.message,
            });

            router.reload({ only: ['proofs', 'stats'] });
        }
    } catch (error) {
        console.error('Error rejecting proof:', error);
        if (error.response?.data?.errors) {
            rejectErrors.value = error.response.data.errors;
        } else {
            // Close modals before showing error (dialog top-layer issue)
            closeRejectModal();
            closeViewModal();

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to reject payment proof',
            });
        }
    } finally {
        rejecting.value = false;
    }
};

// Check if file is an image
const isImage = (fileType) => {
    return ['jpg', 'jpeg', 'png'].includes(fileType?.toLowerCase());
};

// Check if file is PDF
const isPdf = (fileType) => {
    return fileType?.toLowerCase() === 'pdf';
};

// Image zoom state
const imageZoom = ref(1);
const imageRotation = ref(0);
const imagePan = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const imageViewerRef = ref(null);

// Zoom controls
const zoomIn = () => {
    imageZoom.value = Math.min(imageZoom.value + 0.25, 5);
};

const zoomOut = () => {
    imageZoom.value = Math.max(imageZoom.value - 0.25, 0.5);
};

const resetZoom = () => {
    imageZoom.value = 1;
    imageRotation.value = 0;
    imagePan.value = { x: 0, y: 0 };
};

const rotateImage = () => {
    imageRotation.value = (imageRotation.value + 90) % 360;
};

// Handle mouse wheel zoom
const handleWheel = (event) => {
    event.preventDefault();
    const delta = event.deltaY > 0 ? -0.1 : 0.1;
    imageZoom.value = Math.max(0.5, Math.min(5, imageZoom.value + delta));
};

// Handle drag start
const handleDragStart = (event) => {
    if (imageZoom.value > 1) {
        isDragging.value = true;
        dragStart.value = {
            x: event.clientX - imagePan.value.x,
            y: event.clientY - imagePan.value.y
        };
    }
};

// Handle drag move
const handleDragMove = (event) => {
    if (isDragging.value && imageZoom.value > 1) {
        imagePan.value = {
            x: event.clientX - dragStart.value.x,
            y: event.clientY - dragStart.value.y
        };
    }
};

// Handle drag end
const handleDragEnd = () => {
    isDragging.value = false;
};

// Open image in fullscreen lightbox
const showFullscreenViewer = ref(false);
const fullscreenImageUrl = ref(null);
const fullscreenFileName = ref(null);

const openFullscreen = () => {
    // Store image data before closing modal
    fullscreenImageUrl.value = proofDetails.value?.file_url;
    fullscreenFileName.value = proofDetails.value?.file_name;
    showFullscreenViewer.value = true;
    showViewModal.value = false; // Hide the modal
    resetZoom();
};

const closeFullscreen = () => {
    showFullscreenViewer.value = false;
    showViewModal.value = true; // Show the modal again
    resetZoom();
};

// Reset zoom when modal closes
watch(showViewModal, (newVal) => {
    if (!newVal) {
        resetZoom();
    }
});

// Handle keyboard events
const handleKeydown = (event) => {
    if (event.key === 'Escape' && showFullscreenViewer.value) {
        closeFullscreen();
    }
    // Zoom with + and - keys when fullscreen
    if (showFullscreenViewer.value) {
        if (event.key === '+' || event.key === '=') {
            zoomIn();
        } else if (event.key === '-') {
            zoomOut();
        } else if (event.key === 'r' || event.key === 'R') {
            rotateImage();
        } else if (event.key === '0') {
            resetZoom();
        }
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <AppLayout title="Payment Verifications">
        <div class="container-fixed">
            <!-- Header with Breadcrumb -->
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-semibold leading-none text-gray-900">
                        Payment Verifications
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <Link :href="route('admin.dashboard')" class="hover:text-primary transition-colors">
                            <i class="ki-filled ki-home text-gray-500 me-1"></i>
                            Dashboard
                        </Link>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-700">Payment Verifications</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link :href="route('admin.dashboard')" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-arrow-left me-1"></i>
                        Back to Dashboard
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Pending - Highlighted -->
                <div class="card bg-gradient-to-br from-warning/10 to-warning/5 border-warning/20">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-warning/20">
                                <i class="ki-filled ki-time text-warning text-xl"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-warning">{{ stats.pending }}</div>
                                <div class="text-xs text-gray-600 font-medium">Pending Review</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="card bg-gradient-to-br from-success/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success-light">
                                <i class="ki-filled ki-check-circle text-success"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats.approved }}</div>
                                <div class="text-xs text-gray-500">Approved</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="card bg-gradient-to-br from-danger/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-danger-light">
                                <i class="ki-filled ki-cross-circle text-danger"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats.rejected }}</div>
                                <div class="text-xs text-gray-500">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="card bg-gradient-to-br from-primary/5 to-transparent">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                                <i class="ki-filled ki-document text-primary"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ stats.total }}</div>
                                <div class="text-xs text-gray-500">Total Proofs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-header border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <i class="ki-filled ki-filter text-gray-500"></i>
                        <h3 class="card-title text-sm font-semibold text-gray-700">Filters</h3>
                    </div>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="btn btn-sm btn-light-danger"
                    >
                        <i class="ki-filled ki-cross text-xs me-1"></i>
                        Reset Filters
                    </button>
                </div>
                <div class="card-body p-5">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Search Input -->
                        <div class="flex-1 min-w-[280px]">
                            <label class="form-label text-xs text-gray-500 mb-1.5">Search</label>
                            <div class="relative">
                                <i class="ki-filled ki-magnifier absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input
                                    v-model="search"
                                    type="text"
                                    class="input ps-10"
                                    placeholder="Search by company name or invoice number..."
                                />
                            </div>
                        </div>

                        <!-- Status Filter Dropdown -->
                        <div class="w-[200px]">
                            <label class="form-label text-xs text-gray-500 mb-1.5">Status</label>
                            <div class="relative" v-click-outside="closeStatusDropdown">
                                <!-- Dropdown Toggle Button -->
                                <button
                                    type="button"
                                    @click="toggleStatusDropdown"
                                    class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                >
                                    <span class="flex items-center gap-2">
                                        <i :class="[currentStatusStyle.icon, currentStatusStyle.color, 'text-base']"></i>
                                        <span>{{ currentStatusLabel }}</span>
                                    </span>
                                    <i class="ki-filled ki-down text-gray-400 text-xs transition-transform" :class="{ 'rotate-180': showStatusDropdown }"></i>
                                </button>

                                <!-- Dropdown Menu -->
                                <Transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <div
                                        v-show="showStatusDropdown"
                                        class="absolute z-50 mt-1.5 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1.5"
                                    >
                                        <button
                                            v-for="opt in statusOptions"
                                            :key="opt.value"
                                            type="button"
                                            @click="selectStatus(opt.value)"
                                            class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-sm hover:bg-gray-50 transition-colors"
                                            :class="statusFilter === opt.value ? 'bg-primary-light text-primary font-medium' : 'text-gray-700'"
                                        >
                                            <i :class="[
                                                opt.value === 'pending' ? 'ki-filled ki-time text-warning' :
                                                opt.value === 'approved' ? 'ki-filled ki-check-circle text-success' :
                                                opt.value === 'rejected' ? 'ki-filled ki-cross-circle text-danger' :
                                                'ki-filled ki-element-11 text-gray-500',
                                                'text-base'
                                            ]"></i>
                                            <span>{{ opt.label }}</span>
                                            <i v-if="statusFilter === opt.value" class="ki-filled ki-check text-primary text-xs ms-auto"></i>
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Proofs Table -->
            <div class="card shadow-sm">
                <div class="card-header border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light">
                            <i class="ki-filled ki-cheque text-lg text-primary"></i>
                        </div>
                        <div>
                            <h3 class="card-title text-base font-semibold text-gray-900">Payment Proofs</h3>
                            <span class="text-xs text-gray-500">
                                Showing {{ proofs.from || 0 }} to {{ proofs.to || 0 }} of {{ proofs.total }} records
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[180px]">Company</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[140px]">Invoice</th>
                                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px]">Plan</th>
                                    <th class="text-right px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Invoice Amount</th>
                                    <th class="text-right px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[130px]">Transfer Amount</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[110px]">Transfer Date</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[100px]">Status</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[110px]">Submitted</th>
                                    <th class="text-center px-5 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider w-[140px]">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="proof in proofs.data" :key="proof.id" class="hover:bg-gray-50/50 transition-colors">
                                    <!-- Company -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                                {{ proof.company?.name?.charAt(0)?.toUpperCase() || 'C' }}
                                            </div>
                                            <span class="font-medium text-gray-900">{{ proof.company?.name }}</span>
                                        </div>
                                    </td>

                                    <!-- Invoice Number -->
                                    <td class="px-5 py-4">
                                        <span class="text-sm font-mono text-gray-700">{{ proof.invoice?.invoice_number }}</span>
                                    </td>

                                    <!-- Plan Name -->
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-800">{{ proof.invoice?.plan_name }}</span>
                                            <span class="text-xs text-gray-500 capitalize">{{ proof.invoice?.billing_cycle }}</span>
                                        </div>
                                    </td>

                                    <!-- Invoice Amount -->
                                    <td class="px-5 py-4 text-right">
                                        <span class="text-sm font-semibold text-gray-900">{{ proof.invoice?.formatted_amount }}</span>
                                    </td>

                                    <!-- Transfer Amount -->
                                    <td class="px-5 py-4 text-right">
                                        <span class="text-sm font-semibold" :class="proof.transfer_amount == proof.invoice?.amount ? 'text-success' : 'text-warning'">
                                            {{ proof.formatted_transfer_amount }}
                                        </span>
                                    </td>

                                    <!-- Transfer Date -->
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-sm text-gray-700">{{ proof.transfer_date }}</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-5 py-4 text-center">
                                        <span :class="['badge badge-sm inline-flex items-center gap-1', getStatusClass(proof.status)]">
                                            <i :class="[getStatusIcon(proof.status), 'text-[10px]']"></i>
                                            {{ proof.status_label }}
                                        </span>
                                    </td>

                                    <!-- Created Date -->
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-sm text-gray-600">{{ proof.created_at }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- View Button -->
                                            <button
                                                type="button"
                                                @click="viewProof(proof)"
                                                class="btn btn-sm btn-icon btn-light hover:bg-primary hover:border-primary hover:text-white transition-colors"
                                                title="View Details"
                                            >
                                                <i class="ki-filled ki-eye"></i>
                                            </button>

                                            <!-- Approve Button (only for pending) -->
                                            <button
                                                v-if="proof.status === 'pending'"
                                                type="button"
                                                @click="approveProof(proof)"
                                                class="btn btn-sm btn-icon btn-light hover:bg-success hover:border-success hover:text-white transition-colors"
                                                title="Approve"
                                                :disabled="approving"
                                            >
                                                <i class="ki-filled ki-check"></i>
                                            </button>

                                            <!-- Reject Button (only for pending) -->
                                            <button
                                                v-if="proof.status === 'pending'"
                                                type="button"
                                                @click="openRejectModal(proof)"
                                                class="btn btn-sm btn-icon btn-light hover:bg-danger hover:border-danger hover:text-white transition-colors"
                                                title="Reject"
                                            >
                                                <i class="ki-filled ki-cross"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="proofs.data.length === 0">
                                    <td colspan="9" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                                <i class="ki-filled ki-cheque text-3xl text-gray-400"></i>
                                            </div>
                                            <p class="text-base font-medium text-gray-600 mb-1">No payment proofs found</p>
                                            <p class="text-sm text-gray-400" v-if="hasActiveFilters">Try adjusting your filters</p>
                                            <p class="text-sm text-gray-400" v-else>Payment proofs will appear here when submitted by tenants.</p>
                                            <button
                                                v-if="hasActiveFilters"
                                                @click="clearFilters"
                                                class="btn btn-sm btn-light mt-4"
                                            >
                                                <i class="ki-filled ki-arrow-left me-1"></i>
                                                Reset Filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="proofs.last_page > 1" class="card-footer border-t border-gray-200 flex flex-wrap justify-between items-center gap-4 py-4 px-5">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold text-gray-900">{{ proofs.from }}</span> to <span class="font-semibold text-gray-900">{{ proofs.to }}</span> of <span class="font-semibold text-gray-900">{{ proofs.total }}</span> records
                    </div>
                    <div class="flex gap-1.5">
                        <Link
                            v-for="link in proofs.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'btn btn-sm min-w-[36px]',
                                link.active ? 'btn-primary' : 'btn-light',
                                !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- View Payment Proof Modal -->
        <Modal :show="showViewModal" @close="closeViewModal" max-width="2xl">
            <div class="bg-white rounded-lg shadow-xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Payment Proof Details</h2>
                        <p class="mt-1 text-sm text-gray-500">Review payment proof submission</p>
                    </div>
                    <button
                        type="button"
                        @click="closeViewModal"
                        class="btn btn-sm btn-icon btn-light"
                    >
                        <i class="ki-filled ki-cross text-gray-500"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    <!-- Loading State -->
                    <div v-if="loadingDetails" class="flex items-center justify-center py-12">
                        <div class="flex flex-col items-center gap-3">
                            <i class="ki-filled ki-loading animate-spin text-3xl text-primary"></i>
                            <span class="text-sm text-gray-500">Loading details...</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div v-else-if="proofDetails" class="space-y-6">
                        <!-- Status Badge -->
                        <div class="flex items-center justify-between">
                            <span :class="['badge badge-lg inline-flex items-center gap-1.5', getStatusClass(proofDetails.status)]">
                                <i :class="[getStatusIcon(proofDetails.status), 'text-sm']"></i>
                                {{ proofDetails.status_label }}
                            </span>
                            <span v-if="proofDetails.reviewed_at" class="text-sm text-gray-500">
                                Reviewed: {{ proofDetails.reviewed_at }}
                                <span v-if="proofDetails.reviewer">by {{ proofDetails.reviewer.name }}</span>
                            </span>
                        </div>

                        <!-- Company Info -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-abstract-41 text-primary"></i>
                                Company Information
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500">Company Name</label>
                                    <p class="text-sm font-medium text-gray-900">{{ proofDetails.company?.name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Email</label>
                                    <p class="text-sm font-medium text-gray-900">{{ proofDetails.company?.email || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Info -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-document text-info"></i>
                                Invoice Details
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500">Invoice Number</label>
                                    <p class="text-sm font-mono font-medium text-gray-900">{{ proofDetails.invoice?.invoice_number }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Plan</label>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ proofDetails.invoice?.plan_name }}
                                        <span class="text-gray-500 capitalize">({{ proofDetails.invoice?.billing_cycle }})</span>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Invoice Amount</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ proofDetails.invoice?.formatted_amount }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Due Date</label>
                                    <p class="text-sm font-medium text-gray-900">{{ proofDetails.invoice?.due_date }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Info -->
                        <div class="bg-green-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-cheque text-success"></i>
                                Transfer Details
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500">Bank Name</label>
                                    <p class="text-sm font-medium text-gray-900">{{ proofDetails.bank_name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Account Name</label>
                                    <p class="text-sm font-medium text-gray-900">{{ proofDetails.account_name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Account Number</label>
                                    <p class="text-sm font-mono font-medium text-gray-900">{{ proofDetails.account_number }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Transfer Date</label>
                                    <p class="text-sm font-medium text-gray-900">{{ proofDetails.transfer_date }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-xs text-gray-500">Transfer Amount</label>
                                    <p class="text-lg font-bold" :class="proofDetails.transfer_amount == proofDetails.invoice?.amount ? 'text-success' : 'text-warning'">
                                        {{ proofDetails.formatted_transfer_amount }}
                                        <span v-if="proofDetails.transfer_amount != proofDetails.invoice?.amount" class="text-xs font-normal text-warning">
                                            (Differs from invoice amount)
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes from Tenant -->
                        <div v-if="proofDetails.notes" class="bg-yellow-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="ki-filled ki-message-text-2 text-warning"></i>
                                Notes from Tenant
                            </h4>
                            <p class="text-sm text-gray-700">{{ proofDetails.notes }}</p>
                        </div>

                        <!-- Rejection Reason (if rejected) -->
                        <div v-if="proofDetails.status === 'rejected' && proofDetails.rejection_reason" class="bg-red-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-danger mb-2 flex items-center gap-2">
                                <i class="ki-filled ki-cross-circle text-danger"></i>
                                Rejection Reason
                            </h4>
                            <p class="text-sm text-gray-700">{{ proofDetails.rejection_reason }}</p>
                        </div>

                        <!-- Payment Proof File Preview -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                        <i class="ki-filled ki-picture text-primary"></i>
                                        Payment Proof File
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ proofDetails.file_name }} ({{ proofDetails.formatted_file_size }})
                                    </p>
                                </div>
                                <!-- Zoom Controls for Images -->
                                <div v-if="isImage(proofDetails.file_type)" class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="zoomOut"
                                        class="btn btn-xs btn-icon btn-light"
                                        title="Zoom Out"
                                        :disabled="imageZoom <= 0.5"
                                    >
                                        <i class="ki-filled ki-minus text-gray-600"></i>
                                    </button>
                                    <span class="text-xs text-gray-600 min-w-[50px] text-center font-medium">
                                        {{ Math.round(imageZoom * 100) }}%
                                    </span>
                                    <button
                                        type="button"
                                        @click="zoomIn"
                                        class="btn btn-xs btn-icon btn-light"
                                        title="Zoom In"
                                        :disabled="imageZoom >= 5"
                                    >
                                        <i class="ki-filled ki-plus text-gray-600"></i>
                                    </button>
                                    <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                    <button
                                        type="button"
                                        @click="rotateImage"
                                        class="btn btn-xs btn-icon btn-light"
                                        title="Rotate"
                                    >
                                        <i class="ki-filled ki-arrows-circle text-gray-600"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="resetZoom"
                                        class="btn btn-xs btn-icon btn-light"
                                        title="Reset View"
                                    >
                                        <i class="ki-filled ki-arrows-loop text-gray-600"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openFullscreen"
                                        class="btn btn-xs btn-icon btn-primary"
                                        title="Fullscreen"
                                    >
                                        <i class="ki-filled ki-maximize text-white"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-800 flex items-center justify-center min-h-[350px] overflow-hidden">
                                <!-- Image Preview with Zoom -->
                                <div
                                    v-if="isImage(proofDetails.file_type)"
                                    ref="imageViewerRef"
                                    class="relative w-full h-[350px] overflow-hidden flex items-center justify-center"
                                    :class="{ 'cursor-grab': imageZoom > 1, 'cursor-grabbing': isDragging }"
                                    @wheel="handleWheel"
                                    @mousedown="handleDragStart"
                                    @mousemove="handleDragMove"
                                    @mouseup="handleDragEnd"
                                    @mouseleave="handleDragEnd"
                                >
                                    <img
                                        :src="proofDetails.file_url"
                                        :alt="proofDetails.file_name"
                                        class="max-w-full max-h-full object-contain transition-transform duration-100 select-none"
                                        :style="{
                                            transform: `scale(${imageZoom}) rotate(${imageRotation}deg) translate(${imagePan.x / imageZoom}px, ${imagePan.y / imageZoom}px)`,
                                        }"
                                        draggable="false"
                                    />
                                    <!-- Zoom hint -->
                                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-black/60 text-white text-xs px-3 py-1.5 rounded-full">
                                        <i class="ki-filled ki-mouse me-1"></i>
                                        Scroll to zoom • Drag to pan
                                    </div>
                                </div>

                                <!-- PDF Preview Link -->
                                <div v-else-if="isPdf(proofDetails.file_type)" class="text-center p-8">
                                    <div class="flex items-center justify-center w-20 h-20 rounded-lg bg-danger-light mb-4 mx-auto">
                                        <i class="ki-filled ki-document text-4xl text-danger"></i>
                                    </div>
                                    <p class="text-sm text-gray-300 mb-3">PDF Document</p>
                                    <a
                                        :href="proofDetails.file_url"
                                        target="_blank"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="ki-filled ki-eye me-1.5"></i>
                                        View PDF
                                    </a>
                                </div>

                                <!-- Other File Types -->
                                <div v-else class="text-center p-8">
                                    <div class="flex items-center justify-center w-20 h-20 rounded-lg bg-gray-700 mb-4 mx-auto">
                                        <i class="ki-filled ki-file text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-300 mb-3">{{ proofDetails.file_type?.toUpperCase() }} File</p>
                                    <a
                                        :href="proofDetails.file_url"
                                        target="_blank"
                                        class="btn btn-sm btn-light"
                                    >
                                        <i class="ki-filled ki-arrow-down me-1.5"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer (Actions for pending proofs) -->
                <div v-if="proofDetails?.status === 'pending'" class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <button
                        type="button"
                        @click="openRejectModal()"
                        class="btn btn-danger"
                        :disabled="rejecting || approving"
                    >
                        <i class="ki-filled ki-cross me-1.5"></i>
                        Reject
                    </button>
                    <button
                        type="button"
                        @click="approveProof()"
                        class="btn btn-success"
                        :disabled="approving || rejecting"
                    >
                        <i v-if="approving" class="ki-filled ki-loading animate-spin me-1.5"></i>
                        <i v-else class="ki-filled ki-check me-1.5"></i>
                        {{ approving ? 'Approving...' : 'Approve Payment' }}
                    </button>
                </div>

                <!-- Close button for reviewed proofs -->
                <div v-else class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <button
                        type="button"
                        @click="closeViewModal"
                        class="btn btn-light"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" @close="closeRejectModal" max-width="md">
            <div class="bg-white rounded-lg shadow-xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Reject Payment Proof</h2>
                        <p class="mt-1 text-sm text-gray-500">Please provide a reason for rejection</p>
                    </div>
                    <button
                        type="button"
                        @click="closeRejectModal"
                        class="btn btn-sm btn-icon btn-light"
                        :disabled="rejecting"
                    >
                        <i class="ki-filled ki-cross text-gray-500"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="rejectProof">
                    <div class="p-6">
                        <!-- Info about what's being rejected -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                    {{ selectedProof?.company?.name?.charAt(0)?.toUpperCase() || 'C' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ selectedProof?.company?.name }}</p>
                                    <p class="text-sm text-gray-500">{{ selectedProof?.invoice?.invoice_number }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Rejection Reason -->
                        <div>
                            <label class="form-label text-gray-700 mb-2.5">
                                Rejection Reason <span class="text-danger">*</span>
                            </label>
                            <textarea
                                v-model="rejectReason"
                                class="textarea w-full"
                                :class="{ 'border-danger': rejectErrors.reason }"
                                rows="4"
                                placeholder="Please explain why this payment proof is being rejected..."
                                required
                            ></textarea>
                            <p v-if="rejectErrors.reason" class="form-hint text-danger mt-1.5">{{ rejectErrors.reason[0] }}</p>
                            <p class="form-hint mt-1.5">The customer will be notified of this rejection and the reason provided.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                        <button
                            type="button"
                            @click="closeRejectModal"
                            class="btn btn-light"
                            :disabled="rejecting"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn btn-danger"
                            :disabled="rejecting"
                        >
                            <i v-if="rejecting" class="ki-filled ki-loading animate-spin me-1.5"></i>
                            <i v-else class="ki-filled ki-cross me-1.5"></i>
                            {{ rejecting ? 'Rejecting...' : 'Reject Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Fullscreen Image Viewer -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showFullscreenViewer && fullscreenImageUrl"
                    class="fixed inset-0 z-[9999] bg-black/95 flex flex-col"
                >
                    <!-- Fullscreen Header -->
                    <div class="flex items-center justify-between px-4 py-3 bg-black/50 border-b border-gray-800">
                        <div class="flex items-center gap-3 text-white">
                            <i class="ki-filled ki-picture text-primary"></i>
                            <span class="text-sm font-medium">{{ fullscreenFileName }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Zoom controls -->
                            <button
                                type="button"
                                @click="zoomOut"
                                class="btn btn-sm btn-icon bg-gray-800 hover:bg-gray-700 border-gray-700 text-white"
                                title="Zoom Out (50%)"
                                :disabled="imageZoom <= 0.5"
                            >
                                <i class="ki-filled ki-minus"></i>
                            </button>
                            <span class="text-sm text-white min-w-[60px] text-center font-medium">
                                {{ Math.round(imageZoom * 100) }}%
                            </span>
                            <button
                                type="button"
                                @click="zoomIn"
                                class="btn btn-sm btn-icon bg-gray-800 hover:bg-gray-700 border-gray-700 text-white"
                                title="Zoom In (500%)"
                                :disabled="imageZoom >= 5"
                            >
                                <i class="ki-filled ki-plus"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-700 mx-2"></div>
                            <button
                                type="button"
                                @click="rotateImage"
                                class="btn btn-sm btn-icon bg-gray-800 hover:bg-gray-700 border-gray-700 text-white"
                                title="Rotate 90°"
                            >
                                <i class="ki-filled ki-arrows-circle"></i>
                            </button>
                            <button
                                type="button"
                                @click="resetZoom"
                                class="btn btn-sm btn-icon bg-gray-800 hover:bg-gray-700 border-gray-700 text-white"
                                title="Reset View"
                            >
                                <i class="ki-filled ki-arrows-loop"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-700 mx-2"></div>
                            <a
                                :href="fullscreenImageUrl"
                                target="_blank"
                                class="btn btn-sm bg-gray-800 hover:bg-gray-700 border-gray-700 text-white"
                                title="Open in New Tab"
                            >
                                <i class="ki-filled ki-exit-right-corner me-1.5"></i>
                                Open Original
                            </a>
                            <button
                                type="button"
                                @click="closeFullscreen"
                                class="btn btn-sm btn-icon bg-danger hover:bg-danger-active border-danger text-white ms-2"
                                title="Close (Esc)"
                            >
                                <i class="ki-filled ki-cross"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Fullscreen Image Area -->
                    <div
                        class="flex-1 overflow-hidden flex items-center justify-center p-4"
                        :class="{ 'cursor-grab': imageZoom > 1, 'cursor-grabbing': isDragging }"
                        @wheel="handleWheel"
                        @mousedown="handleDragStart"
                        @mousemove="handleDragMove"
                        @mouseup="handleDragEnd"
                        @mouseleave="handleDragEnd"
                    >
                        <img
                            :src="fullscreenImageUrl"
                            :alt="fullscreenFileName"
                            class="max-w-full max-h-full object-contain transition-transform duration-100 select-none"
                            :style="{
                                transform: `scale(${imageZoom}) rotate(${imageRotation}deg) translate(${imagePan.x / imageZoom}px, ${imagePan.y / imageZoom}px)`,
                            }"
                            draggable="false"
                        />
                    </div>

                    <!-- Fullscreen Footer Hint -->
                    <div class="flex items-center justify-center py-2 bg-black/50 border-t border-gray-800">
                        <div class="flex items-center gap-6 text-xs text-gray-400">
                            <span><i class="ki-filled ki-mouse me-1"></i> Scroll to zoom</span>
                            <span><i class="ki-filled ki-cursor me-1"></i> Drag to pan when zoomed</span>
                            <span><kbd class="px-1.5 py-0.5 bg-gray-800 rounded text-gray-300">Esc</kbd> to close</span>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
