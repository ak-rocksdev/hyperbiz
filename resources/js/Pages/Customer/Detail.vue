<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, onMounted } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';

    const { props } = usePage();
    const customer = ref({});

    onMounted(async () => {
        customer.value = props.customer;
    });
</script>

<template>
    <AppLayout title="Customers">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Customer Detail
            </h2>
        </template>

        <div class="container-fixed">
            <div class="flex justify-end mb-5">
                <Link :href="'/customer/list'" class="btn btn-primary">
                    Back
                </Link>
            </div>
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header gap-5">
                        <h3 class="card-title">
                            Customer Detail
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-5">
                            <div class="p-5">
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Customer Name</label>
                                    <div class="flex items-center gap-2.5">
                                        <span class="!text-gray-500">{{ customer.name }}</span>
                                        <span class="badge badge-outline badge-success">
                                            {{ customer.customer_type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Email</label>
                                    <p class="!text-gray-500">{{ customer.email }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Customer Phone</label>
                                    <p class="!text-gray-500">{{ customer.phone_number }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Contact Person</label>
                                    <p v-if="customer.contact_person"  class="!text-gray-500">{{ customer.contact_person }}</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Contact Person Phone Number</label>
                                    <p v-if="customer.contact_person_phone_number" class="!text-gray-500">{{ customer.contact_person_phone_number }}</p>
                                    <p v-else class="!text-gray-500">N/A</p>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label mb-1 !font-extrabold text-md !text-blue-500">Address</label>
                                    <div v-if="customer.address">
                                        <p class="!text-gray-500">{{ customer.address?.address }}</p>
                                        <p class="!text-gray-500">{{ customer.address?.city_name }}, {{ customer.address?.state_name }}</p>
                                        <p class="!text-gray-500">{{ customer.address?.country_name }}</p>
                                    </div>
                                    <div v-else>
                                        <p class="!text-gray-500">N/A</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
