<script setup>
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <!-- Section Title -->
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <slot name="title" />
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <slot name="description" />
                </p>
            </div>
        </div>

        <!-- Card Content -->
        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="card">
                <div class="card-body">
                    <slot name="content" />
                </div>
            </div>
        </div>
    </div>
</template>
