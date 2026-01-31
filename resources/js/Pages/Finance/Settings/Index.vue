<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

// Props from controller
const props = defineProps({
    settings: {
        type: Object,
        required: true
    },
    accounts: {
        type: Array,
        default: () => []
    },
    settingGroups: {
        type: Object,
        default: () => ({
            general: 'General Settings',
            journal: 'Auto-Journal Settings',
            tax: 'Tax Settings',
            accounts: 'Default Account Mappings',
        })
    }
});

const page = usePage();

// Check permission helper
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Current active tab
const currentTab = ref('general');

// Tab configuration with icons
const tabs = [
    { id: 'general', label: 'General', icon: 'ki-filled ki-setting-2' },
    { id: 'journal', label: 'Auto-Journal', icon: 'ki-filled ki-book' },
    { id: 'tax', label: 'Tax Settings', icon: 'ki-filled ki-cheque' },
    { id: 'accounts', label: 'Default Accounts', icon: 'ki-filled ki-wallet' },
];

// Form state - deep copy of settings to track changes
const originalSettings = JSON.parse(JSON.stringify(props.settings));
const form = ref(JSON.parse(JSON.stringify(props.settings)));

// UI state
const isSubmitting = ref(false);
const errors = ref({});

// Compute account options for SearchableSelect
const accountOptions = computed(() => {
    return props.accounts.map(account => ({
        value: account.id,
        label: account.full_name || `${account.code} - ${account.name}`,
        sublabel: account.type ? account.type.charAt(0).toUpperCase() + account.type.slice(1) : ''
    }));
});

// Check if form has unsaved changes
const hasUnsavedChanges = computed(() => {
    return JSON.stringify(form.value) !== JSON.stringify(originalSettings);
});

// Switch tab
const switchTab = (tabId) => {
    currentTab.value = tabId;
};

// Get settings for a specific group
const getSettingsByGroup = (group) => {
    const result = {};
    Object.entries(form.value).forEach(([key, setting]) => {
        if (setting.group === group) {
            result[key] = setting;
        }
    });
    return result;
};

// Get setting label from key
const getSettingLabel = (key) => {
    const labels = {
        // General
        financial_module_enabled: 'Enable Financial Module',
        // Journal
        auto_journal_enabled: 'Enable Auto-Journal',
        auto_journal_sales: 'Auto-Journal for Sales Orders',
        auto_journal_purchase: 'Auto-Journal for Purchase Orders',
        auto_journal_payment: 'Auto-Journal for Payments',
        // Tax
        tax_ppn_enabled: 'Enable PPN (VAT)',
        tax_ppn_rate: 'PPN Rate (%)',
        tax_pph21_enabled: 'Enable PPH 21',
        tax_pph23_enabled: 'Enable PPH 23',
        tax_pph4_enabled: 'Enable PPH 4(2)',
        faktur_pajak_enabled: 'Enable Faktur Pajak',
        company_npwp: 'Company NPWP',
        // Accounts
        default_ar_account: 'Default Accounts Receivable',
        default_ap_account: 'Default Accounts Payable',
        default_sales_account: 'Default Sales Revenue',
        default_cogs_account: 'Default Cost of Goods Sold',
        default_inventory_account: 'Default Inventory',
        default_cash_account: 'Default Cash Account',
        default_bank_account: 'Default Bank Account',
        default_ppn_in_account: 'Default PPN In (VAT Input)',
        default_ppn_out_account: 'Default PPN Out (VAT Output)',
        default_retained_earnings_account: 'Default Retained Earnings',
    };
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Get setting description
const getSettingDescription = (key) => {
    const descriptions = {
        // General
        financial_module_enabled: 'Enable or disable the entire financial module functionality.',
        // Journal
        auto_journal_enabled: 'Master switch for automatic journal entry creation.',
        auto_journal_sales: 'Automatically create journal entries when sales orders are confirmed.',
        auto_journal_purchase: 'Automatically create journal entries when purchase orders are received.',
        auto_journal_payment: 'Automatically create journal entries when payments are recorded.',
        // Tax
        tax_ppn_enabled: 'Enable PPN (Pajak Pertambahan Nilai) tax calculations.',
        tax_ppn_rate: 'Default PPN rate percentage.',
        tax_pph21_enabled: 'Enable PPH 21 (Employee Income Tax) calculations.',
        tax_pph23_enabled: 'Enable PPH 23 (Service Withholding Tax) calculations.',
        tax_pph4_enabled: 'Enable PPH 4(2) (Final Income Tax) calculations.',
        faktur_pajak_enabled: 'Enable Faktur Pajak (Tax Invoice) generation.',
        company_npwp: 'Company tax identification number (NPWP).',
        // Accounts
        default_ar_account: 'Default account for trade receivables.',
        default_ap_account: 'Default account for trade payables.',
        default_sales_account: 'Default account for sales revenue.',
        default_cogs_account: 'Default account for cost of goods sold.',
        default_inventory_account: 'Default account for inventory.',
        default_cash_account: 'Default account for cash transactions.',
        default_bank_account: 'Default account for bank transactions.',
        default_ppn_in_account: 'Default account for VAT input (purchases).',
        default_ppn_out_account: 'Default account for VAT output (sales).',
        default_retained_earnings_account: 'Default account for retained earnings.',
    };
    return descriptions[key] || form.value[key]?.description || '';
};

// Clear field error on input
const clearError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

// Submit form
const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};

    // Prepare data for submission
    const settingsData = {};
    Object.entries(form.value).forEach(([key, setting]) => {
        settingsData[key] = setting.value;
    });

    try {
        const response = await axios.post('/finance/settings/api/update', {
            settings: settingsData
        });

        // Update original settings to match saved state
        Object.keys(form.value).forEach(key => {
            originalSettings[key] = { ...form.value[key] };
        });

        // Show success toast
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: 'success',
            title: response.data.message || 'Settings saved successfully.',
        });

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
                title: error.response?.data?.message || 'An error occurred while saving settings.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Reset form to original values
const resetForm = () => {
    if (!hasUnsavedChanges.value) return;

    Swal.fire({
        title: 'Reset Changes?',
        text: 'This will discard all unsaved changes.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            form.value = JSON.parse(JSON.stringify(originalSettings));
            errors.value = {};
        }
    });
};
</script>

<template>
    <AppLayout title="Financial Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Financial Settings
            </h2>
        </template>

        <div class="container-fixed">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 py-4 text-sm">
                <Link :href="route('dashboard')" class="text-gray-500 hover:text-primary transition-colors">
                    <i class="ki-filled ki-home text-gray-400"></i>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 font-medium">Financial Settings</span>
            </div>

            <!-- Unsaved Changes Indicator -->
            <div
                v-if="hasUnsavedChanges"
                class="mb-5 flex items-center gap-2 px-4 py-3 rounded-lg bg-warning/10 border border-warning/30"
            >
                <i class="ki-filled ki-information-2 text-warning text-base"></i>
                <span class="text-sm text-warning font-medium">You have unsaved changes</span>
            </div>

            <!-- Main Settings Card -->
            <div class="card">
                <!-- Tabs Navigation - Improved mobile scrollability -->
                <div class="card-header border-b border-gray-200 dark:border-gray-700 px-0 sm:px-5 py-0">
                    <div class="flex overflow-x-auto scrollbar-none -mb-px">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="switchTab(tab.id)"
                            class="px-4 sm:px-5 py-3.5 sm:py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 shrink-0"
                            :class="currentTab === tab.id
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-primary hover:bg-primary/10 dark:hover:bg-primary/20'"
                        >
                            <i :class="[tab.icon, 'text-base']"></i>
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <form @submit.prevent="submitForm" novalidate>
                    <div class="card-body p-4 sm:p-6">
                        <!-- General Settings Tab -->
                        <div v-show="currentTab === 'general'" class="space-y-4 sm:space-y-5">
                            <div class="mb-2 sm:mb-4">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">General Settings</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure the main financial module settings.</p>
                            </div>

                            <!-- Financial Module Enable/Disable -->
                            <div
                                v-for="(setting, key) in getSettingsByGroup('general')"
                                :key="key"
                                class="group flex items-start sm:items-center justify-between gap-3 p-3 sm:p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-950 dark:group-hover:text-white text-sm sm:text-base transition-colors">{{ getSettingLabel(key) }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 mt-0.5 sm:mt-1 transition-colors">{{ getSettingDescription(key) }}</div>
                                </div>
                                <div class="flex-shrink-0 pt-0.5 sm:pt-0">
                                    <label class="switch">
                                        <input
                                            type="checkbox"
                                            v-model="setting.value"
                                            :disabled="setting.is_system"
                                            @change="clearError(key)"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Journal Settings Tab -->
                        <div v-show="currentTab === 'journal'" class="space-y-4 sm:space-y-5">
                            <div class="mb-2 sm:mb-4">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">Auto-Journal Settings</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure automatic journal entry creation for transactions.</p>
                            </div>

                            <div
                                v-for="(setting, key) in getSettingsByGroup('journal')"
                                :key="key"
                                class="group flex items-start sm:items-center justify-between gap-3 p-3 sm:p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-950 dark:group-hover:text-white text-sm sm:text-base transition-colors">{{ getSettingLabel(key) }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 mt-0.5 sm:mt-1 transition-colors">{{ getSettingDescription(key) }}</div>
                                </div>
                                <div class="flex-shrink-0 pt-0.5 sm:pt-0">
                                    <label class="switch">
                                        <input
                                            type="checkbox"
                                            v-model="setting.value"
                                            :disabled="setting.is_system || (key !== 'auto_journal_enabled' && !form.auto_journal_enabled?.value)"
                                            @change="clearError(key)"
                                        />
                                    </label>
                                </div>
                            </div>

                            <!-- Info notice when auto-journal is disabled -->
                            <div
                                v-if="!form.auto_journal_enabled?.value"
                                class="flex items-start gap-3 p-3 sm:p-4 rounded-lg bg-info/10 border border-info/30"
                            >
                                <i class="ki-filled ki-information text-info text-base sm:text-lg mt-0.5 shrink-0"></i>
                                <div class="min-w-0">
                                    <div class="font-medium text-info text-sm sm:text-base">Auto-Journal is Disabled</div>
                                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Enable the "Enable Auto-Journal" setting above to configure individual transaction types.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Settings Tab -->
                        <div v-show="currentTab === 'tax'" class="space-y-4 sm:space-y-5">
                            <div class="mb-2 sm:mb-4">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">Tax Settings</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure tax calculations and related settings.</p>
                            </div>

                            <div
                                v-for="(setting, key) in getSettingsByGroup('tax')"
                                :key="key"
                                class="group p-3 sm:p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                            >
                                <!-- Boolean settings (toggles) -->
                                <div v-if="setting.type === 'boolean'" class="flex items-start sm:items-center justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-950 dark:group-hover:text-white text-sm sm:text-base transition-colors">{{ getSettingLabel(key) }}</div>
                                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 mt-0.5 sm:mt-1 transition-colors">{{ getSettingDescription(key) }}</div>
                                    </div>
                                    <div class="flex-shrink-0 pt-0.5 sm:pt-0">
                                        <label class="switch">
                                            <input
                                                type="checkbox"
                                                v-model="setting.value"
                                                :disabled="setting.is_system"
                                                @change="clearError(key)"
                                            />
                                        </label>
                                    </div>
                                </div>

                                <!-- Decimal settings (number input) -->
                                <div v-else-if="setting.type === 'decimal'" class="grid grid-cols-1 sm:grid-cols-[1fr_10rem] gap-3 sm:gap-6 sm:items-center">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-950 dark:group-hover:text-white text-sm sm:text-base transition-colors">{{ getSettingLabel(key) }}</div>
                                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 mt-0.5 sm:mt-1 transition-colors">{{ getSettingDescription(key) }}</div>
                                    </div>
                                    <div class="w-full sm:w-40">
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                class="input text-end w-full"
                                                :class="{ 'border-danger': errors[key] }"
                                                v-model.number="setting.value"
                                                :disabled="setting.is_system"
                                                @input="clearError(key)"
                                            />
                                            <span class="btn btn-input">%</span>
                                        </div>
                                        <p v-if="errors[key]" class="text-xs text-danger mt-1">{{ errors[key] }}</p>
                                    </div>
                                </div>

                                <!-- String settings (text input) -->
                                <div v-else-if="setting.type === 'string'" class="grid grid-cols-1 sm:grid-cols-[1fr_16rem] gap-3 sm:gap-6 sm:items-center">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-950 dark:group-hover:text-white text-sm sm:text-base transition-colors">{{ getSettingLabel(key) }}</div>
                                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 mt-0.5 sm:mt-1 transition-colors">{{ getSettingDescription(key) }}</div>
                                    </div>
                                    <div class="w-full sm:w-64">
                                        <input
                                            type="text"
                                            class="input w-full"
                                            :class="{ 'border-danger': errors[key] }"
                                            v-model="setting.value"
                                            :disabled="setting.is_system"
                                            :placeholder="key === 'company_npwp' ? '00.000.000.0-000.000' : ''"
                                            @input="clearError(key)"
                                        />
                                        <p v-if="errors[key]" class="text-xs text-danger mt-1">{{ errors[key] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Default Accounts Tab -->
                        <div v-show="currentTab === 'accounts'" class="space-y-4 sm:space-y-5">
                            <div class="mb-2 sm:mb-4">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">Default Account Mappings</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure default accounts for automatic journal entries.</p>
                            </div>

                            <!-- Account Mappings Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                                <div
                                    v-for="(setting, key) in getSettingsByGroup('accounts')"
                                    :key="key"
                                    class="group p-3 sm:p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                                >
                                    <div class="mb-2 sm:mb-3">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-950 dark:group-hover:text-white text-sm sm:text-base transition-colors">{{ getSettingLabel(key) }}</div>
                                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 mt-0.5 sm:mt-1 transition-colors">{{ getSettingDescription(key) }}</div>
                                    </div>
                                    <SearchableSelect
                                        v-model="setting.value"
                                        :options="accountOptions"
                                        placeholder="Select account..."
                                        :clearable="true"
                                        :disabled="setting.is_system"
                                        @update:modelValue="clearError(key)"
                                    />
                                    <p v-if="errors[key]" class="text-xs text-danger mt-2">{{ errors[key] }}</p>
                                </div>
                            </div>

                            <!-- Empty state if no accounts -->
                            <div
                                v-if="accounts.length === 0"
                                class="flex flex-col items-center justify-center py-8 sm:py-12 text-gray-500"
                            >
                                <i class="ki-filled ki-wallet text-3xl sm:text-4xl mb-3 sm:mb-4 text-gray-300 dark:text-gray-600"></i>
                                <div class="text-base sm:text-lg font-medium text-gray-700 dark:text-gray-300">No Accounts Available</div>
                                <p class="text-xs sm:text-sm text-gray-400 dark:text-gray-500 mt-1 text-center px-4">Please create chart of accounts first.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons - Fixed mobile order (Save first on mobile) -->
                    <div class="card-footer border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-4">
                        <div class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3">
                            <!-- Reset button (appears second on mobile, first on desktop) -->
                            <button
                                type="button"
                                class="btn btn-light w-full sm:w-auto justify-center order-2 sm:order-1"
                                :disabled="isSubmitting || !hasUnsavedChanges"
                                @click="resetForm"
                            >
                                <i class="ki-filled ki-arrows-circle me-2"></i>
                                Reset Changes
                            </button>

                            <!-- Save button (appears first on mobile - more prominent) -->
                            <button
                                type="submit"
                                class="btn btn-primary w-full sm:w-auto min-w-[160px] justify-center order-1 sm:order-2"
                                :disabled="isSubmitting || !hasUnsavedChanges"
                            >
                                <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <i class="ki-filled ki-check"></i>
                                    Save Settings
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Help Card -->
            <div class="card mt-4 sm:mt-5">
                <div class="card-body p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i class="ki-filled ki-question-2 text-primary text-lg sm:text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Need Help?</h4>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                These settings control how the financial module operates. Changes will take effect immediately after saving.
                                For account mappings, ensure you have created the appropriate chart of accounts before configuring defaults.
                            </p>
                            <div class="mt-3 flex flex-col sm:flex-row sm:flex-wrap gap-1.5 sm:gap-2">
                                <span class="badge badge-sm badge-light text-xs whitespace-normal text-left py-1.5">Auto-Journal creates entries automatically</span>
                                <span class="badge badge-sm badge-light text-xs whitespace-normal text-left py-1.5">Tax settings affect invoice calculations</span>
                                <span class="badge badge-sm badge-light text-xs whitespace-normal text-left py-1.5">Account mappings are used in auto-journal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Hide scrollbar for tab navigation while maintaining scroll functionality */
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
</style>
