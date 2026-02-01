<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    billingCycle: {
        type: String,
        default: 'monthly',
    },
    amount: {
        type: Number,
        default: 0,
    },
    formattedAmount: {
        type: String,
        default: '',
    },
    company: {
        type: Object,
        default: () => ({}),
    },
    bankAccounts: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Object,
        default: () => ({
            stripe: false,
            bank_transfer: true,
        }),
    },
});

const page = usePage();

// Form state
const selectedPaymentMethod = ref(null);
const isSubmitting = ref(false);
const errors = ref({});

// Set default payment method on mount
if (props.paymentMethods.stripe) {
    selectedPaymentMethod.value = 'stripe';
} else if (props.paymentMethods.bank_transfer) {
    selectedPaymentMethod.value = 'bank_transfer';
}

// Computed: Billing period label
const billingPeriodLabel = computed(() => {
    return props.billingCycle === 'yearly' ? 'year' : 'month';
});

// Computed: Price display
const priceDisplay = computed(() => {
    if (props.billingCycle === 'yearly') {
        return props.plan.formatted_price_yearly;
    }
    return props.plan.formatted_price_monthly;
});

// Computed: Discount info for yearly
const yearlyDiscount = computed(() => {
    if (props.billingCycle === 'yearly' && props.plan.yearly_discount > 0) {
        return props.plan.yearly_discount;
    }
    return null;
});

// Get features array from plan
const getPlanFeatures = (plan) => {
    if (!plan.features) return [];
    if (Array.isArray(plan.features)) return plan.features;
    try {
        return JSON.parse(plan.features);
    } catch {
        return [];
    }
};

// Payment method card data
const paymentMethodCards = computed(() => {
    const methods = [];

    if (props.paymentMethods.stripe) {
        methods.push({
            id: 'stripe',
            name: 'Credit / Debit Card',
            description: 'Pay securely with Visa, Mastercard, or other major cards via Stripe.',
            icon: 'ki-filled ki-credit-cart',
            badges: ['Visa', 'Mastercard', 'Amex'],
            recommended: true,
        });
    }

    if (props.paymentMethods.bank_transfer) {
        methods.push({
            id: 'bank_transfer',
            name: 'Bank Transfer',
            description: 'Transfer payment to our bank account. Manual verification required (1-2 business days).',
            icon: 'ki-filled ki-bank',
            badges: [],
            recommended: false,
        });
    }

    return methods;
});

// Check if form is valid
const isFormValid = computed(() => {
    return selectedPaymentMethod.value !== null;
});

// Handle payment method selection
const selectPaymentMethod = (methodId) => {
    selectedPaymentMethod.value = methodId;
    errors.value = {};
};

// Validate form
const validateForm = () => {
    errors.value = {};

    if (!selectedPaymentMethod.value) {
        errors.value.payment_method = ['Please select a payment method'];
        return false;
    }

    return true;
};

// Handle form submission
const submitCheckout = async () => {
    if (!validateForm()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a payment method to proceed.',
        });
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await axios.post(`/subscription/subscribe/${props.plan.id}`, {
            billing_cycle: props.billingCycle,
            payment_method: selectedPaymentMethod.value,
        });

        // Handle response based on payment method
        if (selectedPaymentMethod.value === 'stripe') {
            // Redirect to Stripe checkout URL
            if (response.data.checkout_url) {
                window.location.href = response.data.checkout_url;
            } else {
                throw new Error('Stripe checkout URL not provided');
            }
        } else if (selectedPaymentMethod.value === 'bank_transfer') {
            // Redirect to payment proof upload page
            if (response.data.redirect_url) {
                router.visit(response.data.redirect_url);
            } else if (response.data.invoice_id) {
                router.visit(`/subscription/payment-proof/${response.data.invoice_id}`);
            } else {
                // Default redirect to subscription index with success message
                Swal.fire({
                    icon: 'success',
                    title: 'Invoice Created',
                    text: response.data.message || 'Please complete the bank transfer and upload your payment proof.',
                }).then(() => {
                    router.visit('/subscription');
                });
            }
        }
    } catch (error) {
        console.error('Checkout error:', error);

        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }

        Swal.fire({
            icon: 'error',
            title: 'Checkout Failed',
            text: error.response?.data?.message || 'An error occurred while processing your subscription. Please try again.',
        });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Checkout">
        <div class="container-fixed py-5">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 text-sm mb-6">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <Link href="/subscription" class="text-gray-500 hover:text-primary transition-colors">
                    Subscription
                </Link>
                <span class="text-gray-400">/</span>
                <Link href="/subscription/plans" class="text-gray-500 hover:text-primary transition-colors">
                    Plans
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Checkout</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Complete Your Subscription</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Choose your preferred payment method to subscribe to {{ plan.name }}
                    </p>
                </div>
                <Link href="/subscription/plans" class="btn btn-light">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Back to Plans
                </Link>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Payment Method Selection -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Payment Method Section -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-wallet text-gray-500"></i>
                                Select Payment Method
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <!-- No Payment Methods Available -->
                            <div v-if="paymentMethodCards.length === 0" class="text-center py-8">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <i class="ki-filled ki-information-2 text-gray-400 text-3xl"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-700 mb-2">No Payment Methods Available</h4>
                                <p class="text-sm text-gray-500">
                                    Please contact support to complete your subscription.
                                </p>
                            </div>

                            <!-- Payment Method Cards -->
                            <div v-else class="space-y-4">
                                <div
                                    v-for="method in paymentMethodCards"
                                    :key="method.id"
                                    @click="selectPaymentMethod(method.id)"
                                    class="relative flex items-start gap-4 p-5 border-2 rounded-xl cursor-pointer transition-all duration-200"
                                    :class="[
                                        selectedPaymentMethod === method.id
                                            ? 'border-primary bg-primary/5 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <!-- Radio Indicator -->
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div
                                            class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                                            :class="[
                                                selectedPaymentMethod === method.id
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            ]"
                                        >
                                            <div
                                                v-if="selectedPaymentMethod === method.id"
                                                class="w-2 h-2 rounded-full bg-white"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Icon -->
                                    <div
                                        class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                                        :class="[
                                            selectedPaymentMethod === method.id
                                                ? 'bg-primary/10'
                                                : 'bg-gray-100'
                                        ]"
                                    >
                                        <i
                                            :class="[
                                                method.icon,
                                                'text-2xl',
                                                selectedPaymentMethod === method.id ? 'text-primary' : 'text-gray-500'
                                            ]"
                                        ></i>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="text-base font-semibold text-gray-900">
                                                {{ method.name }}
                                            </h4>
                                            <span
                                                v-if="method.recommended"
                                                class="badge badge-sm badge-primary"
                                            >
                                                Recommended
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mb-3">
                                            {{ method.description }}
                                        </p>

                                        <!-- Card Brand Badges (for Stripe) -->
                                        <div v-if="method.badges.length > 0" class="flex items-center gap-2">
                                            <span
                                                v-for="badge in method.badges"
                                                :key="badge"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-600"
                                            >
                                                {{ badge }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Selected Checkmark -->
                                    <div
                                        v-if="selectedPaymentMethod === method.id"
                                        class="absolute top-4 right-4"
                                    >
                                        <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                                            <i class="ki-filled ki-check text-white text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Validation Error -->
                            <div v-if="errors.payment_method" class="mt-4">
                                <p class="text-sm text-danger flex items-center gap-1">
                                    <i class="ki-filled ki-information-2 text-xs"></i>
                                    {{ errors.payment_method[0] }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Account Information (shown when bank_transfer is selected) -->
                    <div v-if="selectedPaymentMethod === 'bank_transfer' && bankAccounts.length > 0" class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-bank text-gray-500"></i>
                                Bank Account Information
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <p class="text-sm text-gray-600 mb-4">
                                Transfer the exact amount to one of the following bank accounts:
                            </p>
                            <div class="space-y-4">
                                <div
                                    v-for="(account, index) in bankAccounts"
                                    :key="index"
                                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                                >
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Bank Name</span>
                                            <p class="font-semibold text-gray-900">{{ account.bank_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Account Name</span>
                                            <p class="font-semibold text-gray-900">{{ account.account_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Account Number</span>
                                            <p class="font-semibold text-gray-900 font-mono">{{ account.account_number }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 p-4 bg-info/10 border border-info/20 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <i class="ki-filled ki-information-2 text-info mt-0.5"></i>
                                    <div class="text-sm text-info">
                                        <p class="font-semibold mb-1">Important Notes:</p>
                                        <ul class="list-disc list-inside space-y-1 text-info/80">
                                            <li>Transfer the exact amount shown in the order summary</li>
                                            <li>Upload your payment proof after completing the transfer</li>
                                            <li>Verification may take 1-2 business days</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-profile-circle text-gray-500"></i>
                                Billing Information
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Company Name</span>
                                    <p class="font-semibold text-gray-900">{{ company.name || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Email</span>
                                    <p class="font-semibold text-gray-900">{{ company.email || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary (Sticky) -->
                <div class="lg:col-span-1">
                    <div class="card sticky top-5">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-basket text-gray-500"></i>
                                Order Summary
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <!-- Plan Info -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-lg font-bold text-gray-900">{{ plan.name }}</h4>
                                    <span
                                        class="badge badge-sm"
                                        :class="billingCycle === 'yearly' ? 'badge-success' : 'badge-light'"
                                    >
                                        {{ billingCycle === 'yearly' ? 'Yearly' : 'Monthly' }}
                                    </span>
                                </div>
                                <p v-if="plan.description" class="text-sm text-gray-500">
                                    {{ plan.description }}
                                </p>
                            </div>

                            <!-- Features Preview -->
                            <div v-if="getPlanFeatures(plan).length > 0" class="mb-6 pb-6 border-b border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Includes:</p>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(feature, index) in getPlanFeatures(plan).slice(0, 4)"
                                        :key="index"
                                        class="flex items-center gap-2 text-sm text-gray-600"
                                    >
                                        <i class="ki-filled ki-check-circle text-success text-xs"></i>
                                        {{ feature }}
                                    </li>
                                    <li v-if="getPlanFeatures(plan).length > 4" class="text-sm text-primary font-medium pl-5">
                                        +{{ getPlanFeatures(plan).length - 4 }} more features
                                    </li>
                                </ul>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ plan.name }} ({{ billingPeriodLabel }})</span>
                                    <span class="font-medium text-gray-900">{{ priceDisplay }}</span>
                                </div>

                                <!-- Yearly Discount -->
                                <div v-if="yearlyDiscount" class="flex items-center justify-between text-sm">
                                    <span class="text-success flex items-center gap-1">
                                        <i class="ki-filled ki-discount text-xs"></i>
                                        Yearly Discount
                                    </span>
                                    <span class="font-medium text-success">-{{ yearlyDiscount }}%</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="border-t border-gray-200 pt-4 mb-6">
                                <div class="flex items-center justify-between">
                                    <span class="text-base font-semibold text-gray-900">Total</span>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-primary">{{ formattedAmount }}</span>
                                        <span class="text-sm text-gray-500">/{{ billingPeriodLabel }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="button"
                                @click="submitCheckout"
                                :disabled="!isFormValid || isSubmitting"
                                class="btn btn-primary w-full py-3 text-base font-semibold"
                                :class="{ 'opacity-60 cursor-not-allowed': !isFormValid || isSubmitting }"
                            >
                                <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                    <i class="ki-filled ki-loading animate-spin"></i>
                                    Processing...
                                </span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <i
                                        :class="selectedPaymentMethod === 'stripe' ? 'ki-filled ki-credit-cart' : 'ki-filled ki-check'"
                                    ></i>
                                    {{
                                        selectedPaymentMethod === 'stripe'
                                            ? 'Pay with Card'
                                            : selectedPaymentMethod === 'bank_transfer'
                                                ? 'Continue with Bank Transfer'
                                                : 'Proceed to Payment'
                                    }}
                                </span>
                            </button>

                            <!-- Security Note -->
                            <div class="mt-4 flex items-center justify-center gap-2 text-xs text-gray-400">
                                <i class="ki-filled ki-shield-tick"></i>
                                <span>Secure payment processing</span>
                            </div>

                            <!-- Terms -->
                            <p class="mt-4 text-xs text-gray-400 text-center">
                                By proceeding, you agree to our
                                <a href="/terms" class="text-primary hover:underline">Terms of Service</a>
                                and
                                <a href="/privacy" class="text-primary hover:underline">Privacy Policy</a>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    Need help?
                    <a href="mailto:support@hyperbiz.com" class="text-primary hover:underline font-medium">
                        Contact our support team
                    </a>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
