<script setup>
import { ref } from 'vue';
import PdfPreviewModal from './PdfPreviewModal.vue';

const props = defineProps({
    previewUrl: {
        type: String,
        required: true,
    },
    downloadUrl: {
        type: String,
        required: true,
    },
    documentTitle: {
        type: String,
        default: 'Document Preview',
    },
    documentNumber: {
        type: String,
        default: '',
    },
    // Style variants
    variant: {
        type: String,
        default: 'default', // 'default', 'compact', 'icon-only'
    },
    size: {
        type: String,
        default: 'sm', // 'xs', 'sm', 'default'
    },
});

const showPreviewModal = ref(false);

const openPreview = () => {
    showPreviewModal.value = true;
};

const closePreview = () => {
    showPreviewModal.value = false;
};

const downloadPdf = () => {
    window.open(props.downloadUrl, '_blank');
};
</script>

<template>
    <div class="pdf-action-buttons">
        <!-- Default Variant: Preview + Download buttons -->
        <template v-if="variant === 'default'">
            <button
                type="button"
                :class="['btn', `btn-${size}`, 'btn-light']"
                @click="openPreview"
                title="Preview PDF"
            >
                <i class="ki-filled ki-eye me-1"></i>
                Preview
            </button>
            <button
                type="button"
                :class="['btn', `btn-${size}`, 'btn-primary']"
                @click="downloadPdf"
                title="Download PDF"
            >
                <i class="ki-filled ki-file-down me-1"></i>
                PDF
            </button>
        </template>

        <!-- Compact Variant: Single dropdown with both options -->
        <template v-else-if="variant === 'compact'">
            <div class="dropdown" data-dropdown="true" data-dropdown-trigger="click">
                <button
                    type="button"
                    :class="['btn', `btn-${size}`, 'btn-light', 'dropdown-toggle']"
                    data-dropdown-toggle="#pdf-dropdown"
                >
                    <i class="ki-filled ki-document me-1"></i>
                    PDF
                    <i class="ki-filled ki-down text-xs ms-1"></i>
                </button>
                <div class="dropdown-content w-36" id="pdf-dropdown">
                    <div class="menu menu-default">
                        <div class="menu-item">
                            <button class="menu-link" @click="openPreview">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-eye"></i>
                                </span>
                                <span class="menu-title">Preview</span>
                            </button>
                        </div>
                        <div class="menu-item">
                            <button class="menu-link" @click="downloadPdf">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-file-down"></i>
                                </span>
                                <span class="menu-title">Download</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Icon Only Variant -->
        <template v-else-if="variant === 'icon-only'">
            <button
                type="button"
                :class="['btn', 'btn-icon', `btn-${size}`, 'btn-light']"
                @click="openPreview"
                title="Preview PDF"
            >
                <i class="ki-filled ki-eye"></i>
            </button>
            <button
                type="button"
                :class="['btn', 'btn-icon', `btn-${size}`, 'btn-primary']"
                @click="downloadPdf"
                title="Download PDF"
            >
                <i class="ki-filled ki-file-down"></i>
            </button>
        </template>

        <!-- PDF Preview Modal -->
        <PdfPreviewModal
            :show="showPreviewModal"
            :preview-url="previewUrl"
            :download-url="downloadUrl"
            :document-title="documentTitle"
            :document-number="documentNumber"
            @close="closePreview"
        />
    </div>
</template>

<style scoped>
.pdf-action-buttons {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
</style>
