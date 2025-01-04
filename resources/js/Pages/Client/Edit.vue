<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const client = ref({ ...props.client });
const clientTypes = ref({ ...props.clientTypes });
watch(() => client.value.is_customer, (newValue) => {
    console.log('Is Customer:', newValue);
});

const updateClient = () => {
    axios.put(`/client/api/update/${client.value.id}`, client.value)
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
                text: error.response.data.message || 'An error occurred while updating the client.',
            });
            console.error(error);
        });
};
</script>

<template>
    <AppLayout title="Edit Client">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Client
            </h2>
        </template>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Edit Client
                        </h3>
                    </div>
                    <form class="card-body" @submit.prevent="updateClient">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- First Column -->
                            <div class="grid grid-cols-2 gap-5 p-5">
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Client Name</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Client Name"
                                        type="text"
                                        v-model="client.client_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Client Phone</label>
                                    <input
                                        class="input"
                                        placeholder="Client Phone"
                                        type="text"
                                        v-model="client.client_phone_number"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Email</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Client Email"
                                        type="email"
                                        v-model="client.email"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Contact Person</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Contact Person"
                                        type="text"
                                        v-model="client.contact_person"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Contact Person Phone</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Contact Person Phone"
                                        type="text"
                                        v-model="client.contact_person_phone_number"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Client Type</label>
                                    <select
                                        class="select"
                                        v-model="client.mst_client_type_id"
                                    >
                                        <option disabled value="">Select Client Type</option>
                                        <option
                                            v-for="(name, id) in clientTypes"
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
                                        <input class="order-2" type="checkbox" v-model="client.is_customer"
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
                                        v-model="client.address.address"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">City</label>
                                    <input
                                        class="input"
                                        placeholder="Enter City"
                                        type="text"
                                        v-model="client.address.city_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">State</label>
                                    <input
                                        class="input"
                                        placeholder="Enter State"
                                        type="text"
                                        v-model="client.address.state_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Country</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Country"
                                        type="text"
                                        v-model="client.address.country_name"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="flex justify-between px-5 pb-5">
                            <Link href="/client/list" class="btn btn-light">
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
