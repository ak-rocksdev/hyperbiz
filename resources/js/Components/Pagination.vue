<template>
    <div class="card-footer flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium w-full justify-between items-center">
        <div class="flex justify-between items-center gap-2">
            Show
            <select class="select select-sm w-16" :value="perPage" @change="changePerPage($event)">
                <option v-for="option in perPageOptions" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>
            per page
        </div>
        <span v-if="totalResults > 0" class="text-slate-500 text-sm ms-4">
            Total: {{ totalResults }} Result{{ totalResults === 1 ? '' : 's' }}
        </span>
        <div class="pagination flex items-center gap-2">
            <!-- Previous button -->
            <button class="btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                <i class="ki-outline ki-black-left"></i>
            </button>

            <!-- Page buttons -->
            <span
                v-for="(page, index) in visiblePages"
                :key="index"
                class="btn"
                :class="{ 'active': page === props.currentPage }"
                @click="goToPage(page)"
            >
                {{ page }}
            </span>


            <!-- Next button -->
            <button class="btn" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
                <i class="ki-outline ki-black-right"></i>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    lastPage: {
        type: Number,
        required: true,
    },
    totalResults: {
        type: Number,
        default: 0,
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
    perPage: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['update:page', 'update:perPage']);

const visiblePages = computed(() => {
    const maxVisible = 5;
    const pages = [];

    if (props.lastPage <= maxVisible) {
        for (let i = 1; i <= props.lastPage; i++) {
            pages.push(i);
        }
    } else {
        if (props.currentPage <= 3) {
            pages.push(1, 2, 3, 4, '...', props.lastPage);
        } else if (props.currentPage > props.lastPage - 3) {
            pages.push(1, '...', props.lastPage - 3, props.lastPage - 2, props.lastPage - 1, props.lastPage);
        } else {
            pages.push(
                1,
                '...',
                props.currentPage - 1,
                props.currentPage,
                props.currentPage + 1,
                '...',
                props.lastPage
            );
        }
    }

    return pages;
});

const goToPage = (page) => {
    if (page) {
        emit('update:page', page);
    }
};

const changePerPage = (event) => {
    emit('update:perPage', event.target.value);
};
</script>
