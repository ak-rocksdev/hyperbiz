<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    product: Object,
    stock: Object,
    movements: Array,
    movementPagination: Object,
});

const showAdjustModal = ref(false);
const showReorderModal = ref(false);
const isLoading = ref(false);

const adjustForm = ref({
    adjustment_type: 'add',
    quantity: 0,
    unit_cost: props.stock?.average_cost || 0,
    reason: '',
});

const reorderForm = ref({
    reorder_level: props.stock?.reorder_level || 0,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const stockStatusColors = {
    in_stock: 'badge-success',
    low_stock: 'badge-warning',
    out_of_stock: 'badge-danger',
};

const stockStatusLabels = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
};

const movementTypeColors = {
    purchase_in: 'text-green-600',
    sales_out: 'text-red-600',
    adjustment_in: 'text-blue-600',
    adjustment_out: 'text-orange-600',
    return_in: 'text-purple-600',
    return_out: 'text-pink-600',
    opening_stock: 'text-gray-600',
};

// Submit adjustment
const submitAdjustment = () => {
    if (adjustForm.value.quantity <= 0) {
        Swal.fire('Error', 'Quantity must be greater than 0', 'error');
        return;
    }
    if (!adjustForm.value.reason.trim()) {
        Swal.fire('Error', 'Please provide a reason for the adjustment', 'error');
        return;
    }

    isLoading.value = true;
    axios.post(`/inventory/api/adjust/${props.product.id}`, adjustForm.value)
        .then(() => {
            Swal.fire('Success!', 'Stock adjusted successfully.', 'success');
            showAdjustModal.value = false;
            router.reload();
        })
        .catch((error) => {
            Swal.fire('Error!', error.response?.data?.message || 'Failed to adjust stock.', 'error');
        })
        .finally(() => {
            isLoading.value = false;
        });
};

// Submit reorder level
const submitReorderLevel = () => {
    isLoading.value = true;
    axios.post(`/inventory/api/reorder-level/${props.product.id}`, reorderForm.value)
        .then(() => {
            Swal.fire('Success!', 'Reorder level updated.', 'success');
            showReorderModal.value = false;
            router.reload();
        })
        .catch((error) => {
            Swal.fire('Error!', error.response?.data?.message || 'Failed to update reorder level.', 'error');
        })
        .finally(() => {
            isLoading.value = false;
        });
};

const openAdjustModal = () => {
    adjustForm.value = {
        adjustment_type: 'add',
        quantity: 0,
        unit_cost: props.stock?.average_cost || 0,
        reason: '',
    };
    showAdjustModal.value = true;
};
</script>

<template>
    <AppLayout title="Inventory Detail">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('inventory.list')" class="btn btn-icon btn-light btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                        {{ product?.product_name }}
                    </h2>
                    <span :class="['badge', stockStatusColors[stock?.status]]">
                        {{ stockStatusLabels[stock?.status] }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button @click="openAdjustModal" class="btn btn-sm btn-primary">
                        <i class="ki-filled ki-plus-minus me-1"></i> Adjust Stock
                    </button>
                    <button @click="showReorderModal = true" class="btn btn-sm btn-light">
                        <i class="ki-filled ki-setting-2 me-1"></i> Set Reorder Level
                    </button>
                </div>
            </div>
        </template>

        <div class="container-fixed py-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left Column: Product & Stock Info -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Product Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Product Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <div>
                                    <span class="text-sm text-gray-500">Product Name</span>
                                    <p class="font-medium">{{ product?.product_name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">SKU</span>
                                    <p class="font-medium">{{ product?.sku || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Unit of Measure</span>
                                    <p class="font-medium">{{ product?.uom }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Selling Price</span>
                                    <p class="font-medium">{{ formatCurrency(product?.selling_price) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Summary Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Stock Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <p class="text-3xl font-bold text-gray-800">{{ formatNumber(stock?.quantity_on_hand) }}</p>
                                    <span class="text-sm text-gray-500">On Hand</span>
                                </div>
                                <div class="text-center p-4 bg-orange-50 rounded-lg">
                                    <p class="text-3xl font-bold text-orange-600">{{ formatNumber(stock?.quantity_reserved) }}</p>
                                    <span class="text-sm text-gray-500">Reserved</span>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <p class="text-3xl font-bold text-green-600">{{ formatNumber(stock?.quantity_available) }}</p>
                                    <span class="text-sm text-gray-500">Available</span>
                                </div>
                                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                    <p class="text-3xl font-bold text-yellow-600">{{ formatNumber(stock?.reorder_level) }}</p>
                                    <span class="text-sm text-gray-500">Reorder Level</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Movement History Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Stock Movement History</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollable-x-auto">
                                <table class="table table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">Date</th>
                                            <th class="min-w-[140px]">Type</th>
                                            <th class="w-[100px] text-center">Qty</th>
                                            <th class="w-[120px] text-end">Unit Cost</th>
                                            <th class="w-[100px] text-center">Before</th>
                                            <th class="w-[100px] text-center">After</th>
                                            <th class="min-w-[150px]">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="movement in movements" :key="movement.id">
                                            <td>{{ movement.movement_date }}</td>
                                            <td>
                                                <span :class="movementTypeColors[movement.movement_type]" class="font-medium">
                                                    {{ movement.movement_type_label }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span :class="movement.quantity > 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                                    {{ movement.quantity > 0 ? '+' : '' }}{{ formatNumber(movement.quantity) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ formatCurrency(movement.unit_cost) }}</td>
                                            <td class="text-center">{{ formatNumber(movement.quantity_before) }}</td>
                                            <td class="text-center font-medium">{{ formatNumber(movement.quantity_after) }}</td>
                                            <td class="text-sm text-gray-500">{{ movement.notes || '-' }}</td>
                                        </tr>
                                        <tr v-if="!movements || movements.length === 0">
                                            <td colspan="7" class="text-center text-gray-500 py-8">No movement history</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Cost & Value Info -->
                <div class="space-y-5">
                    <!-- Cost Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cost Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Last Cost</span>
                                    <span class="font-medium">{{ formatCurrency(stock?.last_cost) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Average Cost</span>
                                    <span class="font-medium">{{ formatCurrency(stock?.average_cost) }}</span>
                                </div>
                                <div class="border-t pt-4 flex justify-between items-center">
                                    <span class="font-semibold">Total Stock Value</span>
                                    <span class="text-lg font-bold text-primary">{{ formatCurrency(stock?.stock_value) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Status</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Stock Status</span>
                                    <span :class="['badge', stockStatusColors[stock?.status]]">
                                        {{ stockStatusLabels[stock?.status] }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Last Movement</span>
                                    <span>{{ stock?.last_movement_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Stock Modal -->
        <div v-if="showAdjustModal" class="modal open" data-modal="true">
            <div class="modal-content max-w-[500px]">
                <div class="modal-header">
                    <h3 class="modal-title">Adjust Stock</h3>
                    <button class="btn btn-xs btn-icon btn-light" @click="showAdjustModal = false">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Adjustment Type</label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="adjustForm.adjustment_type" value="add" class="radio" />
                                    <span class="text-green-600 font-medium">Add Stock</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="adjustForm.adjustment_type" value="deduct" class="radio" />
                                    <span class="text-red-600 font-medium">Deduct Stock</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Quantity</label>
                            <input type="number" v-model="adjustForm.quantity" class="input w-full" min="0.01" step="0.01" />
                        </div>
                        <div v-if="adjustForm.adjustment_type === 'add'">
                            <label class="form-label">Unit Cost</label>
                            <input type="number" v-model="adjustForm.unit_cost" class="input w-full" min="0" step="0.01" />
                        </div>
                        <div>
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea v-model="adjustForm.reason" class="textarea w-full" rows="3"
                                placeholder="Explain the reason for this adjustment..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="showAdjustModal = false">Cancel</button>
                    <button class="btn btn-primary" @click="submitAdjustment" :disabled="isLoading">
                        {{ isLoading ? 'Processing...' : 'Apply Adjustment' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reorder Level Modal -->
        <div v-if="showReorderModal" class="modal open" data-modal="true">
            <div class="modal-content max-w-[400px]">
                <div class="modal-header">
                    <h3 class="modal-title">Set Reorder Level</h3>
                    <button class="btn btn-xs btn-icon btn-light" @click="showReorderModal = false">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="form-label">Reorder Level</label>
                        <input type="number" v-model="reorderForm.reorder_level" class="input w-full" min="0" />
                        <p class="text-sm text-gray-500 mt-2">
                            You will be alerted when available stock falls below this level.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="showReorderModal = false">Cancel</button>
                    <button class="btn btn-primary" @click="submitReorderLevel" :disabled="isLoading">
                        {{ isLoading ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background-color: rgba(0, 0, 0, 0.5);
}
</style>
