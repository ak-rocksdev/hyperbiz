<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    purchaseOrder: Object,
    suppliers: Array,
    products: Array,
    currencies: Array,
});

// Format suppliers for SearchableSelect
const supplierOptions = computed(() => {
    return (props.suppliers || []).map(s => ({
        value: s.id,
        label: s.client_name || s.name,
        sublabel: s.type,
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

// Clean number - remove unnecessary decimals
const cleanNum = (val) => {
    const num = parseFloat(val) || 0;
    return num % 1 === 0 ? Math.round(num) : parseFloat(num.toFixed(2));
};

const form = ref({
    supplier_id: props.purchaseOrder?.supplier_id || null,
    order_date: props.purchaseOrder?.order_date_raw || new Date().toISOString().split('T')[0],
    expected_date: props.purchaseOrder?.expected_date_raw || '',
    currency_code: props.purchaseOrder?.currency_code || 'IDR',
    exchange_rate: props.purchaseOrder?.exchange_rate || 1,
    tax_rate: cleanNum(props.purchaseOrder?.tax_percentage),
    discount_amount: cleanNum(props.purchaseOrder?.discount_value),
    shipping_cost: cleanNum(props.purchaseOrder?.shipping_cost),
    notes: props.purchaseOrder?.notes || '',
    items: (props.purchaseOrder?.items || []).map(item => ({
        id: item.id,
        product_id: item.product_id,
        product_name: item.product_name,
        sku: item.sku,
        quantity: cleanNum(item.quantity),
        unit_cost: cleanNum(item.unit_cost),
        discount_percentage: cleanNum(item.discount_percentage),
        notes: item.notes || '',
    })),
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
        p.name?.toLowerCase().includes(query) ||
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
            id: null,
            product_id: product.id,
            product_name: product.product_name || product.name,
            sku: product.sku,
            quantity: 1,
            unit_cost: product.cost_price || 0,
            discount_percentage: 0,
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
    const gross = item.quantity * item.unit_cost;
    const discountAmount = gross * ((item.discount_percentage || 0) / 100);
    return gross - discountAmount;
};

// Calculate totals
const subtotal = computed(() => {
    return form.value.items.reduce((sum, item) => sum + itemSubtotal(item), 0);
});

const totalDiscount = computed(() => {
    return form.value.items.reduce((sum, item) => {
        const gross = item.quantity * item.unit_cost;
        return sum + (gross * ((item.discount_percentage || 0) / 100));
    }, 0) + (form.value.discount_amount || 0);
});

const totalTax = computed(() => {
    const taxableAmount = subtotal.value - (form.value.discount_amount || 0);
    return taxableAmount * (form.value.tax_rate / 100);
});

const grandTotal = computed(() => {
    return subtotal.value - totalDiscount.value + totalTax.value + (form.value.shipping_cost || 0);
});

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.value.currency_code || 'IDR'
    }).format(value || 0);
};

// Status helpers
const currentStatus = computed(() => (props.purchaseOrder?.status || 'draft').toLowerCase());
const isDraft = computed(() => currentStatus.value === 'draft');

const statusLabel = computed(() => {
    const labels = {
        draft: 'Draft',
        confirmed: 'Confirmed',
        partial: 'Partial',
        received: 'Received',
        cancelled: 'Cancelled'
    };
    return labels[currentStatus.value] || currentStatus.value;
});

const statusBadgeClass = computed(() => {
    const classes = {
        draft: 'badge-warning',
        confirmed: 'badge-info',
        partial: 'badge-primary',
        received: 'badge-success',
        cancelled: 'badge-danger'
    };
    return classes[currentStatus.value] || 'badge-light';
});

// Action states
const isConfirming = ref(false);
const isDeleting = ref(false);

// Update exchange rate when currency changes
watch(() => form.value.currency_code, (newCurrency) => {
    const currency = props.currencies?.find(c => c.code === newCurrency);
    if (currency) {
        form.value.exchange_rate = currency.exchange_rate;
    }
});

// Submit form (save only)
const submitForm = (redirectToDetail = true) => {
    return new Promise((resolve, reject) => {
        if (form.value.items.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Products',
                text: 'Please add at least one product to the purchase order.',
            });
            reject('No items');
            return;
        }

        isSubmitting.value = true;
        errors.value = {};

        axios.put(`/purchase-orders/api/update/${props.purchaseOrder.id}`, form.value)
            .then(response => {
                if (redirectToDetail) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                    });
                    router.visit(`/purchase-orders/${props.purchaseOrder.id}`);
                }
                resolve(response);
            })
            .catch(err => {
                if (err.response?.data?.errors) {
                    errors.value = err.response.data.errors;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.response?.data?.message || 'Failed to update purchase order',
                });
                reject(err);
            })
            .finally(() => {
                isSubmitting.value = false;
            });
    });
};

// Save and Confirm - saves the draft then confirms it
const saveAndConfirm = async () => {
    const result = await Swal.fire({
        title: 'Confirm Purchase Order?',
        html: `
            <div class="text-left text-gray-600">
                <p class="mb-3">This will:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Save all current changes</li>
                    <li>Change status from <strong>Draft</strong> to <strong>Confirmed</strong></li>
                    <li>Lock the order from further edits</li>
                </ul>
                <p class="mt-3 text-sm text-warning">You won't be able to edit product details after confirming.</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Save & Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#50cd89',
    });

    if (!result.isConfirmed) return;

    try {
        // First save the form
        await submitForm(false);

        // Then confirm the order
        isConfirming.value = true;
        await axios.post(`/purchase-orders/api/confirm/${props.purchaseOrder.id}`);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Order Confirmed',
            text: 'Purchase order has been saved and confirmed.',
        });
        router.visit(`/purchase-orders/${props.purchaseOrder.id}`);
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || 'Failed to confirm order',
        });
    } finally {
        isConfirming.value = false;
    }
};

// Delete draft order
const deleteDraft = async () => {
    const result = await Swal.fire({
        title: 'Delete Draft?',
        html: `
            <div class="text-left text-gray-600">
                <p>Are you sure you want to delete this purchase order draft?</p>
                <p class="mt-2 text-sm text-danger font-medium">This action cannot be undone.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f1416c',
    });

    if (!result.isConfirmed) return;

    try {
        isDeleting.value = true;
        await axios.delete(`/purchase-orders/api/delete/${props.purchaseOrder.id}`);

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: 'success',
            title: 'Deleted',
            text: 'Purchase order draft has been deleted.',
        });
        router.visit('/purchase-orders');
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || 'Failed to delete order',
        });
    } finally {
        isDeleting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Edit Purchase Order">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="`/purchase-orders/${purchaseOrder.id}`" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-outline ki-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit {{ purchaseOrder.po_number }}
                </h2>
                <span :class="statusBadgeClass" class="badge badge-sm">
                    {{ statusLabel }}
                </span>
            </div>
        </template>

        <div class="container-fixed py-5">
            <div class="flex flex-col lg:flex-row gap-5">
                <!-- Left Column: Form -->
                <div class="w-full lg:w-2/3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Purchase Order Details</h3>
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
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <SearchableSelect
                                        v-model="form.supplier_id"
                                        :options="supplierOptions"
                                        placeholder="Select Supplier"
                                        search-placeholder="Search suppliers..."
                                        clearable
                                    />
                                </div>
                                <div>
                                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" v-model="form.order_date" class="input w-full" />
                                </div>
                                <div>
                                    <label class="form-label">Expected Delivery</label>
                                    <input type="date" v-model="form.expected_date" class="input w-full" />
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
                                    <input type="number" v-model="form.tax_rate" class="input w-full" step="0.01" min="0" />
                                </div>
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
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-light text-primary font-bold">
                                                    {{ (product.product_name || product.name)?.charAt(0).toUpperCase() }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-medium">{{ product.product_name || product.name }}</div>
                                                <div class="text-xs text-gray-500">SKU: {{ product.sku || 'N/A' }}</div>
                                            </td>
                                            <td class="text-end">{{ formatCurrency(product.cost_price) }}</td>
                                            <td class="w-10">
                                                <i class="ki-outline ki-plus-squared text-primary text-lg"></i>
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
                                            <th class="min-w-[180px]">Product</th>
                                            <th class="w-[90px] text-center">Qty</th>
                                            <th class="w-[130px] text-end">Unit Cost</th>
                                            <th class="w-[100px] text-center">Disc %</th>
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
                                            <td class="px-2">
                                                <input type="number" v-model.number="item.quantity"
                                                    class="input input-sm text-center w-full min-w-[70px]" min="1" />
                                            </td>
                                            <td class="px-2">
                                                <input type="number" v-model.number="item.unit_cost"
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
                                            <td colspan="6">
                                                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                                    <i class="ki-outline ki-handcart text-5xl mb-3"></i>
                                                    <span>No products added yet</span>
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
                                    placeholder="Internal notes for this purchase order..."></textarea>
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
                                    <span class="text-gray-600">Shipping Cost</span>
                                    <input type="number" v-model="form.shipping_cost"
                                        class="input input-sm w-[120px] text-end" min="0" step="0.01" />
                                </div>
                                <div class="border-t pt-3">
                                    <div class="flex justify-between">
                                        <span class="text-lg font-semibold">Grand Total</span>
                                        <span class="text-lg font-bold text-primary">{{ formatCurrency(grandTotal) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-6 space-y-3">
                                <!-- Status Info -->
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
                                    <span class="text-sm text-gray-600">Current Status</span>
                                    <span :class="statusBadgeClass" class="badge">{{ statusLabel }}</span>
                                </div>

                                <!-- Draft Actions -->
                                <template v-if="isDraft">
                                    <Link :href="`/purchase-orders/${purchaseOrder.id}`" class="btn btn-light w-full">
                                        <i class="ki-outline ki-arrow-left me-2"></i>
                                        Discard Changes
                                    </Link>
                                    <button type="button" @click="submitForm()"
                                        :disabled="isSubmitting || isConfirming"
                                        class="btn btn-primary w-full">
                                        <i class="ki-outline ki-check me-2"></i>
                                        {{ isSubmitting ? 'Saving...' : 'Save Draft' }}
                                    </button>
                                    <button type="button" @click="saveAndConfirm"
                                        :disabled="isSubmitting || isConfirming"
                                        class="btn btn-success w-full">
                                        <i class="ki-outline ki-check-circle me-2"></i>
                                        {{ isConfirming ? 'Confirming...' : 'Save & Confirm' }}
                                    </button>
                                    <div class="border-t pt-3">
                                        <button type="button" @click="deleteDraft"
                                            :disabled="isDeleting"
                                            class="btn btn-light-danger w-full">
                                            <i class="ki-outline ki-trash me-2"></i>
                                            {{ isDeleting ? 'Deleting...' : 'Delete Draft' }}
                                        </button>
                                    </div>
                                </template>

                                <!-- Non-Draft Actions (view only) -->
                                <template v-else>
                                    <div class="p-3 bg-blue-50 rounded-lg text-sm text-blue-700">
                                        <i class="ki-outline ki-information-2 me-2"></i>
                                        This order is no longer a draft and cannot be edited.
                                    </div>
                                    <Link :href="`/purchase-orders/${purchaseOrder.id}`" class="btn btn-primary w-full">
                                        <i class="ki-outline ki-arrow-left me-2"></i>
                                        Back to Detail
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
