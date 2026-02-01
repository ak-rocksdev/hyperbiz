<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    currentPlan: {
        type: Object,
        default: null,
    },
    company: {
        type: Object,
        default: null,
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

// Billing cycle toggle (monthly or yearly)
const billingCycle = ref('monthly');

// Toggle billing cycle
const setBillingCycle = (cycle) => {
    billingCycle.value = cycle;
};

// Get price based on billing cycle
const getPrice = (plan) => {
    if (billingCycle.value === 'yearly') {
        return plan.formatted_price_yearly;
    }
    return plan.formatted_price_monthly;
};

// Get raw price for comparison
const getRawPrice = (plan) => {
    if (billingCycle.value === 'yearly') {
        return plan.price_yearly;
    }
    return plan.price_monthly;
};

// Get billing period label
const getBillingPeriod = () => {
    return billingCycle.value === 'yearly' ? 'year' : 'month';
};

// Format limit display (0 = Unlimited)
const formatLimit = (value) => {
    if (value === 0 || value === null || value === -1) {
        return 'Unlimited';
    }
    return value.toLocaleString();
};

// Get CTA button text
const getCtaText = (plan) => {
    if (plan.is_current) {
        return 'Current Plan';
    }

    if (!props.currentPlan) {
        return 'Get Started';
    }

    const currentPrice = props.currentPlan ? getRawPlanPrice(props.currentPlan) : 0;
    const targetPrice = getRawPrice(plan);

    if (targetPrice > currentPrice) {
        return 'Upgrade';
    } else if (targetPrice < currentPrice) {
        return 'Downgrade';
    }

    return 'Select Plan';
};

// Get raw price for current plan
const getRawPlanPrice = (plan) => {
    if (billingCycle.value === 'yearly') {
        return plan.price_yearly || 0;
    }
    return plan.price_monthly || 0;
};

// Get CTA button style
const getCtaButtonClass = (plan) => {
    if (plan.is_current) {
        return 'btn btn-light cursor-not-allowed opacity-60';
    }

    if (plan.is_popular) {
        return 'btn btn-primary';
    }

    return 'btn btn-light hover:bg-primary hover:border-primary hover:text-white';
};

// Handle plan selection
const selectPlan = (plan) => {
    if (plan.is_current) return;

    // Redirect to checkout page with plan ID and billing cycle
    router.visit(`/subscription/checkout/${plan.id}?cycle=${billingCycle.value}`);
};

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

// Calculate yearly savings percentage (max across all plans)
const maxYearlySavings = computed(() => {
    if (!props.plans || props.plans.length === 0) return 0;
    return Math.max(...props.plans.map(p => p.yearly_discount || 0));
});

// All feature keys for comparison table
const allFeatureKeys = computed(() => {
    const features = new Set();
    props.plans.forEach(plan => {
        const planFeatures = getPlanFeatures(plan);
        planFeatures.forEach(feature => {
            features.add(feature);
        });
    });
    return Array.from(features);
});

// Check if plan has a specific feature
const planHasFeature = (plan, feature) => {
    const planFeatures = getPlanFeatures(plan);
    return planFeatures.includes(feature);
};

// Limit comparison rows for the table
const limitRows = [
    { key: 'max_users', label: 'Users', icon: 'ki-filled ki-people' },
    { key: 'max_products', label: 'Products', icon: 'ki-filled ki-package' },
    { key: 'max_customers', label: 'Customers', icon: 'ki-filled ki-profile-user' },
    { key: 'max_monthly_orders', label: 'Monthly Orders', icon: 'ki-filled ki-basket' },
];
</script>

<template>
    <AppLayout title="Subscription Plans">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Subscription Plans
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <Link href="/subscription" class="text-gray-500 hover:text-primary transition-colors">
                    Subscription
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Plans</span>
            </div>

            <!-- Page Header -->
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 mb-3">
                    Choose Your Plan
                </h1>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Select the perfect plan for your business. All plans include our core features with different limits to match your needs.
                </p>
            </div>

            <!-- Billing Cycle Toggle -->
            <div class="flex items-center justify-center mb-10">
                <div class="inline-flex items-center gap-4 bg-gray-100 rounded-xl p-1.5">
                    <!-- Monthly Button -->
                    <button
                        type="button"
                        @click="setBillingCycle('monthly')"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200',
                            billingCycle === 'monthly'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Monthly
                    </button>

                    <!-- Yearly Button with Savings Badge -->
                    <button
                        type="button"
                        @click="setBillingCycle('yearly')"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                            billingCycle === 'yearly'
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Yearly
                        <span
                            v-if="maxYearlySavings > 0"
                            class="badge badge-sm badge-success"
                        >
                            Save up to {{ maxYearlySavings }}%
                        </span>
                    </button>
                </div>
            </div>

            <!-- Trial Banner (if on trial) -->
            <div v-if="company?.is_on_trial" class="mb-8">
                <div class="card border-info/30 bg-info/5">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-info/20 flex items-center justify-center">
                                    <i class="ki-filled ki-time text-info text-2xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <h4 class="text-base font-semibold text-info mb-1">You are on a free trial</h4>
                                <p class="text-sm text-gray-600">
                                    Upgrade now to continue using HyperBiz without interruption after your trial ends.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative flex flex-col rounded-2xl border-2 bg-white transition-all duration-300 hover:shadow-xl"
                    :class="[
                        plan.is_popular
                            ? 'border-primary shadow-lg scale-[1.02] lg:scale-105'
                            : plan.is_current
                                ? 'border-success/50 shadow-md'
                                : 'border-gray-200 hover:border-primary/50'
                    ]"
                >
                    <!-- Most Popular Badge -->
                    <div
                        v-if="plan.is_popular"
                        class="absolute -top-4 left-1/2 -translate-x-1/2 z-10"
                    >
                        <span class="badge badge-primary shadow-lg px-5 py-2 text-sm font-semibold">
                            <i class="ki-filled ki-crown text-xs me-1.5"></i>
                            Most Popular
                        </span>
                    </div>

                    <!-- Current Plan Badge -->
                    <div
                        v-if="plan.is_current"
                        class="absolute -top-4 left-1/2 -translate-x-1/2 z-10"
                    >
                        <span class="badge badge-success shadow-lg px-5 py-2 text-sm font-semibold">
                            <i class="ki-filled ki-check-circle text-xs me-1.5"></i>
                            Current Plan
                        </span>
                    </div>

                    <!-- Card Header -->
                    <div class="p-6 pb-4 text-center" :class="plan.is_popular ? 'pt-8' : ''">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
                        <p v-if="plan.description" class="text-sm text-gray-500 min-h-[40px]">
                            {{ plan.description }}
                        </p>
                    </div>

                    <!-- Pricing Section -->
                    <div class="px-6 py-6 text-center border-y border-gray-100" :class="plan.is_popular ? 'bg-primary/5' : 'bg-gray-50/50'">
                        <div class="mb-2">
                            <span class="text-4xl font-bold text-gray-900">{{ getPrice(plan) }}</span>
                            <span class="text-sm text-gray-500 font-medium">/{{ getBillingPeriod() }}</span>
                        </div>
                        <!-- Yearly Discount Badge -->
                        <div v-if="billingCycle === 'yearly' && plan.yearly_discount > 0" class="mt-2">
                            <span class="badge badge-sm badge-success">
                                <i class="ki-filled ki-discount text-xs me-1"></i>
                                {{ plan.yearly_discount }}% discount applied
                            </span>
                        </div>
                    </div>

                    <!-- Limits Section -->
                    <div class="p-6 space-y-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Plan Limits</h4>

                        <!-- Users -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10">
                                <i class="ki-filled ki-people text-primary"></i>
                            </div>
                            <div class="flex-grow">
                                <span class="text-sm text-gray-600">Users</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ formatLimit(plan.max_users) }}</span>
                        </div>

                        <!-- Products -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-info/10">
                                <i class="ki-filled ki-package text-info"></i>
                            </div>
                            <div class="flex-grow">
                                <span class="text-sm text-gray-600">Products</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ formatLimit(plan.max_products) }}</span>
                        </div>

                        <!-- Customers -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-success/10">
                                <i class="ki-filled ki-profile-user text-success"></i>
                            </div>
                            <div class="flex-grow">
                                <span class="text-sm text-gray-600">Customers</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ formatLimit(plan.max_customers) }}</span>
                        </div>

                        <!-- Monthly Orders -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-warning/10">
                                <i class="ki-filled ki-basket text-warning"></i>
                            </div>
                            <div class="flex-grow">
                                <span class="text-sm text-gray-600">Monthly Orders</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ formatLimit(plan.max_monthly_orders) }}</span>
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="px-6 pb-6 flex-grow">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Features Included</h4>
                        <ul v-if="getPlanFeatures(plan).length > 0" class="space-y-2.5">
                            <li
                                v-for="(feature, index) in getPlanFeatures(plan).slice(0, 5)"
                                :key="index"
                                class="flex items-start gap-2.5"
                            >
                                <i class="ki-filled ki-check-circle text-success text-sm mt-0.5 shrink-0"></i>
                                <span class="text-sm text-gray-700">{{ feature }}</span>
                            </li>
                            <li v-if="getPlanFeatures(plan).length > 5" class="text-sm text-primary pl-6 font-medium">
                                +{{ getPlanFeatures(plan).length - 5 }} more features
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400 italic">All core features included</p>
                    </div>

                    <!-- CTA Button -->
                    <div class="p-6 pt-0">
                        <button
                            type="button"
                            @click="selectPlan(plan)"
                            :disabled="plan.is_current"
                            :class="[
                                'w-full py-3 text-base font-semibold rounded-lg transition-all duration-200',
                                getCtaButtonClass(plan)
                            ]"
                        >
                            <i
                                v-if="plan.is_current"
                                class="ki-filled ki-check me-2"
                            ></i>
                            <i
                                v-else-if="getCtaText(plan) === 'Upgrade'"
                                class="ki-filled ki-rocket me-2"
                            ></i>
                            <i
                                v-else-if="getCtaText(plan) === 'Downgrade'"
                                class="ki-filled ki-arrow-down me-2"
                            ></i>
                            <i
                                v-else
                                class="ki-filled ki-arrow-right me-2"
                            ></i>
                            {{ getCtaText(plan) }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feature Comparison Table -->
            <div v-if="plans.length > 0" class="card mb-10">
                <div class="card-header border-b border-gray-200">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="ki-filled ki-element-11 text-gray-500"></i>
                        Feature Comparison
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700 min-w-[200px]">
                                        Feature / Limit
                                    </th>
                                    <th
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        class="text-center px-6 py-4 min-w-[140px]"
                                        :class="plan.is_popular ? 'bg-primary/5' : ''"
                                    >
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-sm font-bold text-gray-900">{{ plan.name }}</span>
                                            <span v-if="plan.is_current" class="badge badge-sm badge-success">Current</span>
                                            <span v-else-if="plan.is_popular" class="badge badge-sm badge-primary">Popular</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <!-- Limit Rows -->
                                <tr v-for="limit in limitRows" :key="limit.key" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i :class="[limit.icon, 'text-gray-400']"></i>
                                            <span class="text-sm font-medium text-gray-700">{{ limit.label }}</span>
                                        </div>
                                    </td>
                                    <td
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        class="text-center px-6 py-4"
                                        :class="plan.is_popular ? 'bg-primary/5' : ''"
                                    >
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ formatLimit(plan[limit.key]) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Feature Rows -->
                                <tr v-for="feature in allFeatureKeys" :key="feature" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i class="ki-filled ki-check-circle text-gray-400"></i>
                                            <span class="text-sm font-medium text-gray-700">{{ feature }}</span>
                                        </div>
                                    </td>
                                    <td
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        class="text-center px-6 py-4"
                                        :class="plan.is_popular ? 'bg-primary/5' : ''"
                                    >
                                        <i
                                            v-if="planHasFeature(plan, feature)"
                                            class="ki-filled ki-check-circle text-success text-lg"
                                        ></i>
                                        <i
                                            v-else
                                            class="ki-filled ki-cross-circle text-gray-300 text-lg"
                                        ></i>
                                    </td>
                                </tr>

                                <!-- Pricing Row -->
                                <tr class="bg-gray-50/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i class="ki-filled ki-dollar text-gray-400"></i>
                                            <span class="text-sm font-semibold text-gray-700">
                                                Price ({{ billingCycle === 'yearly' ? 'Yearly' : 'Monthly' }})
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        class="text-center px-6 py-4"
                                        :class="plan.is_popular ? 'bg-primary/10' : ''"
                                    >
                                        <span class="text-base font-bold text-gray-900">
                                            {{ getPrice(plan) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Action Row -->
                                <tr>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-gray-700">Select Plan</span>
                                    </td>
                                    <td
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        class="text-center px-6 py-5"
                                        :class="plan.is_popular ? 'bg-primary/5' : ''"
                                    >
                                        <button
                                            type="button"
                                            @click="selectPlan(plan)"
                                            :disabled="plan.is_current"
                                            :class="[
                                                'btn btn-sm',
                                                plan.is_current
                                                    ? 'btn-light cursor-not-allowed opacity-60'
                                                    : plan.is_popular
                                                        ? 'btn-primary'
                                                        : 'btn-light hover:bg-primary hover:border-primary hover:text-white'
                                            ]"
                                        >
                                            {{ getCtaText(plan) }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card mb-10">
                <div class="card-header border-b border-gray-200">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="ki-filled ki-message-question text-gray-500"></i>
                        Frequently Asked Questions
                    </h3>
                </div>
                <div class="card-body p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">Can I change my plan later?</h4>
                                <p class="text-sm text-gray-600">
                                    Yes, you can upgrade or downgrade your plan at any time. Changes will be prorated based on your current billing cycle.
                                </p>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">What happens if I exceed my limits?</h4>
                                <p class="text-sm text-gray-600">
                                    You will receive notifications when approaching limits. Once exceeded, some features may be restricted until you upgrade.
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">Is there a free trial?</h4>
                                <p class="text-sm text-gray-600">
                                    Yes, all new accounts start with a free trial period. No credit card required to start.
                                </p>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">What payment methods do you accept?</h4>
                                <p class="text-sm text-gray-600">
                                    We accept credit/debit cards{{ paymentMethods.stripe ? ' (via Stripe)' : '' }}{{ paymentMethods.bank_transfer ? ' and bank transfers' : '' }}.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Need Help Section -->
            <div class="text-center pb-10">
                <p class="text-gray-600 mb-4">
                    Need a custom plan or have questions?
                </p>
                <a href="mailto:support@hyperbiz.com" class="btn btn-light">
                    <i class="ki-filled ki-message-text me-2"></i>
                    Contact Sales
                </a>
            </div>
        </div>
    </AppLayout>
</template>
