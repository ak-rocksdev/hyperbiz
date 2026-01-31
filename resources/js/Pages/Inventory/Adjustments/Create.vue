<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    selectedProduct: Object,
    products: Array,
    reasons: Array,
});

// Mode: single or bulk
const mode = ref('single');

// Single adjustment form
const singleForm = ref({
    product_id: props.selectedProduct?.id || null,
    adjustment_type: 'add',
    quantity: '',
    unit_cost: props.selectedProduct?.average_cost || '',
    reason_code: '',
    notes: '',
});

// Bulk adjustments
const bulkItems = ref([]);
const batchNotes = ref('');

// Loading state
const isLoading = ref(false);

// Initialize with selected product if provided
if (props.selectedProduct) {
    mode.value = 'single';
    singleForm.value.product_id = props.selectedProduct.id;
    singleForm.value.unit_cost = props.selectedProduct.average_cost;
}

// Format helpers
const formatNumber = (value) => {
    if (value == null) return '0';
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0
    }).format(Math.floor(Number(value) || 0));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

// Get selected product details
const selectedProductDetails = computed(() => {
    if (!singleForm.value.product_id) return null;
    return props.products?.find(p => p.id === singleForm.value.product_id);
});

// Watch for product change to update unit cost
watch(() => singleForm.value.product_id, (newId) => {
    if (newId && singleForm.value.adjustment_type === 'add') {
        const product = props.products?.find(p => p.id === newId);
        if (product) {
            singleForm.value.unit_cost = product.average_cost || 0;
        }
    }
});

// Filter reasons based on adjustment type
const filteredReasons = computed(() => {
    const type = singleForm.value.adjustment_type;
    return props.reasons?.filter(r => r.type === 'both' || r.type === type) || [];
});

// Filter bulk item reasons
const getFilteredReasonsForItem = (adjustmentType) => {
    return props.reasons?.filter(r => r.type === 'both' || r.type === adjustmentType) || [];
};

// Add new bulk item
const addBulkItem = () => {
    bulkItems.value.push({
        id: Date.now(),
        product_id: null,
        adjustment_type: 'deduct',
        quantity: '',
        unit_cost: '',
        reason_code: '',
        notes: '',
    });
};

// Remove bulk item
const removeBulkItem = (index) => {
    bulkItems.value.splice(index, 1);
};

// Get product for bulk item
const getBulkItemProduct = (productId) => {
    return props.products?.find(p => p.id === productId);
};

// Update bulk item unit cost when product changes
const updateBulkItemCost = (item) => {
    if (item.product_id && item.adjustment_type === 'add') {
        const product = props.products?.find(p => p.id === item.product_id);
        if (product) {
            item.unit_cost = product.average_cost || 0;
        }
    }
};

// Submit single adjustment
const submitSingle = async () => {
    if (!singleForm.value.product_id) {
        Swal.fire('Error', 'Please select a product', 'error');
        return;
    }
    if (!singleForm.value.quantity || singleForm.value.quantity <= 0) {
        Swal.fire('Error', 'Quantity must be greater than 0', 'error');
        return;
    }
    if (!singleForm.value.reason_code) {
        Swal.fire('Error', 'Please select a reason', 'error');
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.post('/inventory/api/adjustments/store', singleForm.value);

        if (response.data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false,
            });
            router.visit(route('inventory.adjustments'));
        }
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Failed to save adjustment', 'error');
    } finally {
        isLoading.value = false;
    }
};

// Submit bulk adjustments
const submitBulk = async () => {
    if (bulkItems.value.length === 0) {
        Swal.fire('Error', 'Please add at least one item', 'error');
        return;
    }

    // Validate all items
    for (let i = 0; i < bulkItems.value.length; i++) {
        const item = bulkItems.value[i];
        if (!item.product_id) {
            Swal.fire('Error', `Row ${i + 1}: Please select a product`, 'error');
            return;
        }
        if (!item.quantity || item.quantity <= 0) {
            Swal.fire('Error', `Row ${i + 1}: Quantity must be greater than 0`, 'error');
            return;
        }
        if (!item.reason_code) {
            Swal.fire('Error', `Row ${i + 1}: Please select a reason`, 'error');
            return;
        }
    }

    isLoading.value = true;

    try {
        const response = await axios.post('/inventory/api/adjustments/bulk', {
            adjustments: bulkItems.value.map(item => ({
                product_id: item.product_id,
                adjustment_type: item.adjustment_type,
                quantity: item.quantity,
                unit_cost: item.unit_cost || null,
                reason_code: item.reason_code,
                notes: item.notes,
            })),
            batch_notes: batchNotes.value,
        });

        if (response.data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false,
            });
            router.visit(route('inventory.adjustments'));
        }
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Failed to save adjustments', 'error');
    } finally {
        isLoading.value = false;
    }
};

// Product options for searchable select
const productOptions = computed(() => {
    return props.products?.map(p => ({
        value: p.id,
        label: p.name,
        sublabel: `SKU: ${p.sku || '-'} | Stock: ${formatNumber(p.quantity_on_hand)} ${p.uom}`,
    })) || [];
});

// Reason options for searchable select (single form)
const reasonOptions = computed(() => {
    return filteredReasons.value?.map(r => ({
        value: r.code,
        label: r.name
    })) || [];
});

// Reason options for bulk item
const getReasonOptionsForItem = (adjustmentType) => {
    return getFilteredReasonsForItem(adjustmentType).map(r => ({
        value: r.code,
        label: r.name
    }));
};
</script>

<template>
    <AppLayout title="Create Stock Adjustment">
        <div class="container-fixed py-5">
            <!-- Page Header -->
            <div class="flex items-center gap-3 mb-5">
                <Link :href="route('inventory.adjustments')" class="btn btn-icon btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left"></i>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create Stock Adjustment</h1>
                    <p class="text-sm text-gray-500 mt-1">Record stock corrections, damages, losses, or other adjustments</p>
                </div>
            </div>

            <!-- Mode Selector -->
            <div class="card mb-5">
                <div class="card-body p-4">
                    <label class="form-label text-sm font-medium mb-3">Adjustment Mode</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-all hover:border-primary"
                            :class="mode === 'single' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" v-model="mode" value="single" class="radio radio-sm" />
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">Single Product</span>
                                <p class="text-xs text-gray-500 mt-1">Adjust one product at a time</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-all hover:border-primary"
                            :class="mode === 'bulk' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" v-model="mode" value="bulk" class="radio radio-sm" />
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">Bulk Adjustment</span>
                                <p class="text-xs text-gray-500 mt-1">Adjust multiple products at once</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Single Adjustment Form -->
            <div v-if="mode === 'single'" class="card">
                <div class="card-header">
                    <h3 class="card-title">Single Product Adjustment</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Product Selection -->
                        <div class="md:col-span-2">
                            <label class="form-label">Product <span class="text-danger">*</span></label>
                            <SearchableSelect
                                v-model="singleForm.product_id"
                                :options="productOptions"
                                placeholder="Select a product..."
                                search-placeholder="Search by name or SKU..."
                                clearable
                            />
                        </div>

                        <!-- Selected Product Info -->
                        <div v-if="selectedProductDetails" class="md:col-span-2 p-4 bg-gray-50 dark:bg-coal-500 rounded-lg">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <span class="text-xs text-gray-500">Current Stock</span>
                                    <p class="text-lg font-semibold">{{ formatNumber(selectedProductDetails.quantity_on_hand) }} {{ selectedProductDetails.uom }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Available</span>
                                    <p class="text-lg font-semibold text-success">{{ formatNumber(selectedProductDetails.quantity_available) }} {{ selectedProductDetails.uom }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Average Cost</span>
                                    <p class="text-lg font-semibold">{{ formatCurrency(selectedProductDetails.average_cost) }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">SKU</span>
                                    <p class="text-lg font-semibold">{{ selectedProductDetails.sku || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Adjustment Type -->
                        <div>
                            <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer flex-1 transition-all"
                                    :class="singleForm.adjustment_type === 'add' ? 'border-success bg-success/10' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" v-model="singleForm.adjustment_type" value="add" class="radio radio-sm" />
                                    <i class="ki-filled ki-arrow-up text-success"></i>
                                    <span class="font-medium text-success">Add Stock</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer flex-1 transition-all"
                                    :class="singleForm.adjustment_type === 'deduct' ? 'border-danger bg-danger/10' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" v-model="singleForm.adjustment_type" value="deduct" class="radio radio-sm" />
                                    <i class="ki-filled ki-arrow-down text-danger"></i>
                                    <span class="font-medium text-danger">Deduct Stock</span>
                                </label>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <SearchableSelect
                                v-model="singleForm.reason_code"
                                :options="reasonOptions"
                                placeholder="Select a reason..."
                                search-placeholder="Search reason..."
                                clearable
                            />
                        </div>

                        <!-- Quantity -->
                        <div>
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input
                                v-model="singleForm.quantity"
                                type="number"
                                class="input w-full"
                                placeholder="Enter quantity"
                                min="0.001"
                                step="0.001"
                            />
                            <p v-if="singleForm.adjustment_type === 'deduct' && selectedProductDetails" class="text-xs text-gray-500 mt-1">
                                Max available: {{ formatNumber(selectedProductDetails.quantity_available) }}
                            </p>
                        </div>

                        <!-- Unit Cost (only for Add) -->
                        <div v-if="singleForm.adjustment_type === 'add'">
                            <label class="form-label">Unit Cost</label>
                            <input
                                v-model="singleForm.unit_cost"
                                type="number"
                                class="input w-full"
                                placeholder="Enter unit cost"
                                min="0"
                                step="0.01"
                            />
                            <p class="text-xs text-gray-500 mt-1">Leave empty to use average cost</p>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="singleForm.notes"
                                class="textarea w-full"
                                rows="3"
                                placeholder="Additional notes about this adjustment..."
                            ></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer flex justify-end gap-3">
                    <Link :href="route('inventory.adjustments')" class="btn btn-light">Cancel</Link>
                    <button @click="submitSingle" :disabled="isLoading" class="btn btn-primary">
                        <i v-if="isLoading" class="ki-filled ki-loading animate-spin me-2"></i>
                        <i v-else class="ki-filled ki-check me-2"></i>
                        {{ isLoading ? 'Saving...' : 'Save Adjustment' }}
                    </button>
                </div>
            </div>

            <!-- Bulk Adjustment Form -->
            <div v-if="mode === 'bulk'" class="space-y-5">
                <!-- Items Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Bulk Adjustment Items</h3>
                        <button @click="addBulkItem" class="btn btn-sm btn-light">
                            <i class="ki-filled ki-plus me-1"></i>
                            Add Item
                        </button>
                    </div>
                    <div class="card-body p-0 overflow-visible">
                        <div class="scrollable-x-auto overflow-y-visible min-h-[280px]">
                            <table class="table table-border">
                                <thead>
                                    <tr>
                                        <th class="w-[50px]">#</th>
                                        <th class="min-w-[250px]">Product</th>
                                        <th class="w-[100px] text-center">Current</th>
                                        <th class="w-[130px]">Type</th>
                                        <th class="w-[100px]">Qty</th>
                                        <th class="w-[120px]">Unit Cost</th>
                                        <th class="min-w-[180px]">Reason</th>
                                        <th class="min-w-[150px]">Notes</th>
                                        <th class="w-[50px]"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in bulkItems" :key="item.id">
                                        <td class="text-center text-gray-500">{{ index + 1 }}</td>
                                        <td>
                                            <SearchableSelect
                                                v-model="item.product_id"
                                                :options="productOptions"
                                                placeholder="Select product..."
                                                search-placeholder="Search..."
                                                size="sm"
                                                @update:modelValue="updateBulkItemCost(item)"
                                            />
                                        </td>
                                        <td class="text-center text-sm">
                                            <span v-if="item.product_id">
                                                {{ formatNumber(getBulkItemProduct(item.product_id)?.quantity_on_hand) }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td>
                                            <select v-model="item.adjustment_type" @change="updateBulkItemCost(item)" class="select select-sm w-full">
                                                <option value="add">+ Add</option>
                                                <option value="deduct">- Deduct</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input v-model="item.quantity" type="number" class="input input-sm w-full" placeholder="Qty" min="0.001" step="0.001" />
                                        </td>
                                        <td>
                                            <input
                                                v-model="item.unit_cost"
                                                type="number"
                                                class="input input-sm w-full"
                                                placeholder="Cost"
                                                min="0"
                                                step="0.01"
                                                :disabled="item.adjustment_type === 'deduct'"
                                            />
                                        </td>
                                        <td>
                                            <SearchableSelect
                                                v-model="item.reason_code"
                                                :options="getReasonOptionsForItem(item.adjustment_type)"
                                                placeholder="Select..."
                                                search-placeholder="Search reason..."
                                                size="sm"
                                            />
                                        </td>
                                        <td>
                                            <input v-model="item.notes" type="text" class="input input-sm w-full" placeholder="Notes..." />
                                        </td>
                                        <td>
                                            <button @click="removeBulkItem(index)" class="btn btn-sm btn-icon btn-light text-danger hover:bg-danger/10">
                                                <i class="ki-filled ki-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="bulkItems.length === 0">
                                        <td colspan="9" class="text-center py-10">
                                            <div class="flex flex-col items-center">
                                                <i class="ki-filled ki-notepad text-4xl text-gray-300 mb-3"></i>
                                                <p class="text-gray-500 mb-3">No items added yet</p>
                                                <button @click="addBulkItem" class="btn btn-sm btn-primary">
                                                    <i class="ki-filled ki-plus me-1"></i>
                                                    Add First Item
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Batch Notes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Batch Information</h3>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Batch Notes (Optional)</label>
                        <textarea
                            v-model="batchNotes"
                            class="textarea w-full"
                            rows="2"
                            placeholder="Common notes for all adjustments in this batch (e.g., Stock opname date, reference number)"
                        ></textarea>
                    </div>
                    <div class="card-footer flex justify-between items-center">
                        <span class="text-sm text-gray-500">{{ bulkItems.length }} item(s) to adjust</span>
                        <div class="flex gap-3">
                            <Link :href="route('inventory.adjustments')" class="btn btn-light">Cancel</Link>
                            <button @click="submitBulk" :disabled="isLoading || bulkItems.length === 0" class="btn btn-primary">
                                <i v-if="isLoading" class="ki-filled ki-loading animate-spin me-2"></i>
                                <i v-else class="ki-filled ki-check me-2"></i>
                                {{ isLoading ? 'Saving...' : 'Save All Adjustments' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
