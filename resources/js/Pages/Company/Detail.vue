<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const { props } = usePage();
const company = ref({ ...props.company });

const logoPreview = ref(company.value.logo ? `/storage/${company.value.logo}` : null);
</script>

<template>
    <AppLayout title="Edit Company">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Company
            </h2>
        </template>

        <div class="container-fixed">
            <div class="grid grid-cols-1 gap-5 lg:gap-7.5">
                <div class="card min-w-1/2 sm:w-full lg:w-1/2">
                    <div class="card-header">
                        <h3 class="card-title">
                            Company Information
                        </h3>
                        <Link class="btn btn-link btn-clear btn-primary py-2 px-3 rounded-lg transition-colors" :href="route('company.edit', company.id)">
                            <i class="ki-filled ki-notepad-edit"></i>
                            Edit
                        </Link>
                    </div>
                    <div class="card-table scrollable-x-auto pb-3">
                        <table class="table align-middle text-sm text-gray-500">
                            <tr>
                                <td class="py-2 min-w-28 text-gray-600 font-normal lg:w-[200px]">
                                    Company Logo
                                </td>
                                <td class="py-2 text-gray700 font-normal min-w-32 text-2sm flex items-center group hover:cursor-pointer">
                                    <img :src="logoPreview" class="max-h-[36px] max-w-full h-auto rounded-lg" alt="Company Logo">
                                    <i class="ki-filled ki-magnifier text-lg ms-2 hidden group-hover:inline text-gray-600"></i>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-600 font-normal">
                                    Name
                                </td>
                                <td class="py-2 text-gray-800 font-normal text-sm">
                                    {{ company.name }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 text-gray-600 font-normal">
                                    Website
                                </td>
                                <td class="py-3 text-gray-800 font-normal flex justify-between">
                                    <span class="badge badge-sm badge-outline badge-success me-3">
                                        {{ company.website }}
                                    </span>
                                    <!-- visit link -->
                                    <a :href="company.website" target="_blank" class="btn btn-link btn-sm">
                                        <i class="ki-filled ki-arrow-up-right me-2"></i>
                                        Visit
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 text-gray-600 font-normal">
                                    Email
                                </td>
                                <td class="py-3 text-gray-700 text-sm font-normal">
                                    {{ company.email }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 text-gray-600 font-normal">
                                    Phone
                                </td>
                                <td class="py-3 text-gray-700 text-sm font-normal">
                                    {{ company.phone }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3">
                                    Address
                                </td>
                                <td class="py-3 text-gray-700 text-2sm font-normal">
                                    {{ company.address }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
