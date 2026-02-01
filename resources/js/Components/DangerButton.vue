<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    size: {
        type: String,
        default: 'default', // 'sm', 'default', 'lg'
    },
});
</script>

<template>
    <button
        :type="type"
        :class="[
            'btn btn-danger',
            {
                'btn-sm': size === 'sm',
                'btn-lg': size === 'lg',
            }
        ]"
    >
        <slot />
    </button>
</template>
