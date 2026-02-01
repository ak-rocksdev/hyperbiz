<script setup>
defineProps({
    message: String,
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-danger mt-1">
            {{ message }}
        </p>
    </div>
</template>
