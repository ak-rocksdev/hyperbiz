<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const { props } = usePage();
const company = ref({ ...props.company });

// Compute logo URL with fallback
const logoUrl = computed(() => {
    return company.value.logo ? `/storage/${company.value.logo}` : null;
});

// Get company initials for fallback avatar
const companyInitials = computed(() => {
    if (!company.value.name) return 'CO';
    const words = company.value.name.split(' ');
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return company.value.name.substring(0, 2).toUpperCase();
});

// Format website URL for display (remove protocol)
const displayWebsite = computed(() => {
    if (!company.value.website) return null;
    return company.value.website.replace(/^https?:\/\//, '');
});

// Ensure website has protocol for href
const websiteHref = computed(() => {
    if (!company.value.website) return '#';
    if (company.value.website.startsWith('http')) {
        return company.value.website;
    }
    return `https://${company.value.website}`;
});
</script>

<template>
    <AppLayout title="Company Details">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Company Details
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Company Details</span>
            </div>

            <div class="grid gap-5 lg:gap-7.5">
                <!-- Profile Header Card -->
                <div class="card">
                    <div class="card-body p-6 lg:p-8">
                        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                            <!-- Logo Section -->
                            <div class="flex-shrink-0">
                                <div class="relative">
                                    <!-- Logo with fallback -->
                                    <div v-if="logoUrl" class="w-32 h-32 lg:w-40 lg:h-40 rounded-xl border-2 border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                                        <img
                                            :src="logoUrl"
                                            :alt="company.name + ' Logo'"
                                            class="max-w-full max-h-full object-contain p-2"
                                        />
                                    </div>
                                    <!-- Fallback initials avatar -->
                                    <div v-else class="w-32 h-32 lg:w-40 lg:h-40 rounded-xl bg-primary/10 flex items-center justify-center">
                                        <span class="text-4xl lg:text-5xl font-bold text-primary">{{ companyInitials }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Info Section -->
                            <div class="flex-grow">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <div>
                                        <!-- Company Name -->
                                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                            {{ company.name }}
                                        </h1>

                                        <!-- Quick Info Pills -->
                                        <div class="flex flex-wrap items-center gap-3 mb-4">
                                            <span v-if="company.email" class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                                <i class="ki-filled ki-sms text-gray-400"></i>
                                                {{ company.email }}
                                            </span>
                                            <span v-if="company.phone" class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                                <i class="ki-filled ki-phone text-gray-400"></i>
                                                {{ company.phone }}
                                            </span>
                                        </div>

                                        <!-- Website Badge -->
                                        <div v-if="company.website" class="flex items-center gap-2">
                                            <a
                                                :href="websiteHref"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-success/10 text-success hover:bg-success/20 transition-colors text-sm font-medium"
                                            >
                                                <i class="ki-filled ki-globe"></i>
                                                {{ displayWebsite }}
                                                <i class="ki-filled ki-arrow-up-right text-xs"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Action Button -->
                                    <div class="flex-shrink-0">
                                        <Link
                                            :href="route('company.edit', company.id)"
                                            class="btn btn-primary"
                                        >
                                            <i class="ki-filled ki-notepad-edit me-2"></i>
                                            Edit Company
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                    <!-- Contact Information Card -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-address-book text-gray-500"></i>
                                Contact Information
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <div class="space-y-5">
                                <!-- Email -->
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-sms text-primary"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Email Address</div>
                                        <div v-if="company.email" class="text-sm text-gray-800 font-medium">
                                            <a :href="'mailto:' + company.email" class="hover:text-primary transition-colors">
                                                {{ company.email }}
                                            </a>
                                        </div>
                                        <div v-else class="text-sm text-gray-400 italic">Not provided</div>
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-phone text-info"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Phone Number</div>
                                        <div v-if="company.phone" class="text-sm text-gray-800 font-medium">
                                            <a :href="'tel:' + company.phone" class="hover:text-primary transition-colors">
                                                {{ company.phone }}
                                            </a>
                                        </div>
                                        <div v-else class="text-sm text-gray-400 italic">Not provided</div>
                                    </div>
                                </div>

                                <!-- Website -->
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ki-filled ki-globe text-success"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Website</div>
                                        <div v-if="company.website" class="text-sm text-gray-800 font-medium">
                                            <a
                                                :href="websiteHref"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1.5 hover:text-primary transition-colors"
                                            >
                                                {{ displayWebsite }}
                                                <i class="ki-filled ki-arrow-up-right text-xs text-gray-400"></i>
                                            </a>
                                        </div>
                                        <div v-else class="text-sm text-gray-400 italic">Not provided</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Card -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-geolocation text-gray-500"></i>
                                Address
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-map text-warning"></i>
                                </div>
                                <div class="min-w-0 flex-grow">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Business Address</div>
                                    <div v-if="company.address" class="text-sm text-gray-800 font-medium leading-relaxed">
                                        {{ company.address }}
                                    </div>
                                    <div v-else class="text-sm text-gray-400 italic">Not provided</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metadata Card -->
                <div class="card">
                    <div class="card-header border-b border-gray-200">
                        <h3 class="card-title flex items-center gap-2">
                            <i class="ki-filled ki-calendar text-gray-500"></i>
                            Record Information
                        </h3>
                    </div>
                    <div class="card-body p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Created At -->
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-calendar-add text-gray-500"></i>
                                </div>
                                <div class="min-w-0 flex-grow">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Created</div>
                                    <div class="text-sm text-gray-800 font-medium">
                                        {{ company.created_at }}
                                        <span class="text-gray-500 font-normal">at {{ company.created_at_time }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Updated At -->
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-calendar-tick text-gray-500"></i>
                                </div>
                                <div class="min-w-0 flex-grow">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Last Updated</div>
                                    <div class="text-sm text-gray-800 font-medium">
                                        {{ company.updated_at }}
                                        <span class="text-gray-500 font-normal">at {{ company.updated_at_time }}</span>
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
