<script setup>
import Modal from '@/Components/Modal.vue';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    plan: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

// Form data
const form = ref({
    name: '',
    slug: '',
    description: '',
    price_monthly: 0,
    price_yearly: 0,
    max_users: 0,
    max_products: 0,
    max_customers: 0,
    max_monthly_orders: 0,
    features: [],
    is_active: true,
});

// Display values for formatted price inputs
const displayPriceMonthly = ref('');
const displayPriceYearly = ref('');

const newFeature = ref('');
const errors = ref({});
const saving = ref(false);

// Format number with thousand separators
const formatNumber = (value) => {
    if (!value && value !== 0) return '';
    return new Intl.NumberFormat('id-ID').format(value);
};

// Parse formatted number back to integer
const parseFormattedNumber = (value) => {
    if (!value) return 0;
    // Remove all non-digit characters
    const cleaned = String(value).replace(/[^\d]/g, '');
    return parseInt(cleaned, 10) || 0;
};

// Handle price input with formatting
const handlePriceInput = (type, event) => {
    const rawValue = event.target.value;
    const numericValue = parseFormattedNumber(rawValue);

    if (type === 'monthly') {
        form.value.price_monthly = numericValue;
        displayPriceMonthly.value = formatNumber(numericValue);
    } else {
        form.value.price_yearly = numericValue;
        displayPriceYearly.value = formatNumber(numericValue);
    }
};

// Sync display values when form values change
const syncDisplayValues = () => {
    displayPriceMonthly.value = formatNumber(form.value.price_monthly);
    displayPriceYearly.value = formatNumber(form.value.price_yearly);
};

// Reset form function (defined before watch)
const resetForm = () => {
    form.value = {
        name: '',
        slug: '',
        description: '',
        price_monthly: 0,
        price_yearly: 0,
        max_users: 0,
        max_products: 0,
        max_customers: 0,
        max_monthly_orders: 0,
        features: [],
        is_active: true,
    };
    errors.value = {};
    newFeature.value = '';
    syncDisplayValues();
};

// Watch for plan changes
watch(() => props.plan, (newPlan) => {
    if (newPlan) {
        form.value = {
            name: newPlan.name || '',
            slug: newPlan.slug || '',
            description: newPlan.description || '',
            price_monthly: newPlan.price_monthly || 0,
            price_yearly: newPlan.price_yearly || 0,
            max_users: newPlan.max_users || 0,
            max_products: newPlan.max_products || 0,
            max_customers: newPlan.max_customers || 0,
            max_monthly_orders: newPlan.max_monthly_orders || 0,
            features: newPlan.features || [],
            is_active: newPlan.is_active ?? true,
        };
        syncDisplayValues();
    } else {
        resetForm();
    }
}, { immediate: true });

// Add feature
const addFeature = () => {
    const trimmedFeature = newFeature.value.trim();
    if (trimmedFeature) {
        form.value.features.push(trimmedFeature);
        newFeature.value = '';
    }
};

// Handle Enter key on feature input
const handleFeatureKeydown = (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        addFeature();
    }
};

// Remove feature
const removeFeature = (index) => {
    form.value.features.splice(index, 1);
};

// Submit form
const submit = async () => {
    errors.value = {};
    saving.value = true;

    try {
        let response;
        if (props.plan) {
            // Update existing plan
            response = await axios.put(route('admin.plans.update', props.plan.id), form.value);
        } else {
            // Create new plan
            response = await axios.post(route('admin.plans.store'), form.value);
        }

        if (response.data.success) {
            emit('saved', response.data.plan);
            close();
        }
    } catch (error) {
        console.error('Error saving plan:', error);
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            alert(error.response?.data?.message || 'Failed to save plan');
        }
    } finally {
        saving.value = false;
    }
};

// Close modal
const close = () => {
    if (!saving.value) {
        resetForm();
        emit('close');
    }
};

// Calculate yearly discount percentage
const yearlyDiscount = computed(() => {
    if (form.value.price_monthly > 0) {
        const monthlyTotal = form.value.price_monthly * 12;
        const savings = monthlyTotal - form.value.price_yearly;
        return Math.round((savings / monthlyTotal) * 100);
    }
    return 0;
});
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="bg-white rounded-lg shadow-xl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ plan ? 'Edit Plan' : 'Create New Plan' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ plan ? 'Update subscription plan details' : 'Add a new subscription plan' }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="btn btn-sm btn-icon btn-light"
                    :disabled="saving"
                >
                    <i class="ki-filled ki-cross text-gray-500"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="submit">
                <div class="p-6 space-y-6 max-h-[65vh] overflow-y-auto">
                    <!-- Basic Information Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 pb-2 border-b border-gray-100">Basic Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">
                                    Plan Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="input"
                                    :class="{ 'border-danger': errors.name }"
                                    placeholder="e.g., Starter, Pro"
                                    required
                                />
                                <p v-if="errors.name" class="form-hint text-danger mt-1.5">{{ errors.name[0] }}</p>
                            </div>

                            <!-- Slug -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">Slug</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    class="input"
                                    :class="{ 'border-danger': errors.slug }"
                                    placeholder="Auto-generated"
                                />
                                <p v-if="errors.slug" class="form-hint text-danger mt-1.5">{{ errors.slug[0] }}</p>
                            </div>

                            <!-- Description (full width) -->
                            <div class="sm:col-span-2">
                                <label class="form-label text-gray-700 mb-2.5">Description</label>
                                <textarea
                                    v-model="form.description"
                                    class="textarea w-full"
                                    :class="{ 'border-danger': errors.description }"
                                    rows="2"
                                    placeholder="Brief description of this plan"
                                ></textarea>
                                <p v-if="errors.description" class="form-hint text-danger mt-1.5">{{ errors.description[0] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 pb-2 border-b border-gray-100">Pricing (IDR)</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Monthly Price -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">
                                    Monthly Price <span class="text-danger">*</span>
                                </label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 text-sm text-gray-700 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md font-medium">
                                        IDR
                                    </span>
                                    <input
                                        type="text"
                                        class="input rounded-l-none"
                                        :class="{ 'border-danger': errors.price_monthly }"
                                        :value="displayPriceMonthly"
                                        @input="handlePriceInput('monthly', $event)"
                                        placeholder="0"
                                    />
                                </div>
                                <p v-if="errors.price_monthly" class="form-hint text-danger mt-1.5">{{ errors.price_monthly[0] }}</p>
                            </div>

                            <!-- Yearly Price -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">
                                    Yearly Price <span class="text-danger">*</span>
                                </label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 text-sm text-gray-700 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md font-medium">
                                        IDR
                                    </span>
                                    <input
                                        type="text"
                                        class="input rounded-l-none"
                                        :class="{ 'border-danger': errors.price_yearly }"
                                        :value="displayPriceYearly"
                                        @input="handlePriceInput('yearly', $event)"
                                        placeholder="0"
                                    />
                                </div>
                                <p v-if="errors.price_yearly" class="form-hint text-danger mt-1.5">{{ errors.price_yearly[0] }}</p>
                                <p v-if="yearlyDiscount > 0" class="form-hint text-success mt-1.5">
                                    Save {{ yearlyDiscount }}% with yearly billing
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Limits Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900">Limits</h3>
                            <span class="form-hint">Set to 0 for unlimited</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Max Users -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">Max Users</label>
                                <input
                                    v-model.number="form.max_users"
                                    type="number"
                                    min="0"
                                    class="input"
                                    :class="{ 'border-danger': errors.max_users }"
                                    placeholder="0 = Unlimited"
                                />
                                <p v-if="errors.max_users" class="form-hint text-danger mt-1.5">{{ errors.max_users[0] }}</p>
                            </div>

                            <!-- Max Products -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">Max Products</label>
                                <input
                                    v-model.number="form.max_products"
                                    type="number"
                                    min="0"
                                    class="input"
                                    :class="{ 'border-danger': errors.max_products }"
                                    placeholder="0 = Unlimited"
                                />
                                <p v-if="errors.max_products" class="form-hint text-danger mt-1.5">{{ errors.max_products[0] }}</p>
                            </div>

                            <!-- Max Customers -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">Max Customers</label>
                                <input
                                    v-model.number="form.max_customers"
                                    type="number"
                                    min="0"
                                    class="input"
                                    :class="{ 'border-danger': errors.max_customers }"
                                    placeholder="0 = Unlimited"
                                />
                                <p v-if="errors.max_customers" class="form-hint text-danger mt-1.5">{{ errors.max_customers[0] }}</p>
                            </div>

                            <!-- Max Monthly Orders -->
                            <div>
                                <label class="form-label text-gray-700 mb-2.5">Max Monthly Orders</label>
                                <input
                                    v-model.number="form.max_monthly_orders"
                                    type="number"
                                    min="0"
                                    class="input"
                                    :class="{ 'border-danger': errors.max_monthly_orders }"
                                    placeholder="0 = Unlimited"
                                />
                                <p v-if="errors.max_monthly_orders" class="form-hint text-danger mt-1.5">{{ errors.max_monthly_orders[0] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Features Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 pb-2 border-b border-gray-100">Features</h3>
                        <div class="space-y-3">
                            <!-- Add Feature -->
                            <div class="flex gap-2.5">
                                <input
                                    v-model="newFeature"
                                    type="text"
                                    class="input flex-1"
                                    placeholder="Add a feature (e.g., Advanced reporting)"
                                    @keydown="handleFeatureKeydown"
                                />
                                <button
                                    type="button"
                                    @click="addFeature"
                                    class="btn btn-primary shrink-0"
                                >
                                    <i class="ki-filled ki-plus text-sm me-1"></i>
                                    Add
                                </button>
                            </div>

                            <!-- Feature List -->
                            <div v-if="form.features.length > 0" class="space-y-2">
                                <div
                                    v-for="(feature, index) in form.features"
                                    :key="index"
                                    class="flex items-center gap-2.5 px-3 py-2.5 bg-gray-50 rounded-lg group hover:bg-gray-100 transition-colors"
                                >
                                    <i class="ki-filled ki-check text-success"></i>
                                    <span class="flex-1 text-sm text-gray-700">{{ feature }}</span>
                                    <button
                                        type="button"
                                        @click="removeFeature(index)"
                                        class="btn btn-xs btn-icon btn-light opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <i class="ki-filled ki-trash text-danger"></i>
                                    </button>
                                </div>
                            </div>
                            <p v-else class="form-hint text-center py-4 bg-gray-50 rounded-lg">
                                No features added yet. Type a feature and press Enter or click Add.
                            </p>
                        </div>
                    </div>

                    <!-- Settings Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 pb-2 border-b border-gray-100">Settings</h3>
                        <div>
                            <!-- Active Status Toggle -->
                            <div class="flex flex-col gap-2">
                                <label class="form-label">Plan Status</label>
                                <label class="switch switch-sm">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="order-2"
                                        name="is_active"
                                    />
                                    <span class="switch-label order-1">
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </label>
                                <p class="form-hint">
                                    {{ form.is_active ? 'Companies can subscribe to this plan' : 'Plan is hidden from subscribers' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <button
                        type="button"
                        @click="close"
                        class="btn btn-light"
                        :disabled="saving"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="saving"
                    >
                        <i v-if="saving" class="ki-filled ki-loading animate-spin me-2"></i>
                        {{ saving ? 'Saving...' : (plan ? 'Update Plan' : 'Create Plan') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
