<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, watch, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import { KTModal } from '../../../metronic/core/components/modal';

// Props from controller
const props = defineProps({
    customers: Array,
    customerTypes: Array,
    pagination: Object,
    filters: Object,
    stats: Object,
});

// Search and pagination state
const searchQuery = ref(props.filters?.search || '');
const currentPage = ref(props.pagination?.current_page || 1);
const perPageOptions = [
    { value: 10, label: '10' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
];
const selectedPerPage = ref(props.pagination?.per_page || 10);
const statusFilter = ref(props.filters?.status || 'all');
const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Form state for create modal
const form = ref({
    customer_name: '',
    email: '',
    mst_customer_type_id: '',
    customer_phone_number: '',
    contact_person: '',
    contact_person_phone_number: '',
    is_customer: true,
});
const isLoading = ref(false);
const errors = ref({});

// Format helpers
const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Fetch data with filters
const fetchData = () => {
    router.get(route('customer.list'), {
        search: searchQuery.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        per_page: selectedPerPage.value,
        page: currentPage.value,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Watch for pagination and filter changes
watch([currentPage, selectedPerPage, statusFilter], () => {
    fetchData();
});

// Perform search
const performSearch = () => {
    currentPage.value = 1;
    fetchData();
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
    currentPage.value = 1;
    fetchData();
};

// Get initials for avatar
const getInitials = (name) => {
    if (!name) return 'C';
    const words = name.split(' ');
    if (words.length > 1) {
        return words.slice(0, 2).map(w => w[0]?.toUpperCase()).join('');
    }
    return name[0]?.toUpperCase() || 'C';
};

// Create new customer
const submitForm = () => {
    isLoading.value = true;
    errors.value = {};

    axios.post('/customer/api/store', form.value)
    .then((response) => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message
        });

        form.value = {
            customer_name: '',
            email: '',
            mst_customer_type_id: '',
            customer_phone_number: '',
            contact_person: '',
            contact_person_phone_number: '',
            is_customer: true,
        };
        closeModal('modal_create_new_customer');
        fetchData();
    })
    .catch(error => {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.response?.data?.message || 'An error occurred',
        });
    })
    .finally(() => {
        isLoading.value = false;
    });
};

// Toggle customer status (activate/deactivate)
const toggleStatus = (customer) => {
    const action = customer.is_active ? 'deactivate' : 'activate';
    const title = customer.is_active ? 'Deactivate Customer?' : 'Activate Customer?';
    const text = customer.is_active
        ? `Are you sure you want to deactivate "${customer.name}"? They won't appear in dropdowns for new orders.`
        : `Are you sure you want to activate "${customer.name}"?`;

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: customer.is_active ? '#F59E0B' : '#22C55E',
        cancelButtonColor: '#6B7280',
        confirmButtonText: customer.is_active ? 'Yes, deactivate' : 'Yes, activate'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`/customer/api/toggle-status/${customer.id}`)
                .then(response => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message
                    });
                    fetchData();
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data?.message || 'Failed to update customer status.'
                    });
                });
        }
    });
};

// Close modal helper
const closeModal = (modalId) => {
    const modalEl = document.querySelector(`#${modalId}`);
    if (modalEl) {
        const modal = KTModal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }
    if (document.querySelector('.modal-backdrop')) {
        document.querySelector('.modal-backdrop').remove();
    }
};

onMounted(() => {
    KTModal.init();
});
</script>

<template>
    <AppLayout title="Customers">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Customers
            </h2>
        </template>

        <!-- Container -->
        <div class="container-fixed">
            <!-- Stats Summary Cards -->
            <div class="py-5">
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-5">
                    <!-- Total Customers -->
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-light">
                                    <i class="ki-filled ki-people text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.total_customers) }}</div>
                                    <div class="text-xs text-gray-500">Total Customers</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Customers -->
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-success-light">
                                    <i class="ki-filled ki-verify text-success text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(stats?.active_customers) }}</div>
                                    <div class="text-xs text-gray-500">Active Customers</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Revenue -->
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-info-light">
                                    <i class="ki-filled ki-dollar text-info text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900">{{ formatCurrency(stats?.total_revenue) }}</div>
                                    <div class="text-xs text-gray-500">Total Revenue</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding -->
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-warning-light">
                                    <i class="ki-filled ki-time text-warning text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900">{{ formatCurrency(stats?.total_outstanding) }}</div>
                                    <div class="text-xs text-gray-500">Outstanding</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Data Card -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-4">
                        <h3 class="card-title">Customers</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Status Filter -->
                            <SearchableSelect
                                v-model="statusFilter"
                                :options="statusOptions"
                                placeholder="All Status"
                                class="w-[140px]"
                                :clearable="false"
                            />

                            <!-- Search Input -->
                            <div class="relative">
                                <i class="ki-filled ki-magnifier text-gray-500 absolute top-1/2 left-3 -translate-y-1/2"></i>
                                <input
                                    type="text"
                                    class="input input-sm pl-9 w-[200px]"
                                    placeholder="Search customers..."
                                    v-model="searchQuery"
                                    @keyup.enter="performSearch"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="clearSearch"
                                    class="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <i class="ki-filled ki-cross text-sm"></i>
                                </button>
                            </div>

                            <!-- Add New Button -->
                            <button class="btn btn-sm btn-primary" data-modal-toggle="#modal_create_new_customer">
                                <i class="ki-filled ki-plus-squared me-1"></i>
                                New Customer
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[50px] text-center">#</th>
                                        <th class="min-w-[250px]">Customer</th>
                                        <th class="w-[140px]">Type</th>
                                        <th class="w-[180px]">Location</th>
                                        <th class="w-[100px] text-center">Orders</th>
                                        <th class="w-[140px] text-end">Total Sales</th>
                                        <th class="w-[140px] text-end">Outstanding</th>
                                        <th class="w-[100px] text-center">Status</th>
                                        <th class="w-[80px] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!customers || customers.length === 0">
                                        <td colspan="9">
                                            <div class="flex flex-col items-center justify-center py-10">
                                                <i class="ki-filled ki-people text-6xl text-gray-300 mb-3"></i>
                                                <span class="text-gray-500">No customers found</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else v-for="(customer, index) in customers" :key="customer.id" class="hover:bg-slate-50 dark:hover:bg-coal-600">
                                        <td class="text-center text-gray-600">
                                            {{ (pagination?.from || 0) + index }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-light text-primary font-bold text-sm shrink-0">
                                                    {{ getInitials(customer.name) }}
                                                </div>
                                                <div class="flex flex-col">
                                                    <Link :href="`/customer/detail/${customer.id}`" class="text-sm font-medium text-gray-900 hover:text-primary">
                                                        {{ customer.name }}
                                                    </Link>
                                                    <span class="text-xs text-gray-500">{{ customer.email || '-' }}</span>
                                                    <span class="text-xs text-gray-500">{{ customer.phone_number || '-' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm badge-outline badge-secondary">
                                                {{ customer.customer_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="customer.address" class="text-sm text-gray-600">
                                                {{ customer.address.city_name }}, {{ customer.address.country_name }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm" :class="customer.orders_count > 0 ? 'badge-outline badge-primary' : 'badge-outline badge-secondary'">
                                                {{ formatNumber(customer.orders_count) }}
                                            </span>
                                        </td>
                                        <td class="text-end font-medium text-gray-900">
                                            {{ formatCurrency(customer.total_sales) }}
                                        </td>
                                        <td class="text-end">
                                            <span :class="customer.outstanding > 0 ? 'text-warning font-medium' : 'text-gray-600'">
                                                {{ formatCurrency(customer.outstanding) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm" :class="customer.is_active ? 'badge-success' : 'badge-secondary'">
                                                {{ customer.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="menu flex-inline justify-center" data-menu="true">
                                                <div class="menu-item" data-menu-item-offset="0, 10px"
                                                    data-menu-item-placement="bottom-end"
                                                    data-menu-item-toggle="dropdown"
                                                    data-menu-item-trigger="click|lg:click">
                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                        <i class="ki-filled ki-dots-vertical"></i>
                                                    </button>
                                                    <div class="menu-dropdown menu-default w-full max-w-[200px]" data-menu-dismiss="true">
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/customer/detail/${customer.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                <span class="menu-title">View Detail</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/customer/edit/${customer.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Edit</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-separator"></div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/sales-orders/create?customer_id=${customer.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-plus-squared"></i></span>
                                                                <span class="menu-title">Create Sales Order</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-item">
                                                            <Link class="menu-link" :href="`/sales-orders/list?customer_id=${customer.id}`">
                                                                <span class="menu-icon"><i class="ki-filled ki-document"></i></span>
                                                                <span class="menu-title">View Orders</span>
                                                            </Link>
                                                        </div>
                                                        <div class="menu-separator"></div>
                                                        <div class="menu-item">
                                                            <span class="menu-link cursor-pointer" @click="toggleStatus(customer)">
                                                                <span class="menu-icon">
                                                                    <i :class="customer.is_active ? 'ki-filled ki-lock' : 'ki-filled ki-check-circle'" class="text-warning"></i>
                                                                </span>
                                                                <span class="menu-title" :class="customer.is_active ? 'text-warning' : 'text-success'">
                                                                    {{ customer.is_active ? 'Deactivate' : 'Activate' }}
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Show
                                <SearchableSelect
                                    v-model="selectedPerPage"
                                    :options="perPageOptions"
                                    placeholder="10"
                                    class="w-20"
                                    :clearable="false"
                                />
                                per page
                            </div>
                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span>
                                    Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} results
                                </span>
                                <div class="pagination flex items-center gap-1">
                                    <button
                                        class="btn btn-sm btn-icon btn-light"
                                        :disabled="currentPage <= 1"
                                        @click="currentPage--"
                                    >
                                        <i class="ki-filled ki-arrow-left"></i>
                                    </button>
                                    <template v-for="page in pagination?.last_page" :key="page">
                                        <button
                                            v-if="page === 1 || page === pagination?.last_page || (page >= currentPage - 1 && page <= currentPage + 1)"
                                            class="btn btn-sm btn-icon"
                                            :class="page === currentPage ? 'btn-primary' : 'btn-light'"
                                            @click="currentPage = page"
                                        >
                                            {{ page }}
                                        </button>
                                        <span v-else-if="page === currentPage - 2 || page === currentPage + 2" class="text-gray-400">...</span>
                                    </template>
                                    <button
                                        class="btn btn-sm btn-icon btn-light"
                                        :disabled="currentPage >= pagination?.last_page"
                                        @click="currentPage++"
                                    >
                                        <i class="ki-filled ki-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create New Customer Modal -->
            <div id="modal_create_new_customer" data-modal="true" class="modal">
                <div class="modal-dialog">
                    <div class="modal-content max-w-[600px] top-[10%]">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Customer</h5>
                            <button type="button" class="btn btn-xs btn-icon btn-light" data-modal-dismiss="true">
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>
                        <form @submit.prevent="submitForm">
                            <div class="modal-body py-5">
                                <!-- Error display -->
                                <div v-if="Object.keys(errors).length" class="bg-red-100 border-l-4 border-red-300 text-red-700 p-4 mb-5 rounded">
                                    <p class="font-bold mb-2">Please fix the following errors:</p>
                                    <ul class="list-disc pl-5 text-sm">
                                        <li v-for="(messages, field) in errors" :key="field">
                                            <span v-for="(message, idx) in messages" :key="idx">{{ message }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Customer Name -->
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">
                                        Customer Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="input w-full"
                                        v-model="form.customer_name"
                                        placeholder="Enter customer name"
                                        required
                                    />
                                </div>

                                <!-- Customer Type & Is Customer -->
                                <div class="grid grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Customer Type <span class="text-danger">*</span>
                                        </label>
                                        <SearchableSelect
                                            v-model="form.mst_customer_type_id"
                                            :options="customerTypes"
                                            placeholder="Select type"
                                            :clearable="true"
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Is a Customer <span class="text-danger">*</span>
                                        </label>
                                        <label class="switch switch-lg mt-2">
                                            <input type="checkbox" v-model="form.is_customer" />
                                        </label>
                                    </div>
                                </div>

                                <!-- Email & Phone -->
                                <div class="grid grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            class="input w-full"
                                            v-model="form.email"
                                            placeholder="email@example.com"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">
                                            Phone Number <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.customer_phone_number"
                                            placeholder="+62..."
                                            required
                                        />
                                    </div>
                                </div>

                                <!-- Contact Person -->
                                <div class="grid grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <label class="form-label text-gray-700">Contact Person</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.contact_person"
                                            placeholder="Contact person name"
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">Contact Person Phone</label>
                                        <input
                                            type="text"
                                            class="input w-full"
                                            v-model="form.contact_person_phone_number"
                                            placeholder="Phone number"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ isLoading ? 'Creating...' : 'Create Customer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
