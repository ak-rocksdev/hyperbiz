<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Props from controller
defineProps({
    user: Object, // { name, email }
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
</style>

<template>
    <Head title="Welcome - Get Started" />

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

            <!-- Main Welcome Card -->
            <div class="card shadow-lg">
                <div class="card-body p-8 lg:p-10">
                    <!-- Progress Steps -->
                    <div class="flex items-center justify-center mb-8">
                        <div class="flex items-center gap-2">
                            <!-- Step 1: Welcome (Active) -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-sm">
                                    1
                                </div>
                                <span class="ms-2 text-sm font-medium text-gray-900">Welcome</span>
                            </div>

                            <!-- Connector -->
                            <div class="w-12 h-0.5 bg-gray-200 mx-2"></div>

                            <!-- Step 2: Setup Company -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-semibold text-sm">
                                    2
                                </div>
                                <span class="ms-2 text-sm font-medium text-gray-500">Setup</span>
                            </div>

                            <!-- Connector -->
                            <div class="w-12 h-0.5 bg-gray-200 mx-2"></div>

                            <!-- Step 3: Get Started -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-semibold text-sm">
                                    3
                                </div>
                                <span class="ms-2 text-sm font-medium text-gray-500">Done</span>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center">
                            <i class="ki-filled ki-hand-wave text-primary text-4xl"></i>
                        </div>
                    </div>

                    <!-- Welcome Message -->
                    <div class="text-center mb-8">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-3">
                            Welcome, {{ user?.name || 'there' }}!
                        </h1>
                        <p class="text-gray-600 text-base leading-relaxed">
                            We're excited to have you on board. Let's get your business set up in just a few steps.
                        </p>
                    </div>

                    <!-- What's Next Section -->
                    <div class="bg-gray-50 dark:bg-coal-600 rounded-xl p-6 mb-8">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">
                            What's Next
                        </h3>
                        <div class="space-y-4">
                            <!-- Step Item 1 -->
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <i class="ki-filled ki-office-bag text-primary text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Set Up Your Company</h4>
                                    <p class="text-sm text-gray-600">Add your company details, contact information, and branding.</p>
                                </div>
                            </div>

                            <!-- Step Item 2 -->
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                                    <i class="ki-filled ki-rocket text-success text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Start Managing Your Business</h4>
                                    <p class="text-sm text-gray-600">Access your dashboard and explore all features available to you.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <Link
                        :href="route('onboarding.company-setup')"
                        class="btn btn-primary btn-lg w-full justify-center"
                    >
                        <span class="me-2">Let's Get Started</span>
                        <i class="ki-filled ki-arrow-right"></i>
                    </Link>

                    <!-- Support Text -->
                    <p class="text-center text-sm text-gray-500 mt-6">
                        Need help?
                        <a href="mailto:support@hyperbiz.com" class="link">Contact Support</a>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-500 mt-6">
                &copy; {{ new Date().getFullYear() }} HyperBiz. All rights reserved.
            </p>
        </div>
    </div>
</template>
