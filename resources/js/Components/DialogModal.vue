<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <!-- Modal Header -->
        <div class="modal-header">
            <h3 class="modal-title">
                <slot name="title" />
            </h3>
            <button
                v-if="closeable"
                type="button"
                class="btn btn-icon btn-sm btn-light"
                @click="close"
            >
                <i class="ki-filled ki-cross text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body text-sm text-gray-600 dark:text-gray-400">
            <slot name="content" />
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer flex justify-end gap-2.5">
            <slot name="footer" />
        </div>
    </Modal>
</template>
