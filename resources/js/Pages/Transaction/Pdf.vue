<script setup>
    import { ref, onMounted } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import axios from 'axios';

    const selectedTransaction = ref(null);

    const fetchTransactionDetails = async () => {
        // get id from inertiajs route params
        let id = router.page.props.transaction.id;
        try {
            const response = await axios.get(`/transaction/api/detail/${id}`);
            console.log('Transaction details:', response); 
            selectedTransaction.value = response.data.transaction;
        } catch (error) {
            console.error('Failed to fetch transaction details:', error);
        }
    };

    onMounted(fetchTransactionDetails);

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
    };
</script>

<style scoped>
    .container {
        max-width: 800px;
    }
</style>

<template>
    <div class="container mx-auto py-8">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Transaction Preview</h1>
            <Link :href="route('transaction.list')" class="btn btn-outline btn-primary transition-all">
                <i class="ki-filled ki-arrow-left me-2"></i>Back to Transactions
            </Link>
        </div>

        <!-- Transaction Details -->
        <div v-if="selectedTransaction">
            <div class="p-5">
                <!-- Transaction Summary -->
                <div class="flex flex-col gap-3 mb-4">
                    <div class="flex flex-row gap-3 w-full">
                        <label class="form-label !font-extrabold text-md !text-blue-500">Status</label>
                        <p class="!text-gray-500">
                            <span v-if="selectedTransaction.status" 
                                :class="{'badge-warning': selectedTransaction.status === 'pending', 'badge-success': selectedTransaction.status !== 'pending' }"
                                class="badge badge-outline">
                                {{ selectedTransaction.status }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Transaction Information -->
                <div class="p-5 mb-10 bg-white border-gray-300 border rounded-xl shadow-lg space-y-2 sm:py-4 sm:flex sm:items-start sm:space-y-0 sm:space-x-6">
                    <div class="mb-5">
                        <img :src="'https://picsum.photos/500'" alt="Product Image" class="sm:w-full lg:max-w-[200px] h-auto rounded shadow-lg" />
                    </div>
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div class="mb-3">
                            <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Client</label>
                            <p class="!text-gray-500">{{ selectedTransaction.client_name }}</p>
                        </div>
                        <div class="w-full">
                            <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Transaction Date</label>
                            <p class="!text-gray-500 text-sm">{{ selectedTransaction.transaction_date }}</p>
                        </div>
                        <div class="mb-3 w-full">
                            <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Transaction Type</label>
                            <span class="capitalize">
                                <span v-if="selectedTransaction.transaction_type === 'sell'">
                                    <i class="ki-solid ki-entrance-left me-1 text-green-500"></i> {{ selectedTransaction.transaction_type }}
                                </span>
                                <span v-else>
                                    <i class="ki-solid ki-exit-left me-1 text-orange-500"></i> {{ selectedTransaction.transaction_type }}
                                </span>
                            </span>
                        </div>
                        <div class="w-full">
                            <label class="form-label mb-1 !font-extrabold text-md !text-cyan-900">Transaction Code</label>
                            <p class="!text-gray-500">{{ selectedTransaction.transaction_code }}</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Products -->
                <div class="overflow-x-auto">
                    <table class="table-auto w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 border-b border-gray-300">
                                <th class="py-2 ps-4 min-w-[125px]">Product</th>
                                <th class="py-2 px-4 text-center">Quantity</th>
                                <th class="py-2 px-4 text-right">Price</th>
                                <th class="py-2 pe-4 text-right">Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(product, index) in selectedTransaction.details" :key="index" class="hover:bg-gray-100 border-b border-gray-300 border-dashed">
                                <td class="py-4 ps-4">{{ product.name }}</td>
                                <td class="py-4 px-4 w-[125px] text-center">{{ product.quantity }} Kg</td>
                                <td class="py-4 px-4 text-right w-[150px]">{{ formatCurrency(product.price) }}</td>
                                <td class="py-4 pe-4 w-[150px] text-end">{{ product.total_price }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex flex-row gap-3 w-full mt-5">
                        <label class="form-label !font-extrabold text-md !text-blue-700">Grand Total</label>
                        <p class="!text-green-600 font-bold">{{ selectedTransaction.grand_total }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="h-[350px] flex items-center justify-center">
            <p>Loading transaction details...</p>
        </div>
    </div>
</template>
