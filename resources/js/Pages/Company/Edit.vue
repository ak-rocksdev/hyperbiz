<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const { props } = usePage();

// Form state - create a deep copy to track changes
const originalData = JSON.parse(JSON.stringify(props.company));
const form = ref({
    name: props.company.name || '',
    email: props.company.email || '',
    phone: props.company.phone || '',
    address: props.company.address || '',
    website: props.company.website || '',
});

// Logo state
const logoFile = ref(null);
const logoPreview = ref(props.company.logo ? `/storage/${props.company.logo}` : null);
const originalLogo = props.company.logo ? `/storage/${props.company.logo}` : null;
const removeLogo = ref(false);

// UI state
const isSubmitting = ref(false);
const errors = ref({});
const fileInputRef = ref(null);

// Computed: Check if form has unsaved changes
const hasUnsavedChanges = computed(() => {
    const formChanged =
        form.value.name !== (originalData.name || '') ||
        form.value.email !== (originalData.email || '') ||
        form.value.phone !== (originalData.phone || '') ||
        form.value.address !== (originalData.address || '') ||
        form.value.website !== (originalData.website || '');

    const logoChanged = logoFile.value !== null || removeLogo.value;

    return formChanged || logoChanged;
});

// Computed: Get company initials for fallback avatar
const companyInitials = computed(() => {
    if (!form.value.name) return 'CO';
    const words = form.value.name.split(' ');
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return form.value.name.substring(0, 2).toUpperCase();
});

// Client-side validation
const validateForm = () => {
    const newErrors = {};

    // Name validation
    if (!form.value.name.trim()) {
        newErrors.name = 'Company name is required.';
    } else if (form.value.name.length > 255) {
        newErrors.name = 'Company name cannot exceed 255 characters.';
    }

    // Email validation
    if (!form.value.email.trim()) {
        newErrors.email = 'Email address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        newErrors.email = 'Please enter a valid email address.';
    } else if (form.value.email.length > 255) {
        newErrors.email = 'Email cannot exceed 255 characters.';
    }

    // Phone validation
    if (!form.value.phone.trim()) {
        newErrors.phone = 'Phone number is required.';
    } else if (form.value.phone.length > 20) {
        newErrors.phone = 'Phone number cannot exceed 20 characters.';
    }

    // Address validation
    if (!form.value.address.trim()) {
        newErrors.address = 'Address is required.';
    } else if (form.value.address.length > 500) {
        newErrors.address = 'Address cannot exceed 500 characters.';
    }

    // Website validation (optional)
    if (form.value.website && form.value.website.trim()) {
        const urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
        if (!urlPattern.test(form.value.website)) {
            newErrors.website = 'Please enter a valid website URL.';
        } else if (form.value.website.length > 255) {
            newErrors.website = 'Website URL cannot exceed 255 characters.';
        }
    }

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
};

// Handle logo file selection
const handleLogoChange = (event) => {
    const file = event.target.files[0];

    if (!file) return;

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        errors.value.logo = 'Logo must be a JPEG, PNG, or JPG file.';
        return;
    }

    // Validate file size (2MB max)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        errors.value.logo = 'Logo file size cannot exceed 2MB.';
        return;
    }

    // Clear any previous logo errors
    delete errors.value.logo;

    // Set the file and preview
    logoFile.value = file;
    removeLogo.value = false;
    logoPreview.value = URL.createObjectURL(file);
};

// Trigger file input click
const triggerFileInput = () => {
    fileInputRef.value?.click();
};

// Remove logo
const handleRemoveLogo = () => {
    logoFile.value = null;
    logoPreview.value = null;
    removeLogo.value = true;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
    delete errors.value.logo;
};

// Clear field error on input
const clearError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

// Submit form
const submitForm = async () => {
    // Run client-side validation first
    if (!validateForm()) {
        // Scroll to first error
        const firstErrorField = document.querySelector('.border-danger');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('email', form.value.email);
    formData.append('phone', form.value.phone);
    formData.append('address', form.value.address);
    formData.append('website', form.value.website || '');

    if (logoFile.value) {
        formData.append('logo', logoFile.value);
    }

    if (removeLogo.value) {
        formData.append('remove_logo', '1');
    }

    try {
        const response = await axios.post(`/company/api/update/${props.company.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        // Show success toast
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: 'success',
            title: response.data.message || 'Company updated successfully.',
        });

        // Redirect to detail page
        router.visit(route('company.detail', { id: props.company.id }));

    } catch (error) {
        isSubmitting.value = false;

        if (error.response?.status === 422) {
            // Validation errors from server
            const serverErrors = error.response.data.errors || {};
            errors.value = {};
            Object.keys(serverErrors).forEach(key => {
                errors.value[key] = serverErrors[key][0] || serverErrors[key];
            });
        } else {
            // General error
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while updating the company.',
            });
        }
    }
};

// Handle cancel with unsaved changes warning
const handleCancel = () => {
    if (hasUnsavedChanges.value) {
        Swal.fire({
            title: 'Unsaved Changes',
            text: 'You have unsaved changes. Are you sure you want to leave?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, leave',
            cancelButtonText: 'Stay',
        }).then((result) => {
            if (result.isConfirmed) {
                router.visit(route('company.detail', { id: props.company.id }));
            }
        });
    } else {
        router.visit(route('company.detail', { id: props.company.id }));
    }
};

// Warn before leaving page with unsaved changes
const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

// Add/remove beforeunload listener
watch(hasUnsavedChanges, (newVal) => {
    if (newVal) {
        window.addEventListener('beforeunload', handleBeforeUnload);
    } else {
        window.removeEventListener('beforeunload', handleBeforeUnload);
    }
}, { immediate: true });

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    // Clean up object URL if created
    if (logoFile.value && logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
});
</script>

<template>
    <AppLayout title="Edit Company">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Company
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <Link
                    :href="route('company.detail', { id: props.company.id })"
                    class="text-gray-500 hover:text-primary transition-colors"
                >
                    Company Details
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Edit</span>
            </div>

            <!-- Unsaved Changes Indicator -->
            <div
                v-if="hasUnsavedChanges"
                class="mb-5 flex items-center gap-2 px-4 py-3 rounded-lg bg-warning/10 border border-warning/30"
            >
                <i class="ki-filled ki-information-2 text-warning"></i>
                <span class="text-sm text-warning font-medium">You have unsaved changes</span>
            </div>

            <form @submit.prevent="submitForm" novalidate>
                <div class="grid gap-5 lg:gap-7.5">
                    <!-- Logo & Basic Information Card -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-profile-circle text-gray-500"></i>
                                Company Information
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <div class="flex flex-col lg:flex-row gap-8">
                                <!-- Logo Upload Section -->
                                <div class="flex-shrink-0">
                                    <label class="form-label mb-3">
                                        Company Logo
                                    </label>

                                    <!-- Hidden file input -->
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept=".jpg,.jpeg,.png"
                                        class="hidden"
                                        @change="handleLogoChange"
                                        aria-label="Upload company logo"
                                    />

                                    <!-- Logo upload area -->
                                    <div class="relative group">
                                        <!-- With logo preview -->
                                        <div
                                            v-if="logoPreview"
                                            class="relative w-40 h-40 rounded-xl border-2 border-gray-200 overflow-hidden bg-gray-50 cursor-pointer hover:border-primary transition-colors"
                                            @click="triggerFileInput"
                                            role="button"
                                            tabindex="0"
                                            aria-label="Click to change logo"
                                            @keydown.enter="triggerFileInput"
                                            @keydown.space.prevent="triggerFileInput"
                                        >
                                            <img
                                                :src="logoPreview"
                                                alt="Company logo preview"
                                                class="w-full h-full object-contain p-2"
                                            />
                                            <!-- Hover overlay -->
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <i class="ki-filled ki-camera text-white text-2xl"></i>
                                            </div>
                                        </div>
                                        <!-- Remove button - positioned outside overflow-hidden container -->
                                        <button
                                            v-if="logoPreview"
                                            type="button"
                                            class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-danger text-white flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity hover:bg-danger-active z-10"
                                            @click.stop="handleRemoveLogo"
                                            aria-label="Remove logo"
                                        >
                                            <i class="ki-filled ki-cross text-sm"></i>
                                        </button>

                                        <!-- Without logo - upload area -->
                                        <div
                                            v-else
                                            class="w-40 h-40 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 cursor-pointer hover:border-primary hover:bg-primary/5 transition-all flex flex-col items-center justify-center gap-2"
                                            @click="triggerFileInput"
                                            role="button"
                                            tabindex="0"
                                            aria-label="Click to upload logo"
                                            @keydown.enter="triggerFileInput"
                                            @keydown.space.prevent="triggerFileInput"
                                        >
                                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="ki-filled ki-picture text-gray-500 text-xl"></i>
                                            </div>
                                            <span class="text-sm text-gray-500 font-medium">Upload Logo</span>
                                        </div>
                                    </div>

                                    <!-- Logo hints -->
                                    <p class="text-xs text-gray-500 mt-2 max-w-[160px]">
                                        Accepted formats: JPG, PNG. Max size: 2MB
                                    </p>

                                    <!-- Logo error -->
                                    <p v-if="errors.logo" class="text-xs text-danger mt-1">
                                        {{ errors.logo }}
                                    </p>
                                </div>

                                <!-- Basic Info Fields -->
                                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Company Name -->
                                    <div class="md:col-span-2">
                                        <label for="company-name" class="form-label mb-2">
                                            Company Name
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="company-name"
                                            type="text"
                                            class="input"
                                            :class="{ 'border-danger': errors.name }"
                                            v-model="form.name"
                                            placeholder="Enter company name"
                                            autocomplete="organization"
                                            @input="clearError('name')"
                                            aria-required="true"
                                            :aria-invalid="!!errors.name"
                                            :aria-describedby="errors.name ? 'name-error' : undefined"
                                        />
                                        <p v-if="errors.name" id="name-error" class="text-xs text-danger mt-1">
                                            {{ errors.name }}
                                        </p>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="company-email" class="form-label mb-2">
                                            Email Address
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="company-email"
                                            type="email"
                                            class="input"
                                            :class="{ 'border-danger': errors.email }"
                                            v-model="form.email"
                                            placeholder="company@example.com"
                                            autocomplete="email"
                                            @input="clearError('email')"
                                            aria-required="true"
                                            :aria-invalid="!!errors.email"
                                            :aria-describedby="errors.email ? 'email-error' : undefined"
                                        />
                                        <p v-if="errors.email" id="email-error" class="text-xs text-danger mt-1">
                                            {{ errors.email }}
                                        </p>
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="company-phone" class="form-label mb-2">
                                            Phone Number
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="company-phone"
                                            type="tel"
                                            class="input"
                                            :class="{ 'border-danger': errors.phone }"
                                            v-model="form.phone"
                                            placeholder="+1 (555) 000-0000"
                                            autocomplete="tel"
                                            @input="clearError('phone')"
                                            aria-required="true"
                                            :aria-invalid="!!errors.phone"
                                            :aria-describedby="errors.phone ? 'phone-error' : undefined"
                                        />
                                        <p v-if="errors.phone" id="phone-error" class="text-xs text-danger mt-1">
                                            {{ errors.phone }}
                                        </p>
                                    </div>

                                    <!-- Website -->
                                    <div class="md:col-span-2">
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
                                                :class="{ 'border-danger': errors.website }"
                                                v-model="form.website"
                                                placeholder="https://www.example.com"
                                                autocomplete="url"
                                                @input="clearError('website')"
                                                :aria-invalid="!!errors.website"
                                                :aria-describedby="errors.website ? 'website-error' : undefined"
                                            />
                                        </div>
                                        <p v-if="errors.website" id="website-error" class="text-xs text-danger mt-1">
                                            {{ errors.website }}
                                        </p>
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
                            <div>
                                <label for="company-address" class="form-label mb-2">
                                    Business Address
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    id="company-address"
                                    class="textarea"
                                    :class="{ 'border-danger': errors.address }"
                                    v-model="form.address"
                                    placeholder="Enter full business address"
                                    rows="3"
                                    autocomplete="street-address"
                                    @input="clearError('address')"
                                    aria-required="true"
                                    :aria-invalid="!!errors.address"
                                    :aria-describedby="errors.address ? 'address-error' : undefined"
                                ></textarea>
                                <p v-if="errors.address" id="address-error" class="text-xs text-danger mt-1">
                                    {{ errors.address }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Card -->
                    <div class="card">
                        <div class="card-body p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                <!-- Left side - Cancel -->
                                <button
                                    type="button"
                                    class="btn btn-light w-full sm:w-auto"
                                    @click="handleCancel"
                                    :disabled="isSubmitting"
                                >
                                    <i class="ki-filled ki-arrow-left me-2"></i>
                                    Cancel
                                </button>

                                <!-- Right side - Save -->
                                <button
                                    type="submit"
                                    class="btn btn-primary w-full sm:w-auto min-w-[160px] justify-center"
                                    :disabled="isSubmitting"
                                >
                                    <span v-if="isSubmitting" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Saving...
                                    </span>
                                    <span v-else class="flex items-center gap-2">
                                        <i class="ki-filled ki-check"></i>
                                        Save Changes
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
