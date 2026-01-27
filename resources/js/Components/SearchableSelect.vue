<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: null
    },
    options: {
        type: Array,
        required: true
        // Each option: { value: string|number, label: string, sublabel?: string }
    },
    placeholder: {
        type: String,
        default: 'Select...'
    },
    searchPlaceholder: {
        type: String,
        default: 'Search...'
    },
    searchable: {
        type: Boolean,
        default: true
    },
    clearable: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'default', // 'sm', 'default', 'lg'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);
const searchInputRef = ref(null);

// Get selected option
const selectedOption = computed(() => {
    return props.options.find(opt => opt.value === props.modelValue);
});

// Filtered options based on search
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt =>
        opt.label.toLowerCase().includes(query) ||
        (opt.sublabel && opt.sublabel.toLowerCase().includes(query))
    );
});

// Toggle dropdown
const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        setTimeout(() => {
            searchInputRef.value?.focus();
        }, 50);
    }
};

// Select option
const selectOption = (option) => {
    emit('update:modelValue', option.value);
    isOpen.value = false;
    searchQuery.value = '';
};

// Clear selection
const clearSelection = (e) => {
    e.stopPropagation();
    emit('update:modelValue', null);
};

// Close dropdown when clicking outside
const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

// Handle keyboard navigation
const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

// Size classes
const sizeClasses = computed(() => {
    const sizes = {
        sm: 'h-[32px] text-xs',
        default: 'h-[40px] text-sm',
        lg: 'h-[48px] text-base'
    };
    return sizes[props.size] || sizes.default;
});

// Input size classes
const inputSizeClasses = computed(() => {
    const sizes = {
        sm: 'py-1.5 text-xs',
        default: 'py-2 text-sm',
        lg: 'py-2.5 text-base'
    };
    return sizes[props.size] || sizes.default;
});

// Option size classes
const optionSizeClasses = computed(() => {
    const sizes = {
        sm: 'px-3 py-2 text-xs',
        default: 'px-4 py-2.5 text-sm',
        lg: 'px-4 py-3 text-base'
    };
    return sizes[props.size] || sizes.default;
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            class="w-full flex items-center justify-between px-3 border border-gray-300 rounded-md bg-white transition-colors"
            :class="[
                sizeClasses,
                disabled ? 'opacity-60 cursor-not-allowed bg-gray-50' : 'hover:border-gray-400 cursor-pointer',
                isOpen ? 'border-primary ring-1 ring-primary/20' : ''
            ]"
            :disabled="disabled"
        >
            <span :class="selectedOption ? 'text-gray-900' : 'text-gray-400'">
                {{ selectedOption?.label || placeholder }}
            </span>
            <div class="flex items-center gap-1">
                <!-- Clear Button -->
                <span
                    v-if="clearable && selectedOption"
                    @click="clearSelection"
                    class="p-1 hover:bg-gray-100 rounded transition-colors"
                >
                    <i class="ki-outline ki-cross text-gray-400 text-xs"></i>
                </span>
                <!-- Arrow Icon -->
                <i
                    class="ki-filled ki-down text-gray-400 text-xs transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }"
                ></i>
            </div>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
            >
                <!-- Search Input -->
                <div v-if="searchable" class="p-2 border-b border-gray-100">
                    <div class="relative">
                        <i class="ki-filled ki-magnifier text-gray-400 absolute top-1/2 left-2.5 -translate-y-1/2 text-xs"></i>
                        <input
                            ref="searchInputRef"
                            type="text"
                            v-model="searchQuery"
                            :placeholder="searchPlaceholder"
                            class="w-full pl-8 pr-3 border border-gray-200 rounded-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
                            :class="inputSizeClasses"
                        />
                    </div>
                </div>

                <!-- Options List -->
                <div class="max-h-[200px] overflow-y-auto">
                    <!-- No Results -->
                    <div v-if="filteredOptions.length === 0" class="px-4 py-6 text-center text-gray-400 text-xs">
                        <i class="ki-filled ki-search-list text-xl mb-2 block"></i>
                        <p>No results found</p>
                    </div>

                    <!-- Options -->
                    <div
                        v-for="option in filteredOptions"
                        :key="option.value"
                        @click="selectOption(option)"
                        class="flex items-center gap-2 cursor-pointer transition-colors"
                        :class="[
                            optionSizeClasses,
                            option.value === modelValue
                                ? 'bg-primary/5 text-primary'
                                : 'hover:bg-gray-50 text-gray-700'
                        ]"
                    >
                        <!-- Label & Sublabel -->
                        <div class="flex-1 min-w-0">
                            <div class="truncate">{{ option.label }}</div>
                            <div v-if="option.sublabel" class="text-xs text-gray-400 truncate">
                                {{ option.sublabel }}
                            </div>
                        </div>

                        <!-- Check Icon -->
                        <i
                            v-if="option.value === modelValue"
                            class="ki-filled ki-check text-primary text-sm flex-shrink-0"
                        ></i>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
