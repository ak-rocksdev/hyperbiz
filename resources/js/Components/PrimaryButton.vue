<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
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
            'btn btn-primary',
            {
                'btn-sm': size === 'sm',
                'btn-lg': size === 'lg',
            }
        ]"
    >
        <slot />
    </button>
</template>
