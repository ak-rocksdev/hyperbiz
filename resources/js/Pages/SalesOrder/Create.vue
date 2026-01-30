<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    customers: Array,
    products: Array,
    currencies: Array,
    defaultCurrency: String,
    defaultTaxEnabled: Boolean,
    defaultTaxName: String,
    defaultTaxPercentage: Number,
    preselectedCustomerId: Number,
});

// Format customers for SearchableSelect
const customerOptions = computed(() => {
    return (props.customers || []).map(c => ({
        value: c.id,
        label: c.client_name,
        sublabel: c.type,
    }));
});

// Format currencies for SearchableSelect
const currencyOptions = computed(() => {
    return (props.currencies || []).map(c => ({
        value: c.code,
        label: c.name,
        sublabel: c.code,
    }));
});

const form = ref({
    customer_id: props.preselectedCustomerId || null,
    order_date: new Date().toISOString().split('T')[0],
    due_date: '',
    currency_code: props.defaultCurrency || 'IDR',
    exchange_rate: 1,
    tax_percentage: props.defaultTaxEnabled ? props.defaultTaxPercentage : 0,
    discount_value: 0,
    shipping_fee: 0,
    shipping_address: '',
    notes: '',
    items: [],
});

const errors = ref({});
const isSubmitting = ref(false);
const searchQuery = ref('');

// Filter products based on search
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products || [];
    const query = searchQuery.value.toLowerCase();
    return (props.products || []).filter(p =>
        p.product_name?.toLowerCase().includes(query) ||
        p.sku?.toLowerCase().includes(query)
    );
});

// Add product to items
const addProduct = (product) => {
    const existingIndex = form.value.items.findIndex(item => item.product_id === product.id);
    if (existingIndex !== -1) {
        form.value.items[existingIndex].quantity++;
    } else {
        form.value.items.push({
            product_id: product.id,
            product_name: product.product_name,
            sku: product.sku,
            quantity: 1,
            unit_price: product.selling_price || 0,
            discount_percentage: 0,
            available_stock: product.available_stock || 0,
            notes: '',
        });
    }
    searchQuery.value = '';
};

// Remove product from items
const removeProduct = (index) => {
    form.value.items.splice(index, 1);
};

// Calculate item subtotal
const itemSubtotal = (item) => {
    const gross = item.quantity * item.unit_price;
    const discountAmount = gross * ((item.discount_percentage || 0) / 100);
    return gross - discountAmount;
};

// Calculate totals
const subtotal = computed(() => {
    return form.value.items.reduce((sum, item) => sum + itemSubtotal(item), 0);
});

const totalDiscount = computed(() => {
    return form.value.items.reduce((sum, item) => {
        const gross = item.quantity * item.unit_price;
        return sum + (gross * ((item.discount_percentage || 0) / 100));
    }, 0) + (form.value.discount_value || 0);
});

const totalTax = computed(() => {
    const taxableAmount = subtotal.value - (form.value.discount_value || 0);
    return taxableAmount * ((form.value.tax_percentage || 0) / 100);
});

const grandTotal = computed(() => {
    return subtotal.value - totalDiscount.value + totalTax.value + (form.value.shipping_fee || 0);
});

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.value.currency_code || 'IDR'
    }).format(value || 0);
};

// Update exchange rate when currency changes
watch(() => form.value.currency_code, (newCurrency) => {
    const currency = props.currencies?.find(c => c.code === newCurrency);
    if (currency) {
        form.value.exchange_rate = currency.exchange_rate;
    }
});

// Update shipping address when customer changes
watch(() => form.value.customer_id, (newCustomerId) => {
    const customer = props.customers?.find(c => c.id === newCustomerId);
    if (customer) {
        form.value.shipping_address = customer.address || '';
    }
}, { immediate: true });

// Submit form
const submitForm = (action = 'draft') => {
    if (form.value.items.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Products',
            text: 'Please add at least one product to the sales order.',
        });
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    const payload = {
        ...form.value,
        save_as_draft: action === 'draft',
    };

    axios.post('/sales-orders/api/store', payload)
        .then(response => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Success',
                text: response.data.message,
            });
            router.visit(route('sales-orders.list'));
        })
        .catch(err => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.response?.data?.message || 'Failed to create sales order',
            });
        })
        .finally(() => {
            isSubmitting.value = false;
        });
};
</script>

<template>
    <AppLayout title="Create Sales Order">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('sales-orders.list')" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Create Sales Order
                </h2>
            </div>
        </template>

        <div class="container-fixed py-5">
            <div class="flex flex-col lg:flex-row gap-5">
                <!-- Left Column: Form -->
                <div class="w-full lg:w-2/3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sales Order Details</h3>
                        </div>
                        <div class="card-body">
                            <!-- Error Display -->
                            <div v-if="Object.keys(errors).length" class="bg-red-50 border-l-4 border-red-400 p-4 mb-5">
                                <div class="flex">
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700 font-medium">Please fix the following errors:</p>
                                        <ul class="mt-2 list-disc list-inside text-sm text-red-600">
                                            <li v-for="(messages, field) in errors" :key="field">
                                                <span v-for="msg in messages" :key="msg">{{ msg }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                                    <SearchableSelect
                                        v-model="form.customer_id"
                                        :options="customerOptions"
                                        placeholder="Select Customer"
                                        search-placeholder="Search customers..."
                                        clearable
                                    />
                                </div>
                                <div>
                                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" v-model="form.order_date" class="input w-full" />
                                </div>
                                <div>
                                    <label class="form-label">Due Date</label>
                                    <input type="date" v-model="form.due_date" class="input w-full" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="form-label">Currency</label>
                                    <SearchableSelect
                                        v-model="form.currency_code"
                                        :options="currencyOptions"
                                        placeholder="Select Currency"
                                        search-placeholder="Search currency..."
                                    />
                                </div>
                                <div>
                                    <label class="form-label">Exchange Rate</label>
                                    <input type="number" v-model="form.exchange_rate" class="input w-full" step="0.0001" />
                                </div>
                                <div>
                                    <label class="form-label">Tax Rate (%)</label>
                                    <input type="number" v-model="form.tax_percentage" class="input w-full" step="0.01" min="0" />
                                </div>
                            </div>

                            <!-- Shipping Address -->
                            <div class="mb-6">
                                <label class="form-label">Shipping Address</label>
                                <textarea v-model="form.shipping_address" class="textarea w-full" rows="2"
                                    placeholder="Delivery address..."></textarea>
                            </div>

                            <!-- Products Search -->
                            <div class="border-t pt-5 mb-4">
                                <label class="form-label">Add Products</label>
                                <div class="relative">
                                    <i class="ki-outline ki-magnifier absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                    <input type="text" v-model="searchQuery"
                                        class="input w-full pl-10"
                                        placeholder="Search products by name or SKU..." />
                                </div>
                            </div>

                            <!-- Products List to Add -->
                            <div v-if="searchQuery" class="border rounded-lg max-h-60 overflow-y-auto mb-5">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr v-for="product in filteredProducts" :key="product.id"
                                            class="hover:bg-gray-50 cursor-pointer"
                                            @click="addProduct(product)">
                                            <td class="w-12">
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-success-light text-success font-bold">
                                                    {{ product.product_name?.charAt(0).toUpperCase() }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-medium">{{ product.product_name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    SKU: {{ product.sku || 'N/A' }} |
                                                    Stock: <span :class="product.available_stock > 0 ? 'text-success' : 'text-danger'">
                                                        {{ product.available_stock || 0 }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ formatCurrency(product.selling_price) }}</td>
                                            <td class="w-10">
                                                <i class="ki-filled ki-plus-squared text-success"></i>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredProducts.length === 0">
                                            <td colspan="4" class="text-center text-gray-500 py-4">No products found</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Selected Items Table -->
                            <div class="border rounded-lg overflow-hidden">
                                <table class="table table-border w-full">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[160px]">Product</th>
                                            <th class="w-[70px] text-center">Stock</th>
                                            <th class="w-[90px] text-center">Qty</th>
                                            <th class="w-[130px] text-end">Unit Price</th>
                                            <th class="w-[90px] text-center">Disc %</th>
                                            <th class="w-[130px] text-end">Subtotal</th>
                                            <th class="w-[60px] text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td>
                                                <div class="font-medium text-gray-900">{{ item.product_name }}</div>
                                                <div class="text-xs text-gray-500">SKU: {{ item.sku || 'N/A' }}</div>
                                            </td>
                                            <td class="text-center">
                                                <span :class="item.available_stock >= item.quantity ? 'text-success' : 'text-danger'" class="font-medium">
                                                    {{ item.available_stock }}
                                                </span>
                                            </td>
                                            <td class="px-2">
                                                <input type="number" v-model.number="item.quantity"
                                                    class="input input-sm text-center w-full min-w-[70px]" min="1" />
                                            </td>
                                            <td class="px-2">
                                                <input type="number" v-model.number="item.unit_price"
                                                    class="input input-sm text-end w-full min-w-[100px]" min="0" step="0.01" />
                                            </td>
                                            <td class="px-2">
                                                <input type="number" v-model.number="item.discount_percentage"
                                                    class="input input-sm text-center w-full min-w-[70px]" min="0" max="100" step="0.1" />
                                            </td>
                                            <td class="text-end font-medium text-gray-900 whitespace-nowrap">{{ formatCurrency(itemSubtotal(item)) }}</td>
                                            <td class="text-center">
                                                <button type="button" @click="removeProduct(index)"
                                                    class="btn btn-icon btn-xs btn-light hover:btn-danger">
                                                    <i class="ki-outline ki-trash text-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.items.length === 0">
                                            <td colspan="7">
                                                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                                    <i class="ki-outline ki-handcart text-5xl mb-3"></i>
                                                    <span>No products added yet</span>
                                                    <span class="text-sm">Search and click on products above to add them</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Notes -->
                            <div class="mt-5">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" class="textarea w-full" rows="3"
                                    placeholder="Internal notes for this sales order..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary -->
                <div class="w-full lg:w-1/3">
                    <div class="card sticky top-5">
                        <div class="card-header">
                            <h3 class="card-title">Order Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Discount</span>
                                    <span class="font-medium text-red-500">-{{ formatCurrency(totalDiscount) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax</span>
                                    <span class="font-medium">{{ formatCurrency(totalTax) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Shipping</span>
                                    <input type="number" v-model="form.shipping_fee"
                                        class="input input-sm w-[120px] text-end" min="0" step="0.01" />
                                </div>
                                <div class="border-t pt-3">
                                    <div class="flex justify-between">
                                        <span class="text-lg font-semibold">Grand Total</span>
                                        <span class="text-lg font-bold text-success">{{ formatCurrency(grandTotal) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <button type="button" @click="submitForm('draft')"
                                    :disabled="isSubmitting"
                                    class="btn btn-light w-full">
                                    <i class="ki-filled ki-file me-2"></i>
                                    Save as Draft
                                </button>
                                <button type="button" @click="submitForm('confirm')"
                                    :disabled="isSubmitting"
                                    class="btn btn-success w-full">
                                    <i class="ki-filled ki-check me-2"></i>
                                    Save & Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
