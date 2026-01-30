<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Get props from controller
const props = defineProps({
    customer: Object,
    customerTypes: Array,
});

// Form state - initialize with customer data
const form = ref({
    id: props.customer.id,
    customer_name: props.customer.customer_name,
    email: props.customer.email,
    customer_phone_number: props.customer.customer_phone_number,
    contact_person: props.customer.contact_person || '',
    contact_person_phone_number: props.customer.contact_person_phone_number || '',
    mst_customer_type_id: props.customer.mst_customer_type_id,
    is_customer: props.customer.is_customer ? 1 : 0,
    address: {
        address: props.customer.address?.address || '',
        city_name: props.customer.address?.city_name || '',
        state_name: props.customer.address?.state_name || '',
        country_name: props.customer.address?.country_name || '',
    },
});

const isLoading = ref(false);

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

// Update customer via API
const updateCustomer = () => {
    isLoading.value = true;

    axios.put(`/customer/api/update/${form.value.id}`, form.value)
    .then(response => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Success',
            text: response.data.message || 'Customer updated successfully'
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'An error occurred.',
        });
    })
    .finally(() => {
        isLoading.value = false;
    });
};
</script>

<template>
    <AppLayout title="Edit Customer">
        <!-- Page Header with back button -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/customer/list" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Customer
                </h2>
            </div>
        </template>

        <!-- Main Content Container -->
        <div class="container-fixed py-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Left Column: Form (2/3 width) -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-profile-circle text-gray-500 me-2"></i>
                                Customer Information
                            </h3>
                        </div>
                        <form class="card-body" @submit.prevent="updateCustomer">
                            <!-- Basic Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <div>
                                    <label class="form-label text-gray-700">
                                        Customer Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        class="input w-full"
                                        type="text"
                                        v-model="form.customer_name"
                                        placeholder="Enter customer name"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="form-label text-gray-700">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        class="input w-full"
                                        type="email"
                                        v-model="form.email"
                                        placeholder="email@example.com"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <div>
                                    <label class="form-label text-gray-700">
                                        Phone Number <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        class="input w-full"
                                        type="text"
                                        v-model="form.customer_phone_number"
                                        placeholder="+62..."
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="form-label text-gray-700">
                                        Customer Type <span class="text-danger">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.mst_customer_type_id"
                                        :options="customerTypes"
                                        placeholder="Select customer type"
                                        :clearable="false"
                                    />
                                </div>
                            </div>

                            <!-- Contact Person -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <div>
                                    <label class="form-label text-gray-700">Contact Person</label>
                                    <input
                                        class="input w-full"
                                        type="text"
                                        v-model="form.contact_person"
                                        placeholder="Contact person name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label text-gray-700">Contact Person Phone</label>
                                    <input
                                        class="input w-full"
                                        type="text"
                                        v-model="form.contact_person_phone_number"
                                        placeholder="Contact person phone"
                                    />
                                </div>
                            </div>

                            <!-- Is Customer Toggle -->
                            <div class="mb-5">
                                <label class="form-label text-gray-700">
                                    Is a Customer <span class="text-danger">*</span>
                                </label>
                                <label class="switch switch-lg mt-2">
                                    <input type="checkbox" v-model="form.is_customer" :true-value="1" :false-value="0" />
                                </label>
                                <span class="text-xs text-gray-500 ms-3">
                                    Enable if this entity is a customer (can place orders).
                                </span>
                            </div>

                            <!-- Address Section -->
                            <div class="border-t border-gray-200 pt-5 mt-5">
                                <h4 class="text-md font-semibold text-gray-800 mb-4">
                                    <i class="ki-filled ki-geolocation text-gray-500 me-2"></i>
                                    Address Information
                                </h4>
                                <div class="mb-5">
                                    <label class="form-label text-gray-700">Street Address</label>
                                    <input
                                        class="input w-full"
                                        type="text"
                                        v-model="form.address.address"
                                        placeholder="Street address"
                                    />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="form-label text-gray-700">City</label>
                                        <input
                                            class="input w-full"
                                            type="text"
                                            v-model="form.address.city_name"
                                            placeholder="City"
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">State / Province</label>
                                        <input
                                            class="input w-full"
                                            type="text"
                                            v-model="form.address.state_name"
                                            placeholder="State"
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-700">Country</label>
                                        <input
                                            class="input w-full"
                                            type="text"
                                            v-model="form.address.country_name"
                                            placeholder="Country"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Form Footer -->
                            <div class="flex justify-between pt-5 mt-5 border-t border-gray-200">
                                <Link href="/customer/list" class="btn btn-light">
                                    <i class="ki-filled ki-arrow-left me-1"></i> Cancel
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <i v-if="!isLoading" class="ki-filled ki-check me-1"></i>
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ isLoading ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Info (1/3 width) -->
                <div class="space-y-5">
                    <!-- Statistics Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-chart-simple text-gray-500 me-2"></i>
                                Customer Statistics
                            </h3>
                        </div>
                        <div class="card-body space-y-4">
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-light">
                                    <i class="ki-filled ki-document text-primary text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ customer.orders_count || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500">Total Orders</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-coal-500 rounded-xl">
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-success-light">
                                    <i class="ki-filled ki-dollar text-success text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(customer.total_sales) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Total Revenue</div>
                                </div>
                            </div>
                            <div v-if="customer.is_active === false" class="p-3 bg-yellow-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="ki-filled ki-information-2 text-yellow-600"></i>
                                    <span class="text-sm text-yellow-800">
                                        This customer is currently <strong>inactive</strong>.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-setting-2 text-gray-500 me-2"></i>
                                Actions
                            </h3>
                        </div>
                        <div class="card-body space-y-3">
                            <!-- View Customer Detail -->
                            <Link :href="`/customer/detail/${form.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-eye me-2"></i>
                                View Customer Detail
                            </Link>

                            <!-- Create Sales Order -->
                            <Link :href="`/sales-orders/create?customer_id=${form.id}`" class="btn btn-light w-full">
                                <i class="ki-filled ki-plus-squared me-2"></i>
                                Create Sales Order
                            </Link>

                            <!-- Back to Customer List -->
                            <Link href="/customer/list" class="btn btn-light w-full">
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back to Customer List
                            </Link>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-filled ki-information-2 text-gray-500 me-2"></i>
                                Help
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="text-sm text-gray-600 space-y-2">
                                <p><strong>Customer Name:</strong> The company or individual name.</p>
                                <p><strong>Customer Type:</strong> Categorization for reporting and filtering.</p>
                                <p><strong>Is a Customer:</strong> Toggle off if this entity is a supplier only.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
