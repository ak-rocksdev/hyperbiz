<script setup>
defineProps({
    value: String,
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <label class="form-label">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
        <span v-if="required" class="text-danger">*</span>
    </label>
</template>
