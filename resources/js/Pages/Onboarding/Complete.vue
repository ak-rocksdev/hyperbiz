<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, computed } from 'vue';

// Props from controller
const props = defineProps({
    user: Object, // { name, email }
    company: Object, // { id, name, trial_ends_at, trial_days_remaining }
    trial_days: Number, // configured trial period in days
});

// Format trial end date
const formattedTrialEndDate = computed(() => {
    if (!props.company?.trial_ends_at) return null;
    const date = new Date(props.company.trial_ends_at);
    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
});

// Initialize Metronic theme and load required assets
onMounted(() => {
    // Function to load a script dynamically
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            // Check if script is already loaded
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });
    }

    // Load Metronic scripts
    loadScript('/assets/js/core.bundle.js')
        .then(() => {
            if (typeof KTDom !== 'undefined') {
                KTDom.ready(() => {
                    KTComponents.init();
                });
            }
        })
        .catch(err => console.error('Failed to load script:', err));

    // Set HTML attributes for Metronic theme
    document.documentElement.setAttribute('data-theme', 'true');
    document.documentElement.setAttribute('dir', 'ltr');
    document.documentElement.setAttribute('lang', 'en');
    document.documentElement.setAttribute('data-theme-mode', 'light');

    // Load Metronic CSS if not already loaded
    if (!document.querySelector('link[href="/assets/vendors/keenicons/styles.bundle.css"]')) {
        const link1 = document.createElement('link');
        link1.href = '/assets/vendors/keenicons/styles.bundle.css';
        link1.rel = 'stylesheet';
        document.head.appendChild(link1);
    }

    if (!document.querySelector('link[href="/assets/css/styles.css"]')) {
        const link2 = document.createElement('link');
        link2.href = '/assets/css/styles.css';
        link2.rel = 'stylesheet';
        document.head.appendChild(link2);
    }

    // Set body classes
    document.body.className = 'antialiased flex h-full text-base text-gray-700 dark:bg-coal-500';
    document.getElementById('app').className = 'h-full w-full';
});
</script>

<style scoped>
.page-bg {
    background-image: url('/assets/media/images/2600x1200/bg-10.png');
    min-height: 100vh;
}

.dark .page-bg {
    background-image: url('/assets/media/images/2600x1200/bg-10-dark.png');
}

/* Confetti animation for celebration effect */
@keyframes float-up {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(-100px) rotate(720deg);
        opacity: 0;
    }
}

.confetti-piece {
    position: absolute;
    width: 10px;
    height: 10px;
    animation: float-up 3s ease-out forwards;
}

/* Pulse animation for success icon */
@keyframes pulse-success {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 20px rgba(34, 197, 94, 0);
    }
}

.success-icon-animate {
    animation: pulse-success 2s ease-in-out infinite;
}
</style>

<template>
    <Head title="Setup Complete - Welcome to HyperBiz" />

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg py-10">
        <div class="w-full max-w-xl px-4">
            <!-- Logo Section -->
            <div class="flex justify-center mb-8">
                <img
                    src="/assets/media/app/bkpi_square_logo.png"
                    alt="HyperBiz Logo"
                    class="h-16 w-auto"
                />
            </div>

            <!-- Main Completion Card -->
            <div class="card shadow-lg overflow-hidden">
                <!-- Success Banner -->
                <div class="bg-gradient-to-r from-success to-success-active py-6 px-8 text-center relative">
                    <!-- Decorative Elements -->
                    <div class="absolute top-2 left-4 w-3 h-3 bg-white/20 rounded-full"></div>
                    <div class="absolute top-6 left-12 w-2 h-2 bg-white/30 rounded-full"></div>
                    <div class="absolute bottom-4 right-8 w-4 h-4 bg-white/20 rounded-full"></div>
                    <div class="absolute top-4 right-16 w-2 h-2 bg-white/25 rounded-full"></div>

                    <div class="relative z-10">
                        <div class="flex justify-center mb-3">
                            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center success-icon-animate">
                                <i class="ki-filled ki-check-circle text-success text-4xl"></i>
                            </div>
                        </div>
                        <h1 class="text-xl font-bold text-white">
                            Setup Complete!
                        </h1>
                    </div>
                </div>

                <div class="card-body p-8 lg:p-10">
                    <!-- Progress Steps (All Complete) -->
                    <div class="flex items-center justify-center mb-8">
                        <div class="flex items-center gap-2">
                            <!-- Step 1: Welcome (Completed) -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-success flex items-center justify-center text-white">
                                    <i class="ki-filled ki-check text-sm"></i>
                                </div>
                                <span class="ms-2 text-sm font-medium text-success">Welcome</span>
                            </div>

                            <!-- Connector (completed) -->
                            <div class="w-12 h-0.5 bg-success mx-2"></div>

                            <!-- Step 2: Setup (Completed) -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-success flex items-center justify-center text-white">
                                    <i class="ki-filled ki-check text-sm"></i>
                                </div>
                                <span class="ms-2 text-sm font-medium text-success">Setup</span>
                            </div>

                            <!-- Connector (completed) -->
                            <div class="w-12 h-0.5 bg-success mx-2"></div>

                            <!-- Step 3: Done (Active/Complete) -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-success flex items-center justify-center text-white">
                                    <i class="ki-filled ki-check text-sm"></i>
                                </div>
                                <span class="ms-2 text-sm font-medium text-success">Done</span>
                            </div>
                        </div>
                    </div>

                    <!-- Celebration Message -->
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-3">
                            Welcome to HyperBiz, {{ user?.name || 'there' }}!
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            Your company <strong class="text-gray-900">{{ company?.name }}</strong> has been created successfully.
                            You're all set to start managing your business.
                        </p>
                    </div>

                    <!-- Trial Info Card -->
                    <div
                        v-if="company?.trial_days_remaining"
                        class="bg-info/10 border border-info/20 rounded-xl p-5 mb-8"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-info/20 flex items-center justify-center">
                                <i class="ki-filled ki-gift text-info text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-info mb-1">
                                    Free Trial Active ({{ trial_days }} days)
                                </h4>
                                <p class="text-sm text-gray-600">
                                    You have <strong class="text-gray-900">{{ company.trial_days_remaining }} days</strong> remaining in your free trial.
                                    <span v-if="formattedTrialEndDate">
                                        Your trial ends on {{ formattedTrialEndDate }}.
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Start Section -->
                    <div class="bg-gray-50 dark:bg-coal-600 rounded-xl p-6 mb-8">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">
                            Quick Start
                        </h3>
                        <div class="space-y-3">
                            <!-- Quick Action 1: Add Customer -->
                            <Link
                                :href="route('customer.list')"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-coal-500 rounded-lg border border-gray-200 dark:border-coal-400 hover:border-primary hover:bg-primary/5 transition-colors cursor-pointer"
                            >
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <i class="ki-filled ki-user text-primary text-lg"></i>
                                </div>
                                <div class="flex-grow">
                                    <span class="text-sm font-medium text-gray-900">Add your first customer</span>
                                </div>
                                <i class="ki-filled ki-arrow-right text-gray-400"></i>
                            </Link>

                            <!-- Quick Action 2: Add Products -->
                            <Link
                                :href="route('product.list')"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-coal-500 rounded-lg border border-gray-200 dark:border-coal-400 hover:border-success hover:bg-success/5 transition-colors cursor-pointer"
                            >
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                    <i class="ki-filled ki-handcart text-success text-lg"></i>
                                </div>
                                <div class="flex-grow">
                                    <span class="text-sm font-medium text-gray-900">Add your products</span>
                                </div>
                                <i class="ki-filled ki-arrow-right text-gray-400"></i>
                            </Link>

                            <!-- Quick Action 3: Create Sales Order -->
                            <Link
                                :href="route('sales-orders.create')"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-coal-500 rounded-lg border border-gray-200 dark:border-coal-400 hover:border-warning hover:bg-warning/5 transition-colors cursor-pointer"
                            >
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                                    <i class="ki-filled ki-document text-warning text-lg"></i>
                                </div>
                                <div class="flex-grow">
                                    <span class="text-sm font-medium text-gray-900">Create your first order</span>
                                </div>
                                <i class="ki-filled ki-arrow-right text-gray-400"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <Link
                        :href="route('dashboard')"
                        class="btn btn-primary btn-lg w-full justify-center"
                    >
                        <i class="ki-filled ki-element-11 me-2"></i>
                        Go to Dashboard
                    </Link>

                    <!-- Additional Links -->
                    <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mt-6">
                        <Link :href="route('company.detail', { id: company?.id })" class="text-sm text-gray-600 hover:text-primary transition-colors">
                            <i class="ki-filled ki-setting-2 me-1"></i>
                            Company Settings
                        </Link>
                        <span class="hidden sm:inline text-gray-300">|</span>
                        <a href="mailto:support@hyperbiz.com" class="text-sm text-gray-600 hover:text-primary transition-colors">
                            <i class="ki-filled ki-message-question me-1"></i>
                            Get Help
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-500 mt-6">
                &copy; {{ new Date().getFullYear() }} HyperBiz. All rights reserved.
            </p>
        </div>
    </div>
</template>
