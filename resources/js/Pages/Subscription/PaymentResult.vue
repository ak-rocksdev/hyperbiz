<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    success: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
    company: {
        type: Object,
        default: null,
    },
    invoiceId: {
        type: [Number, String],
        default: null,
    },
});

const iconClass = computed(() => {
    return props.success
        ? 'ki-filled ki-check-circle text-success'
        : 'ki-filled ki-cross-circle text-danger';
});

const goToDashboard = () => {
    router.visit('/subscription');
};

const retryPayment = () => {
    if (props.invoiceId) {
        router.visit(`/subscription/payment-proof/${props.invoiceId}`);
    } else {
        router.visit('/subscription/plans');
    }
};
</script>

<template>
    <AppLayout title="Payment Result">
        <div class="container-fixed">
            <div class="flex flex-col items-center justify-center min-h-[60vh] py-10">
                <!-- Result Card -->
                <div class="card max-w-lg w-full text-center">
                    <div class="card-body py-12 px-8">
                        <!-- Icon -->
                        <div class="mb-6">
                            <i :class="iconClass" class="text-7xl"></i>
                        </div>

                        <!-- Title -->
                        <h1 class="text-2xl font-bold text-gray-900 mb-3">
                            {{ title }}
                        </h1>

                        <!-- Message -->
                        <p class="text-gray-600 mb-8">
                            {{ message }}
                        </p>

                        <!-- Company Info (success only) -->
                        <div v-if="success && company" class="bg-success-light rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-center gap-3">
                                <i class="ki-filled ki-building text-success text-xl"></i>
                                <div class="text-left">
                                    <p class="font-medium text-gray-900">{{ company.name }}</p>
                                    <p class="text-sm text-success">
                                        Status: {{ company.subscription_status?.charAt(0).toUpperCase() + company.subscription_status?.slice(1) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button
                                v-if="success"
                                @click="goToDashboard"
                                class="btn btn-primary"
                            >
                                <i class="ki-filled ki-home-2 mr-2"></i>
                                Go to Subscription Dashboard
                            </button>

                            <template v-else>
                                <button
                                    @click="retryPayment"
                                    class="btn btn-primary"
                                >
                                    <i class="ki-filled ki-arrows-loop mr-2"></i>
                                    Try Again
                                </button>
                                <button
                                    @click="goToDashboard"
                                    class="btn btn-light"
                                >
                                    <i class="ki-filled ki-arrow-left mr-2"></i>
                                    Back to Dashboard
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-8 text-center">
                    <p class="text-gray-500 text-sm">
                        Need help?
                        <a href="mailto:support@hyperbiz.com" class="text-primary hover:underline">
                            Contact Support
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
