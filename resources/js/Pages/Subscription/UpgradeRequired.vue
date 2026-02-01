<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'expired',
    },
    message: {
        type: String,
        default: 'Your subscription has expired. Please upgrade to continue.',
    },
});

const page = usePage();

// Get status display info
const statusInfo = computed(() => {
    const statusMap = {
        expired: {
            title: 'Subscription Expired',
            icon: 'ki-filled ki-time',
            iconBg: 'bg-warning/10',
            iconColor: 'text-warning',
            description: 'Your trial period has ended. Upgrade now to continue using all features.',
        },
        suspended: {
            title: 'Account Suspended',
            icon: 'ki-filled ki-shield-cross',
            iconBg: 'bg-danger/10',
            iconColor: 'text-danger',
            description: 'Your account has been suspended due to payment issues. Please update your subscription.',
        },
    };
    return statusMap[props.status] || statusMap.expired;
});

// Get company info from shared props
const company = computed(() => page.props.company || {});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-coal-500 flex flex-col">
        <!-- Header -->
        <header class="bg-white dark:bg-coal-600 border-b border-gray-200 dark:border-coal-100">
            <div class="container-fixed py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img
                            src="/assets/media/app/hyperbiz-logo.png"
                            alt="HyperBiz"
                            class="h-8"
                        />
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ company.name }}
                        </span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="btn btn-sm btn-light"
                        >
                            <i class="ki-filled ki-exit-right me-1.5"></i>
                            Logout
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-lg">
                <!-- Status Card -->
                <div class="card shadow-lg">
                    <div class="card-body p-8 lg:p-10 text-center">
                        <!-- Icon -->
                        <div class="flex justify-center mb-6">
                            <div :class="[
                                'w-20 h-20 rounded-full flex items-center justify-center',
                                statusInfo.iconBg
                            ]">
                                <i :class="[statusInfo.icon, statusInfo.iconColor, 'text-4xl']"></i>
                            </div>
                        </div>

                        <!-- Title -->
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                            {{ statusInfo.title }}
                        </h1>

                        <!-- Description -->
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ statusInfo.description }}
                        </p>

                        <!-- Message from server -->
                        <div class="bg-gray-50 dark:bg-coal-500 rounded-lg p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="ki-filled ki-information-2 text-gray-500 mt-0.5"></i>
                                <p class="text-sm text-gray-600 dark:text-gray-400 text-left">
                                    {{ message }}
                                </p>
                            </div>
                        </div>

                        <!-- What you can do -->
                        <div class="text-left mb-8">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                While in read-only mode, you can:
                            </h3>
                            <ul class="space-y-2">
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="ki-filled ki-check-circle text-success text-xs"></i>
                                    View all your existing data
                                </li>
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="ki-filled ki-check-circle text-success text-xs"></i>
                                    Export reports and documents
                                </li>
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="ki-filled ki-check-circle text-success text-xs"></i>
                                    Access your profile settings
                                </li>
                            </ul>
                        </div>

                        <!-- What's blocked -->
                        <div class="text-left mb-8">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                You cannot:
                            </h3>
                            <ul class="space-y-2">
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="ki-filled ki-cross-circle text-danger text-xs"></i>
                                    Create new records (products, orders, customers)
                                </li>
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="ki-filled ki-cross-circle text-danger text-xs"></i>
                                    Edit or update existing data
                                </li>
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="ki-filled ki-cross-circle text-danger text-xs"></i>
                                    Delete any records
                                </li>
                            </ul>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <Link
                                :href="route('subscription.plans')"
                                class="btn btn-primary flex-1 justify-center"
                            >
                                <i class="ki-filled ki-crown me-2"></i>
                                Upgrade Now
                            </Link>
                            <Link
                                :href="route('subscription.index')"
                                class="btn btn-light flex-1 justify-center"
                            >
                                <i class="ki-filled ki-eye me-2"></i>
                                View Subscription
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Back to dashboard link -->
                <div class="text-center mt-6">
                    <Link
                        href="/"
                        class="text-sm text-gray-500 hover:text-primary transition-colors"
                    >
                        <i class="ki-filled ki-arrow-left me-1"></i>
                        Back to Dashboard (Read-Only)
                    </Link>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-coal-600 border-t border-gray-200 dark:border-coal-100 py-4">
            <div class="container-fixed">
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    Need help? Contact us at
                    <a href="mailto:support@hyperbiz.com" class="text-primary hover:underline">
                        support@hyperbiz.com
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>
