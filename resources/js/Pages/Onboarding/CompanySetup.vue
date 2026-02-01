<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import InputError from '@/Components/InputError.vue';

// Props from controller
defineProps({
    user: Object, // { name, email }
});

// Industry options
const industries = [
    { value: '', label: 'Select an industry' },
    { value: 'retail', label: 'Retail & E-commerce' },
    { value: 'manufacturing', label: 'Manufacturing' },
    { value: 'wholesale', label: 'Wholesale & Distribution' },
    { value: 'services', label: 'Professional Services' },
    { value: 'healthcare', label: 'Healthcare' },
    { value: 'technology', label: 'Technology & Software' },
    { value: 'food_beverage', label: 'Food & Beverage' },
    { value: 'construction', label: 'Construction' },
    { value: 'education', label: 'Education' },
    { value: 'real_estate', label: 'Real Estate' },
    { value: 'logistics', label: 'Logistics & Transportation' },
    { value: 'other', label: 'Other' },
];

// Form using Inertia's useForm
const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    industry: '',
});

// Submit handler
const submit = () => {
    form.post(route('onboarding.store-company'), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

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
    <Head title="Company Setup - Onboarding" />

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg py-10">
        <div class="w-full max-w-2xl px-4">
            <!-- Logo Section -->
            <div class="flex justify-center mb-8">
                <img
                    src="/assets/media/app/bkpi_square_logo.png"
                    alt="HyperBiz Logo"
                    class="h-16 w-auto"
                />
            </div>

            <!-- Main Setup Card -->
            <div class="card shadow-lg">
                <div class="card-body p-8 lg:p-10">
                    <!-- Progress Steps -->
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

                            <!-- Step 2: Setup Company (Active) -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-sm">
                                    2
                                </div>
                                <span class="ms-2 text-sm font-medium text-gray-900">Setup</span>
                            </div>

                            <!-- Connector -->
                            <div class="w-12 h-0.5 bg-gray-200 mx-2"></div>

                            <!-- Step 3: Done -->
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-semibold text-sm">
                                    3
                                </div>
                                <span class="ms-2 text-sm font-medium text-gray-500">Done</span>
                            </div>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="text-center mb-8">
                        <div class="flex justify-center mb-4">
                            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-office-bag text-primary text-3xl"></i>
                            </div>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            Set Up Your Company
                        </h1>
                        <p class="text-gray-600">
                            Tell us about your business so we can personalize your experience.
                        </p>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Company Name -->
                        <div>
                            <label for="company-name" class="form-label mb-2">
                                Company Name
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="btn btn-input">
                                    <i class="ki-filled ki-abstract-14 text-gray-500"></i>
                                </span>
                                <input
                                    id="company-name"
                                    type="text"
                                    class="input"
                                    :class="{ 'border-danger': form.errors.name }"
                                    v-model="form.name"
                                    placeholder="Enter your company name"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Two Column Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Email -->
                            <div>
                                <label for="company-email" class="form-label mb-2">
                                    Company Email
                                </label>
                                <div class="input-group">
                                    <span class="btn btn-input">
                                        <i class="ki-filled ki-sms text-gray-500"></i>
                                    </span>
                                    <input
                                        id="company-email"
                                        type="email"
                                        class="input"
                                        :class="{ 'border-danger': form.errors.email }"
                                        v-model="form.email"
                                        placeholder="company@example.com"
                                    />
                                </div>
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="company-phone" class="form-label mb-2">
                                    Phone Number
                                </label>
                                <div class="input-group">
                                    <span class="btn btn-input">
                                        <i class="ki-filled ki-phone text-gray-500"></i>
                                    </span>
                                    <input
                                        id="company-phone"
                                        type="tel"
                                        class="input"
                                        :class="{ 'border-danger': form.errors.phone }"
                                        v-model="form.phone"
                                        placeholder="+1 (555) 000-0000"
                                    />
                                </div>
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>

                        <!-- Industry -->
                        <div>
                            <label for="company-industry" class="form-label mb-2">
                                Industry
                            </label>
                            <select
                                id="company-industry"
                                class="select"
                                :class="{ 'border-danger': form.errors.industry }"
                                v-model="form.industry"
                            >
                                <option
                                    v-for="option in industries"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.industry" />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="company-website" class="form-label mb-2">
                                Website
                            </label>
                            <div class="input-group">
                                <span class="btn btn-input">
                                    <i class="ki-filled ki-globe text-gray-500"></i>
                                </span>
                                <input
                                    id="company-website"
                                    type="url"
                                    class="input"
                                    :class="{ 'border-danger': form.errors.website }"
                                    v-model="form.website"
                                    placeholder="https://www.example.com"
                                />
                            </div>
                            <InputError :message="form.errors.website" />
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="company-address" class="form-label mb-2">
                                Business Address
                            </label>
                            <div class="input-group items-start">
                                <span class="btn btn-input">
                                    <i class="ki-filled ki-geolocation text-gray-500"></i>
                                </span>
                                <textarea
                                    id="company-address"
                                    class="textarea"
                                    :class="{ 'border-danger': form.errors.address }"
                                    v-model="form.address"
                                    placeholder="Enter your full business address"
                                    rows="3"
                                ></textarea>
                            </div>
                            <InputError :message="form.errors.address" />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-4 border-t border-gray-200">
                            <!-- Back Button -->
                            <Link
                                :href="route('onboarding.welcome')"
                                class="btn btn-light w-full sm:w-auto"
                            >
                                <i class="ki-filled ki-arrow-left me-2"></i>
                                Back
                            </Link>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                class="btn btn-primary w-full sm:w-auto min-w-[180px] justify-center"
                                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Creating...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    Create Company
                                    <i class="ki-filled ki-arrow-right"></i>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Text -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-500">
                    <i class="ki-filled ki-information-2 me-1"></i>
                    You can update these details later in your company settings.
                </p>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-500 mt-4">
                &copy; {{ new Date().getFullYear() }} HyperBiz. All rights reserved.
            </p>
        </div>
    </div>
</template>
