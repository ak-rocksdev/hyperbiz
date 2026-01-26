<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const customer = ref({ ...props.customer });
const customerTypes = ref({ ...props.customerTypes });
watch(() => customer.value.is_customer, (newValue) => {
    console.log('Is Customer:', newValue);
});

const updateCustomer = () => {
    axios.put(`/customer/api/update/${customer.value.id}`, customer.value)
        .then(response => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response.data.message || 'An error occurred while updating the customer.',
            });
            console.error(error);
        });
};
</script>

<template>
    <AppLayout title="Edit Customer">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Customer
            </h2>
        </template>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Edit Customer
                        </h3>
                    </div>
                    <form class="card-body" @submit.prevent="updateCustomer">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- First Column -->
                            <div class="grid grid-cols-2 gap-5 p-5">
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Customer Name</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Customer Name"
                                        type="text"
                                        v-model="customer.customer_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Customer Phone</label>
                                    <input
                                        class="input"
                                        placeholder="Customer Phone"
                                        type="text"
                                        v-model="customer.customer_phone_number"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Email</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Customer Email"
                                        type="email"
                                        v-model="customer.email"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Contact Person</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Contact Person"
                                        type="text"
                                        v-model="customer.contact_person"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Contact Person Phone</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Contact Person Phone"
                                        type="text"
                                        v-model="customer.contact_person_phone_number"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Customer Type</label>
                                    <select
                                        class="select"
                                        v-model="customer.mst_customer_type_id"
                                    >
                                        <option disabled value="">Select Customer Type</option>
                                        <option
                                            v-for="(name, id) in customerTypes"
                                            :key="id"
                                            :value="id"
                                        >
                                            {{ name }}
                                        </option>
                                    </select>
                                </div>
                                <!-- create is customer -->
                                <div class="mb-4">
                                    <label class="form-label max-w-60 mb-4">
                                        Is a Customer
                                        <span class="ms-1 text-danger">*</span>
                                    </label>
                                    <label class="switch switch-lg">
                                        <input class="order-2" type="checkbox" v-model="customer.is_customer"
                                            :true-value="1" :false-value="0" />
                                    </label>
                                </div>
                            </div>

                            <!-- Second Column -->
                            <div class="grid grid-cols-1 gap-5 p-5">
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Address</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Address"
                                        type="text"
                                        v-model="customer.address.address"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">City</label>
                                    <input
                                        class="input"
                                        placeholder="Enter City"
                                        type="text"
                                        v-model="customer.address.city_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">State</label>
                                    <input
                                        class="input"
                                        placeholder="Enter State"
                                        type="text"
                                        v-model="customer.address.state_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Country</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Country"
                                        type="text"
                                        v-model="customer.address.country_name"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="flex justify-between px-5 pb-5">
                            <Link href="/customer/list" class="btn btn-light">
                                Back
                            </Link>
                            <button type="submit" class="btn btn-primary">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
