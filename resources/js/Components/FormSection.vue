<script setup>
import { computed, useSlots } from 'vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
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
            <form @submit.prevent="$emit('submitted')">
                <div class="card">
                    <div class="card-body">
                        <div class="grid grid-cols-6 gap-6">
                            <slot name="form" />
                        </div>
                    </div>

                    <div v-if="hasActions" class="card-footer flex items-center justify-end gap-2.5">
                        <slot name="actions" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
