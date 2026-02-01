<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    company: Object,
    currentPlan: Object,
    usage: Object,
    limits: Object,
    pendingInvoices: Array,
    recentInvoices: Array,
});

const page = usePage();

// Format currency
const formatCurrency = (value, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Get subscription status badge class
const getStatusBadgeClass = (status) => {
    const classes = {
        trial: 'badge-info',
        active: 'badge-success',
        expired: 'badge-danger',
        cancelled: 'badge-warning',
        pending: 'badge-secondary',
    };
    return classes[status?.toLowerCase()] || 'badge-secondary';
};

// Get invoice status badge class
const getInvoiceStatusClass = (status) => {
    const classes = {
        paid: 'badge-success',
        pending: 'badge-warning',
        awaiting_verification: 'badge-info',
        overdue: 'badge-danger',
        cancelled: 'badge-secondary',
        failed: 'badge-danger',
        expired: 'badge-secondary',
    };
    return classes[status?.toLowerCase()] || 'badge-secondary';
};

// Get invoice status label for display
const getInvoiceStatusLabel = (status) => {
    const labels = {
        paid: 'Paid',
        pending: 'Pending',
        awaiting_verification: 'Awaiting Verification',
        overdue: 'Overdue',
        cancelled: 'Cancelled',
        failed: 'Failed',
        expired: 'Expired',
    };
    return labels[status?.toLowerCase()] || status;
};

// Check if invoice needs payment action (not awaiting verification or paid)
const needsPaymentAction = (status) => {
    const noActionStatuses = ['awaiting_verification', 'paid', 'cancelled', 'expired'];
    return !noActionStatuses.includes(status?.toLowerCase());
};

// Get payment status label and class based on invoice status
const getPaymentStatus = (invoiceStatus) => {
    const statusMap = {
        pending: { label: 'Not Paid', class: 'text-orange-600 bg-orange-50', icon: 'ki-filled ki-timer text-orange-500' },
        awaiting_verification: { label: 'Verifying', class: 'text-blue-600 bg-blue-50', icon: 'ki-filled ki-loading text-blue-500' },
        paid: { label: 'Paid', class: 'text-green-600 bg-green-50', icon: 'ki-filled ki-check-circle text-green-500' },
        failed: { label: 'Failed', class: 'text-red-600 bg-red-50', icon: 'ki-filled ki-cross-circle text-red-500' },
        cancelled: { label: 'Cancelled', class: 'text-gray-600 bg-gray-50', icon: 'ki-filled ki-minus-circle text-gray-500' },
        expired: { label: 'Expired', class: 'text-gray-600 bg-gray-50', icon: 'ki-filled ki-time text-gray-500' },
        overdue: { label: 'Overdue', class: 'text-red-600 bg-red-50', icon: 'ki-filled ki-notification-bing text-red-500' },
    };
    return statusMap[invoiceStatus?.toLowerCase()] || { label: 'Unknown', class: 'text-gray-600 bg-gray-50', icon: 'ki-filled ki-question text-gray-500' };
};

// Get progress bar color based on percentage
const getProgressColor = (percentage) => {
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 80) return 'bg-yellow-500';
    return 'bg-green-500';
};

// Get progress bar background color
const getProgressBgColor = (percentage) => {
    if (percentage >= 90) return 'bg-red-100';
    if (percentage >= 80) return 'bg-yellow-100';
    return 'bg-green-100';
};

// Get usage text color
const getUsageTextColor = (percentage) => {
    if (percentage >= 90) return 'text-red-600';
    if (percentage >= 80) return 'text-yellow-600';
    return 'text-green-600';
};

// Usage items configuration
const usageItems = computed(() => {
    if (!props.usage?.has_plan) return [];

    return [
        {
            key: 'users',
            label: 'Users',
            icon: 'ki-filled ki-people',
            current: props.usage.usage?.users || 0,
            max: props.usage.limits?.max_users,
            percentage: props.usage.percentages?.users || 0,
        },
        {
            key: 'products',
            label: 'Products',
            icon: 'ki-filled ki-package',
            current: props.usage.usage?.products || 0,
            max: props.usage.limits?.max_products,
            percentage: props.usage.percentages?.products || 0,
        },
        {
            key: 'customers',
            label: 'Customers',
            icon: 'ki-filled ki-user',
            current: props.usage.usage?.customers || 0,
            max: props.usage.limits?.max_customers,
            percentage: props.usage.percentages?.customers || 0,
        },
        {
            key: 'monthly_orders',
            label: 'Monthly Orders',
            icon: 'ki-filled ki-basket',
            current: props.usage.usage?.monthly_orders || 0,
            max: props.usage.limits?.max_monthly_orders,
            percentage: props.usage.percentages?.monthly_orders || 0,
        },
    ];
});

// Check if there are exceeded limits
const hasExceededLimits = computed(() => props.limits?.exceeded);
const exceededLimitsList = computed(() => props.limits?.exceeded_limits || []);
const warningsList = computed(() => props.limits?.warnings || []);

// Billing cycle display
const billingCycleLabel = computed(() => {
    if (props.company?.billing_cycle === 'monthly') return 'Monthly';
    if (props.company?.billing_cycle === 'yearly') return 'Yearly';
    return props.company?.billing_cycle || '-';
});

// Current plan price based on billing cycle
const currentPlanPrice = computed(() => {
    if (!props.currentPlan) return null;
    if (props.company?.billing_cycle === 'yearly') {
        return props.currentPlan.formatted_price_yearly;
    }
    return props.currentPlan.formatted_price_monthly;
});
</script>

<template>
    <AppLayout title="Subscription">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Subscription
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 font-medium">Subscription</span>
            </div>

            <!-- Limit Exceeded Alert -->
            <div v-if="hasExceededLimits" class="mb-5">
                <div class="card border-red-200 bg-red-50">
                    <div class="card-body p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <i class="ki-filled ki-notification-bing text-red-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <h4 class="text-base font-semibold text-red-800 mb-1">Usage Limit Exceeded</h4>
                                <p class="text-sm text-red-600 mb-2">
                                    You have exceeded your plan limits. Please upgrade to continue using all features.
                                </p>
                                <ul class="text-sm text-red-600 list-disc list-inside">
                                    <li v-for="limit in exceededLimitsList" :key="limit">{{ limit }}</li>
                                </ul>
                            </div>
                            <div class="flex-shrink-0">
                                <Link :href="route('subscription.plans')" class="btn btn-sm btn-danger">
                                    Upgrade Now
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warnings Alert -->
            <div v-else-if="warningsList.length > 0" class="mb-5">
                <div class="card border-yellow-200 bg-yellow-50">
                    <div class="card-body p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <i class="ki-filled ki-information-2 text-yellow-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <h4 class="text-base font-semibold text-yellow-800 mb-1">Approaching Limits</h4>
                                <p class="text-sm text-yellow-600 mb-2">
                                    You are approaching your plan limits. Consider upgrading to avoid disruption.
                                </p>
                                <ul class="text-sm text-yellow-600 list-disc list-inside">
                                    <li v-for="warning in warningsList" :key="warning">{{ warning }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:gap-7.5">
                <!-- Row 1: Subscription Status + Usage Statistics -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                    <!-- Current Subscription Status Card -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-shield-tick text-gray-500"></i>
                                Current Subscription
                            </h3>
                            <span
                                class="badge badge-sm"
                                :class="getStatusBadgeClass(company?.subscription_status)"
                            >
                                {{ company?.subscription_status_label || company?.subscription_status }}
                            </span>
                        </div>
                        <div class="card-body p-6">
                            <!-- No Plan State -->
                            <div v-if="!currentPlan" class="text-center py-8">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <i class="ki-filled ki-abstract-26 text-gray-400 text-3xl"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-700 mb-2">No Active Plan</h4>
                                <p class="text-sm text-gray-500 mb-4">Choose a plan to unlock all features</p>
                                <Link :href="route('subscription.plans')" class="btn btn-primary">
                                    <i class="ki-filled ki-arrow-right me-2"></i>
                                    View Plans
                                </Link>
                            </div>

                            <!-- Active Plan State -->
                            <div v-else>
                                <!-- Plan Name & Price -->
                                <div class="flex items-start justify-between mb-6">
                                    <div>
                                        <h4 class="text-xl font-bold text-gray-900 mb-1">{{ currentPlan.name }}</h4>
                                        <p class="text-sm text-gray-500">{{ currentPlan.description }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-primary">{{ currentPlanPrice }}</div>
                                        <div class="text-xs text-gray-500">/ {{ billingCycleLabel }}</div>
                                    </div>
                                </div>

                                <!-- Trial Info -->
                                <div v-if="company?.is_on_trial" class="mb-5 p-4 rounded-lg bg-info/10 border border-info/20">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-info/20 flex items-center justify-center">
                                            <i class="ki-filled ki-time text-info text-xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-info">Trial Period</div>
                                            <div class="text-sm text-gray-600">
                                                <span class="font-bold text-info">{{ company.trial_days_remaining }}</span> days remaining
                                                <span class="text-gray-400 mx-1">|</span>
                                                Ends {{ formatDate(company.trial_ends_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subscription Dates -->
                                <div class="space-y-3 mb-6">
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-500">
                                            <i class="ki-filled ki-calendar-add text-gray-400 me-2"></i>
                                            Starts At
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ formatDate(company?.subscription_starts_at) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-500">
                                            <i class="ki-filled ki-calendar-tick text-gray-400 me-2"></i>
                                            Ends At
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ formatDate(company?.subscription_ends_at) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="text-sm text-gray-500">
                                            <i class="ki-filled ki-arrows-loop text-gray-400 me-2"></i>
                                            Billing Cycle
                                        </span>
                                        <span class="badge badge-sm badge-light">
                                            {{ billingCycleLabel }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-3">
                                    <Link :href="route('subscription.plans')" class="btn btn-primary flex-1">
                                        <i class="ki-filled ki-rocket me-2"></i>
                                        Upgrade Plan
                                    </Link>
                                    <Link :href="route('subscription.plans')" class="btn btn-light flex-1">
                                        <i class="ki-filled ki-element-11 me-2"></i>
                                        View Plans
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Statistics Card -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-chart-simple text-gray-500"></i>
                                Usage Statistics
                            </h3>
                        </div>
                        <div class="card-body p-6">
                            <!-- No Plan State -->
                            <div v-if="!usage?.has_plan" class="text-center py-8">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <i class="ki-filled ki-chart-line text-gray-400 text-3xl"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-700 mb-2">No Usage Data</h4>
                                <p class="text-sm text-gray-500">Subscribe to a plan to track your usage</p>
                            </div>

                            <!-- Usage Bars -->
                            <div v-else class="space-y-5">
                                <div v-for="item in usageItems" :key="item.key" class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <i :class="item.icon" class="text-gray-500"></i>
                                            <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-semibold"
                                                :class="getUsageTextColor(item.percentage)"
                                            >
                                                {{ item.current }}
                                            </span>
                                            <span class="text-sm text-gray-400">/</span>
                                            <span class="text-sm text-gray-500">
                                                {{ item.max === -1 ? 'Unlimited' : item.max }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div v-if="item.max !== -1" class="relative">
                                        <div
                                            class="h-2 rounded-full overflow-hidden"
                                            :class="getProgressBgColor(item.percentage)"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="getProgressColor(item.percentage)"
                                                :style="{ width: Math.min(item.percentage, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <div class="absolute -top-1 right-0 text-xs text-gray-400">
                                            {{ item.percentage }}%
                                        </div>
                                    </div>
                                    <!-- Unlimited indicator -->
                                    <div v-else class="h-2 rounded-full bg-green-100 overflow-hidden">
                                        <div class="h-full w-full bg-green-500/30 rounded-full"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div v-if="usage?.has_plan" class="mt-6 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-center gap-6 text-xs text-gray-500">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                        <span>Normal (&lt;80%)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                                        <span>Warning (80-90%)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                        <span>Critical (&gt;90%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Pending Invoices -->
                <div v-if="pendingInvoices && pendingInvoices.length > 0" class="card border-orange-200">
                    <div class="card-header border-b border-orange-200 bg-orange-50">
                        <h3 class="card-title flex items-center gap-2">
                            <i class="ki-filled ki-notification-bing text-orange-500"></i>
                            <span class="text-orange-800">Pending Invoices</span>
                        </h3>
                        <span class="badge badge-sm badge-warning">{{ pendingInvoices.length }} pending</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="overflow-x-auto">
                            <table class="table table-border table-fixed w-full">
                                <thead>
                                    <tr class="bg-orange-50/50">
                                        <th class="w-[18%]">Invoice #</th>
                                        <th class="w-[15%]">Plan</th>
                                        <th class="w-[15%] text-end">Amount</th>
                                        <th class="w-[14%] text-center">Due Date</th>
                                        <th class="w-[18%] text-center">Payment Status</th>
                                        <th class="w-[20%] text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invoice in pendingInvoices" :key="invoice.id" class="hover:bg-orange-50/30">
                                        <td>
                                            <span class="font-medium text-gray-800">{{ invoice.invoice_number }}</span>
                                        </td>
                                        <td>
                                            <span class="text-gray-600">{{ invoice.plan_name }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="font-semibold text-gray-900">
                                                {{ formatCurrency(invoice.amount, invoice.currency || 'IDR') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="text-sm"
                                                :class="new Date(invoice.due_date) < new Date() ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                            >
                                                {{ formatDate(invoice.due_date) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                                :class="getPaymentStatus(invoice.status).class"
                                            >
                                                <i :class="getPaymentStatus(invoice.status).icon" class="text-[10px]"></i>
                                                {{ getPaymentStatus(invoice.status).label }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <!-- Pay Now button for pending/overdue invoices -->
                                            <Link
                                                v-if="needsPaymentAction(invoice.status)"
                                                :href="invoice.payment_url || `/subscription/payment-proof/${invoice.id}`"
                                                class="btn btn-sm btn-primary"
                                            >
                                                <i class="ki-filled ki-dollar me-1"></i>
                                                Pay Now
                                            </Link>
                                            <!-- View Status button for awaiting_verification -->
                                            <Link
                                                v-else-if="invoice.status?.toLowerCase() === 'awaiting_verification'"
                                                :href="`/subscription/payment-proof/${invoice.id}`"
                                                class="btn btn-sm btn-info"
                                            >
                                                <i class="ki-filled ki-time me-1"></i>
                                                View Status
                                            </Link>
                                            <!-- Download Invoice for paid/other statuses -->
                                            <a
                                                v-else
                                                :href="`/subscription/invoice/${invoice.id}/download`"
                                                class="btn btn-sm btn-light"
                                            >
                                                <i class="ki-filled ki-exit-down me-1"></i>
                                                Download
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Recent Invoices -->
                <div class="card">
                    <div class="card-header border-b border-gray-200">
                        <h3 class="card-title flex items-center gap-2">
                            <i class="ki-filled ki-document text-gray-500"></i>
                            Recent Invoices
                        </h3>
                        <Link href="/subscription/billing-history" class="btn btn-sm btn-light">
                            View All
                        </Link>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="recentInvoices && recentInvoices.length > 0" class="overflow-x-auto">
                            <table class="table table-border table-fixed w-full">
                                <thead>
                                    <tr>
                                        <th class="w-[20%]">Invoice #</th>
                                        <th class="w-[18%]">Plan</th>
                                        <th class="w-[15%] text-end">Amount</th>
                                        <th class="w-[14%] text-center">Date</th>
                                        <th class="w-[18%] text-center">Payment Status</th>
                                        <th class="w-[15%] text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invoice in recentInvoices" :key="invoice.id" class="hover:bg-slate-50">
                                        <td>
                                            <span class="font-medium text-gray-800">
                                                {{ invoice.invoice_number }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-gray-600">{{ invoice.plan_name }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="font-semibold text-gray-900">
                                                {{ formatCurrency(invoice.amount, invoice.currency || 'IDR') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-sm text-gray-600">{{ formatDate(invoice.created_at) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                                :class="getPaymentStatus(invoice.status).class"
                                            >
                                                <i :class="getPaymentStatus(invoice.status).icon" class="text-[10px]"></i>
                                                {{ getPaymentStatus(invoice.status).label }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex justify-center gap-1">
                                                <!-- Upload proof for pending -->
                                                <Link
                                                    v-if="invoice.status?.toLowerCase() === 'pending'"
                                                    :href="`/subscription/payment-proof/${invoice.id}`"
                                                    class="btn btn-xs btn-primary"
                                                    title="Upload Payment Proof"
                                                >
                                                    <i class="ki-filled ki-exit-up"></i>
                                                </Link>
                                                <!-- View status for awaiting verification -->
                                                <Link
                                                    v-else-if="invoice.status?.toLowerCase() === 'awaiting_verification'"
                                                    :href="`/subscription/payment-proof/${invoice.id}`"
                                                    class="btn btn-xs btn-info"
                                                    title="View Status"
                                                >
                                                    <i class="ki-filled ki-time"></i>
                                                </Link>
                                                <!-- Download invoice -->
                                                <a
                                                    :href="`/subscription/invoice/${invoice.id}/download`"
                                                    class="btn btn-xs btn-light"
                                                    title="Download Invoice"
                                                >
                                                    <i class="ki-filled ki-exit-down"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center justify-center py-12">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <i class="ki-filled ki-document text-gray-400 text-3xl"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-600 mb-2">No Invoices Yet</h4>
                            <p class="text-sm text-gray-500">Your billing history will appear here</p>
                        </div>
                    </div>
                </div>

                <!-- Plan Features (if plan exists) -->
                <div v-if="currentPlan?.features && currentPlan.features.length > 0" class="card">
                    <div class="card-header border-b border-gray-200">
                        <h3 class="card-title flex items-center gap-2">
                            <i class="ki-filled ki-check-circle text-gray-500"></i>
                            Plan Features
                        </h3>
                    </div>
                    <div class="card-body p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="(feature, index) in currentPlan.features"
                                :key="index"
                                class="flex items-center gap-3 p-3 rounded-lg bg-gray-50"
                            >
                                <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-check text-success"></i>
                                </div>
                                <span class="text-sm text-gray-700">{{ feature }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
