<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Calendar } from 'vanilla-calendar-pro';
import 'vanilla-calendar-pro/styles/layout.css';

const props = defineProps({
    modelValue: {
        type: [String, null],
        default: null
    },
    placeholder: {
        type: String,
        default: 'Select date'
    },
    format: {
        type: String,
        default: 'YYYY-MM-DD' // Output format for v-model
    },
    displayFormat: {
        type: String,
        default: 'DD MMM YYYY' // Display format in input
    },
    mode: {
        type: String,
        default: 'single', // 'single', 'multiple', 'range'
    },
    enableTime: {
        type: Boolean,
        default: false
    },
    timeMode: {
        type: Number,
        default: 24 // 12 or 24
    },
    minDate: {
        type: String,
        default: null
    },
    maxDate: {
        type: String,
        default: null
    },
    disabledDates: {
        type: Array,
        default: () => []
    },
    disabled: {
        type: Boolean,
        default: false
    },
    clearable: {
        type: Boolean,
        default: true
    },
    size: {
        type: String,
        default: 'default' // 'sm', 'default', 'lg'
    },
    position: {
        type: String,
        default: 'left' // 'left', 'center', 'right'
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const inputRef = ref(null);
const containerRef = ref(null);
const calendarInstance = ref(null);
const displayValue = ref('');
const uid = ref(`datepicker-${Math.random().toString(36).substr(2, 9)}`);

// Size classes
const sizeClasses = computed(() => {
    const sizes = {
        sm: 'h-[32px] text-xs',
        default: 'h-[40px] text-sm',
        lg: 'h-[48px] text-base'
    };
    return sizes[props.size] || sizes.default;
});

// Format date for display
const formatDateForDisplay = (dateStr, time = null) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;

    const year = date.getFullYear();
    const month = date.getMonth();
    const day = date.getDate();

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const monthNamesShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const tokens = {
        'YYYY': String(year),
        'YY': String(year).slice(-2),
        'MMMM': monthNames[month],
        'MMM': monthNamesShort[month],
        'MM': String(month + 1).padStart(2, '0'),
        'M': String(month + 1),
        'DD': String(day).padStart(2, '0'),
        'D': String(day),
    };

    let result = props.displayFormat;
    const sortedTokens = Object.keys(tokens).sort((a, b) => b.length - a.length);
    for (const token of sortedTokens) {
        result = result.replace(new RegExp(token, 'g'), tokens[token]);
    }

    if (time && props.enableTime) {
        result += ' ' + formatTime(time);
    }

    return result;
};

// Format time
const formatTime = (time) => {
    if (!time) return '';

    let hours, minutes;
    if (typeof time === 'string') {
        if (/AM|PM/i.test(time)) return time;
        const parts = time.split(':');
        hours = parseInt(parts[0], 10);
        minutes = parseInt(parts[1] || '0', 10);
    } else if (typeof time === 'object' && time.hours !== undefined) {
        hours = time.hours;
        minutes = time.minutes || 0;
    } else {
        return '';
    }

    if (isNaN(hours) || isNaN(minutes)) return '';

    if (props.timeMode === 12) {
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours === 0 ? 12 : hours > 12 ? hours - 12 : hours;
        return `${displayHours}:${String(minutes).padStart(2, '0')} ${period}`;
    }
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
};

// Initialize calendar
const initCalendar = () => {
    if (!inputRef.value) return;

    const options = {
        inputMode: true,
        positionToInput: props.position,
        selectedTheme: 'light',
    };

    // Selection mode
    if (props.mode === 'range') {
        options.selectionDatesMode = 'multiple-ranged';
    } else if (props.mode === 'multiple') {
        options.selectionDatesMode = 'multiple';
    }

    // Time mode
    if (props.enableTime) {
        options.selectionTimeMode = props.timeMode;
    }

    // Date constraints
    if (props.minDate) {
        options.dateMin = props.minDate;
    }
    if (props.maxDate) {
        options.dateMax = props.maxDate;
    }
    if (props.disabledDates.length > 0) {
        options.disableDates = props.disabledDates;
    }

    // Set initial selected date
    if (props.modelValue) {
        if (props.mode === 'range' && props.modelValue.includes(' - ')) {
            const [start, end] = props.modelValue.split(' - ');
            options.selectedDates = [start, end];
        } else {
            options.selectedDates = [props.modelValue];
        }
        updateDisplayValue(props.modelValue);
    }

    // Change handler
    options.onChangeToInput = (calendar) => {
        const dates = calendar.context.selectedDates;
        const selectedTime = calendar.context.selectedTime;

        if (dates.length === 0) {
            displayValue.value = '';
            emit('update:modelValue', null);
            emit('change', null);
            return;
        }

        let outputValue;
        let displayVal;

        if (props.mode === 'range') {
            // Range mode - wait for 2 dates before closing
            if (dates.length >= 2) {
                outputValue = `${dates[0]} - ${dates[dates.length - 1]}`;
                displayVal = `${formatDateForDisplay(dates[0])} - ${formatDateForDisplay(dates[dates.length - 1])}`;

                // Auto-close on range complete (if no time mode)
                if (!props.enableTime) {
                    calendar.hide();
                }

                displayValue.value = displayVal;
                emit('update:modelValue', outputValue);
                emit('change', outputValue);
            } else {
                // Only 1 date selected in range mode - show partial selection, don't close
                displayValue.value = `${formatDateForDisplay(dates[0])} - ...`;
            }
            return; // Exit early for range mode
        } else if (props.mode === 'multiple') {
            outputValue = dates.join(', ');
            displayVal = dates.map(d => formatDateForDisplay(d, selectedTime)).join(', ');
        } else {
            outputValue = dates[0];
            if (selectedTime && props.enableTime) {
                outputValue += ' ' + formatTime(selectedTime);
            }
            displayVal = formatDateForDisplay(dates[0], selectedTime);

            // Auto-close for single date (if no time mode)
            if (!props.enableTime) {
                calendar.hide();
            }
        }

        displayValue.value = displayVal;
        emit('update:modelValue', outputValue);
        emit('change', outputValue);
    };

    calendarInstance.value = new Calendar(`#${uid.value}`, options);
    calendarInstance.value.init();
};

// Update display value from model value
const updateDisplayValue = (value) => {
    if (!value) {
        displayValue.value = '';
        return;
    }

    if (props.mode === 'range' && value.includes(' - ')) {
        const [start, end] = value.split(' - ');
        displayValue.value = `${formatDateForDisplay(start)} - ${formatDateForDisplay(end)}`;
    } else if (props.mode === 'multiple' && value.includes(', ')) {
        const dates = value.split(', ');
        displayValue.value = dates.map(d => formatDateForDisplay(d)).join(', ');
    } else {
        // Check if value includes time
        const parts = value.split(' ');
        if (parts.length > 1 && props.enableTime) {
            displayValue.value = formatDateForDisplay(parts[0]) + ' ' + parts.slice(1).join(' ');
        } else {
            displayValue.value = formatDateForDisplay(value);
        }
    }
};

// Clear selection
const clearSelection = (e) => {
    e.stopPropagation();
    displayValue.value = '';
    emit('update:modelValue', null);
    emit('change', null);

    if (calendarInstance.value) {
        calendarInstance.value.update({ dates: true });
    }
};

// Watch for external model value changes
watch(() => props.modelValue, (newValue) => {
    updateDisplayValue(newValue);

    if (calendarInstance.value) {
        if (newValue) {
            let dates;
            if (props.mode === 'range' && newValue.includes(' - ')) {
                const [start, end] = newValue.split(' - ');
                dates = [start, end];
            } else if (props.mode === 'multiple' && newValue.includes(', ')) {
                dates = newValue.split(', ');
            } else {
                dates = [newValue.split(' ')[0]]; // Remove time portion for calendar
            }
            calendarInstance.value.update({ selectedDates: dates });
        } else {
            calendarInstance.value.update({ dates: true });
        }
    }
}, { immediate: false });

onMounted(() => {
    initCalendar();
});

onUnmounted(() => {
    if (calendarInstance.value) {
        calendarInstance.value = null;
    }
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <div
            class="kt-input flex items-center gap-2 px-3 border border-gray-300 rounded-md bg-white transition-colors"
            :class="[
                sizeClasses,
                disabled ? 'opacity-60 cursor-not-allowed bg-gray-50' : 'hover:border-gray-400 cursor-pointer',
            ]"
        >
            <i class="ki-filled ki-calendar text-gray-400"></i>
            <input
                :id="uid"
                ref="inputRef"
                type="text"
                :value="displayValue"
                :placeholder="placeholder"
                :disabled="disabled"
                readonly
                class="grow bg-transparent border-none outline-none cursor-pointer text-gray-900 placeholder-gray-400"
                :class="{ 'cursor-not-allowed': disabled }"
            />
            <div class="flex items-center gap-1">
                <!-- Clear Button -->
                <span
                    v-if="clearable && displayValue"
                    @click="clearSelection"
                    class="p-1 hover:bg-gray-100 rounded transition-colors"
                >
                    <i class="ki-outline ki-cross text-gray-400 text-xs"></i>
                </span>
            </div>
        </div>
    </div>
</template>

<style>
/* Vanilla Calendar Pro - Metronic Theme Customization */
:root {
    --vc-calendar-bg: #ffffff;
    --vc-calendar-text: #1f2937;
    --vc-calendar-border: #e5e7eb;
    --vc-calendar-radius: 0.5rem;
    --vc-focus-color: #3b82f6;
    --vc-header-text: #1f2937;
    --vc-header-text-hover: #6b7280;
    --vc-weekday-text: #6b7280;
    --vc-date-text: #1f2937;
    --vc-date-bg-hover: #f3f4f6;
    --vc-today-bg: #eff6ff;
    --vc-today-text: #3b82f6;
    --vc-selected-bg: #3b82f6;
    --vc-selected-text: #ffffff;
    --vc-range-bg: #dbeafe;
    --vc-range-text: #3b82f6;
    --vc-disabled-text: #d1d5db;
    --vc-outside-text: #9ca3af;
}

/* Calendar Container */
[data-vc-theme=light].vc[data-vc-input] {
    background-color: var(--vc-calendar-bg);
    border: 1px solid var(--vc-calendar-border);
    border-radius: var(--vc-calendar-radius);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 100;
    padding: 1rem;
}

/* Header */
[data-vc-theme=light] .vc-header__content,
[data-vc-theme=light] .vc-month,
[data-vc-theme=light] .vc-year {
    color: var(--vc-header-text);
    font-weight: 600;
    font-size: 0.875rem;
}

[data-vc-theme=light] .vc-month:hover,
[data-vc-theme=light] .vc-year:hover {
    color: var(--vc-header-text-hover);
}

/* Arrow Navigation */
[data-vc-theme=light] .vc-arrow {
    opacity: 0.6;
    transition: opacity 0.2s;
}

[data-vc-theme=light] .vc-arrow:hover {
    opacity: 1;
}

/* Week Days */
[data-vc-theme=light] .vc-week__day {
    color: var(--vc-weekday-text);
    font-weight: 600;
    font-size: 0.75rem;
}

/* Date Cells */
[data-vc-theme=light] .vc-date__btn {
    color: var(--vc-date-text);
    font-size: 0.875rem;
    border-radius: 0.375rem;
    transition: background-color 0.15s, color 0.15s;
}

[data-vc-theme=light] .vc-date__btn:hover {
    background-color: var(--vc-date-bg-hover);
}

/* Today */
[data-vc-theme=light] .vc-date[data-vc-date-today] .vc-date__btn {
    background-color: var(--vc-today-bg);
    color: var(--vc-today-text);
    font-weight: 600;
}

/* Selected */
[data-vc-theme=light] .vc-date[data-vc-date-selected] .vc-date__btn {
    background-color: var(--vc-selected-bg);
    color: var(--vc-selected-text);
}

[data-vc-theme=light] .vc-date[data-vc-date-selected] .vc-date__btn:hover {
    background-color: var(--vc-selected-bg);
    color: var(--vc-selected-text);
}

/* Range Middle */
[data-vc-theme=light] .vc-date[data-vc-date-selected=middle] .vc-date__btn {
    background-color: var(--vc-range-bg);
    color: var(--vc-range-text);
    border-radius: 0;
}

/* Range First */
[data-vc-theme=light] .vc-date[data-vc-date-selected=first] .vc-date__btn {
    border-radius: 0.375rem 0 0 0.375rem;
}

/* Range Last */
[data-vc-theme=light] .vc-date[data-vc-date-selected=last] .vc-date__btn {
    border-radius: 0 0.375rem 0.375rem 0;
}

/* Disabled */
[data-vc-theme=light] .vc-date[data-vc-date-disabled] .vc-date__btn {
    color: var(--vc-disabled-text);
    cursor: not-allowed;
}

/* Outside Month */
[data-vc-theme=light] .vc-date[data-vc-date-month=next] .vc-date__btn,
[data-vc-theme=light] .vc-date[data-vc-date-month=prev] .vc-date__btn {
    color: var(--vc-outside-text);
}

/* Month/Year Picker */
[data-vc-theme=light] .vc-months__month,
[data-vc-theme=light] .vc-years__year {
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: background-color 0.15s;
}

[data-vc-theme=light] .vc-months__month:hover,
[data-vc-theme=light] .vc-years__year:hover {
    background-color: var(--vc-date-bg-hover);
}

[data-vc-theme=light] .vc-months__month[data-vc-months-month-selected],
[data-vc-theme=light] .vc-years__year[data-vc-years-year-selected] {
    background-color: var(--vc-selected-bg);
    color: var(--vc-selected-text);
}

/* Time Picker */
[data-vc-theme=light] .vc-time {
    border-top: 1px solid var(--vc-calendar-border);
    margin-top: 0.75rem;
    padding-top: 0.75rem;
}

[data-vc-theme=light] .vc-time__hour input,
[data-vc-theme=light] .vc-time__minute input {
    font-size: 0.875rem;
    font-weight: 500;
    background-color: #f3f4f6;
    border-radius: 0.25rem;
}

[data-vc-theme=light] .vc-time__hour input:focus,
[data-vc-theme=light] .vc-time__minute input:focus {
    background-color: #e5e7eb;
    outline: none;
}

[data-vc-theme=light] .vc-time__keeping {
    font-size: 0.75rem;
    color: #6b7280;
}
</style>
