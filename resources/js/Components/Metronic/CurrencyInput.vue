<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: 0
    },
    prefix: {
        type: String,
        default: ''
    },
    suffix: {
        type: String,
        default: ''
    },
    thousandSeparator: {
        type: String,
        default: '.'
    },
    decimalSeparator: {
        type: String,
        default: ','
    },
    precision: {
        type: Number,
        default: 0
    },
    min: {
        type: Number,
        default: 0
    },
    max: {
        type: Number,
        default: null
    },
    placeholder: {
        type: String,
        default: '0'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    inputClass: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const displayValue = ref('');
const inputRef = ref(null);

// Format number with thousand separators
const formatNumber = (value) => {
    if (value === null || value === undefined || value === '') return '';

    let num = parseFloat(value);
    if (isNaN(num)) return '';

    // Handle precision
    if (props.precision > 0) {
        num = num.toFixed(props.precision);
    } else {
        num = Math.round(num).toString();
    }

    // Split integer and decimal parts
    let parts = num.toString().split('.');
    let integerPart = parts[0];
    let decimalPart = parts[1] || '';

    // Add thousand separators
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, props.thousandSeparator);

    // Combine parts
    if (props.precision > 0 && decimalPart) {
        return integerPart + props.decimalSeparator + decimalPart;
    }
    return integerPart;
};

// Parse formatted string back to number
const parseNumber = (value) => {
    if (!value || value === '') return 0;

    // Remove thousand separators and convert decimal separator
    let cleaned = value
        .replace(new RegExp('\\' + props.thousandSeparator, 'g'), '')
        .replace(props.decimalSeparator, '.');

    // Remove any non-numeric characters except decimal point and minus
    cleaned = cleaned.replace(/[^\d.-]/g, '');

    let num = parseFloat(cleaned);
    if (isNaN(num)) return 0;

    // Apply min/max constraints
    if (props.min !== null && num < props.min) num = props.min;
    if (props.max !== null && num > props.max) num = props.max;

    return num;
};

// Handle input
const onInput = (event) => {
    const cursorPosition = event.target.selectionStart;
    const oldLength = displayValue.value.length;

    // Get raw input value
    let rawValue = event.target.value;

    // Remove non-numeric except decimal separator
    rawValue = rawValue.replace(/[^\d.,]/g, '');

    // Parse and format
    const numValue = parseNumber(rawValue);
    displayValue.value = formatNumber(numValue);

    // Emit the numeric value
    emit('update:modelValue', numValue);
    emit('change', numValue);

    // Adjust cursor position after formatting
    const newLength = displayValue.value.length;
    const diff = newLength - oldLength;

    setTimeout(() => {
        if (inputRef.value) {
            const newPosition = Math.max(0, cursorPosition + diff);
            inputRef.value.setSelectionRange(newPosition, newPosition);
        }
    }, 0);
};

// Handle blur - ensure proper formatting
const onBlur = () => {
    const numValue = parseNumber(displayValue.value);
    displayValue.value = formatNumber(numValue);
    emit('update:modelValue', numValue);
};

// Watch for external value changes
watch(() => props.modelValue, (newVal) => {
    const currentNum = parseNumber(displayValue.value);
    const newNum = parseFloat(newVal) || 0;

    // Only update if the values are different to avoid cursor jumping
    if (currentNum !== newNum) {
        displayValue.value = formatNumber(newNum);
    }
}, { immediate: true });

onMounted(() => {
    displayValue.value = formatNumber(props.modelValue);
});
</script>

<template>
    <div class="flex">
        <span
            v-if="prefix"
            class="inline-flex items-center px-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md text-gray-600 text-sm font-medium"
        >
            {{ prefix }}
        </span>
        <input
            ref="inputRef"
            type="text"
            inputmode="numeric"
            :class="[
                'input w-full',
                prefix ? 'rounded-l-none border-l-0' : '',
                suffix ? 'rounded-r-none border-r-0' : '',
                inputClass
            ]"
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            @input="onInput"
            @blur="onBlur"
        />
        <span
            v-if="suffix"
            class="inline-flex items-center px-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-gray-600 text-sm font-medium"
        >
            {{ suffix }}
        </span>
    </div>
</template>
