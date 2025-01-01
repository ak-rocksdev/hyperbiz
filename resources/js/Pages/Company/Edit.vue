<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const company = ref({ ...props.company });

const updateCompany = () => {
    axios.put(`/company/api/update/${company.value.id}`, company.value)
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
                text: error.response.data.message || 'An error occurred while updating the company.',
            });
            console.error(error);
        });
};
</script>

<template>
    <AppLayout title="Edit Company">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Company
            </h2>
        </template>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Edit Company
                        </h3>
                    </div>
                    <form class="card-body" @submit.prevent="updateCompany">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- First Column -->
                            <div class="grid grid-cols-2 gap-5 p-5">
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Company Name</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Company Name"
                                        type="text"
                                        v-model="company.name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Company Phone</label>
                                    <input
                                        class="input"
                                        placeholder="Company Phone"
                                        type="text"
                                        v-model="company.phone"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Email</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Company Email"
                                        type="email"
                                        v-model="company.email"
                                    />
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
                                        v-model="company.address.address"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">City</label>
                                    <input
                                        class="input"
                                        placeholder="Enter City"
                                        type="text"
                                        v-model="company.address.city_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">State</label>
                                    <input
                                        class="input"
                                        placeholder="Enter State"
                                        type="text"
                                        v-model="company.address.state_name"
                                    />
                                </div>
                                <div>
                                    <label class="form-label mb-2 !font-extrabold text-md !text-blue-500">Country</label>
                                    <input
                                        class="input"
                                        placeholder="Enter Country"
                                        type="text"
                                        v-model="company.address.country_name"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="flex justify-between px-5 pb-5">
                            <Link href="/company/list" class="btn btn-light">
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
