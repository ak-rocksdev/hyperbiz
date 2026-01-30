<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
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
});

const emit = defineEmits(['close']);

const isLoading = ref(true);
const iframeKey = ref(0);

// Reset loading state when URL changes or modal opens
watch(() => [props.show, props.previewUrl], () => {
    if (props.show) {
        isLoading.value = true;
        iframeKey.value++; // Force iframe reload
    }
});

const handleIframeLoad = () => {
    isLoading.value = false;
};

const closeModal = () => {
    emit('close');
};

const downloadPdf = () => {
    window.open(props.downloadUrl, '_blank');
};

const printPdf = () => {
    // Open preview URL in new window and trigger print
    const printWindow = window.open(props.previewUrl, '_blank');
    if (printWindow) {
        printWindow.onload = () => {
            printWindow.print();
        };
    }
};

const openInNewTab = () => {
    window.open(props.previewUrl, '_blank');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="pdf-modal-overlay" @click.self="closeModal">
            <div class="pdf-modal-container">
                <!-- Modal Header -->
                <div class="pdf-modal-header">
                    <div class="flex items-center gap-3">
                        <div class="pdf-icon-wrapper">
                            <i class="ki-filled ki-document text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="pdf-modal-title">{{ documentTitle }}</h3>
                            <span v-if="documentNumber" class="pdf-modal-subtitle">{{ documentNumber }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Action Buttons -->
                        <button
                            type="button"
                            class="btn btn-sm btn-light"
                            @click="openInNewTab"
                            title="Open in New Tab"
                        >
                            <i class="ki-filled ki-exit-up"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-light"
                            @click="printPdf"
                            title="Print"
                        >
                            <i class="ki-filled ki-printer"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            @click="downloadPdf"
                        >
                            <i class="ki-filled ki-file-down me-1"></i>
                            Download
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-icon btn-light ms-2"
                            @click="closeModal"
                        >
                            <i class="ki-outline ki-cross text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body - PDF Preview -->
                <div class="pdf-modal-body">
                    <!-- Loading Indicator -->
                    <div v-if="isLoading" class="pdf-loading">
                        <div class="pdf-loading-spinner"></div>
                        <span class="pdf-loading-text">Loading PDF...</span>
                    </div>

                    <!-- PDF iFrame -->
                    <iframe
                        :key="iframeKey"
                        :src="previewUrl"
                        class="pdf-iframe"
                        :class="{ 'opacity-0': isLoading }"
                        @load="handleIframeLoad"
                        frameborder="0"
                    ></iframe>
                </div>

                <!-- Modal Footer -->
                <div class="pdf-modal-footer">
                    <div class="text-sm text-gray-500">
                        <i class="ki-filled ki-information-2 me-1"></i>
                        Tip: Use the browser's zoom controls to adjust the view
                    </div>
                    <button type="button" class="btn btn-light" @click="closeModal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.pdf-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.2s ease-out;
}

.pdf-modal-container {
    display: flex;
    flex-direction: column;
    width: 90vw;
    max-width: 1200px;
    height: 90vh;
    background-color: #ffffff;
    border-radius: 0.75rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: slideIn 0.3s ease-out;
    overflow: hidden;
}

.pdf-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background-color: #f9fafb;
    flex-shrink: 0;
}

.pdf-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: rgba(59, 130, 246, 0.1);
    border-radius: 0.5rem;
}

.pdf-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.pdf-modal-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

.pdf-modal-body {
    flex: 1;
    position: relative;
    background-color: #374151;
    overflow: hidden;
}

.pdf-iframe {
    width: 100%;
    height: 100%;
    border: none;
    transition: opacity 0.3s ease;
}

.pdf-loading {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #374151;
    z-index: 10;
}

.pdf-loading-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.pdf-loading-text {
    margin-top: 1rem;
    font-size: 0.875rem;
    color: #9ca3af;
}

.pdf-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
    flex-shrink: 0;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Dark mode support */
:deep(.dark) .pdf-modal-container {
    background-color: #1f2937;
}

:deep(.dark) .pdf-modal-header,
:deep(.dark) .pdf-modal-footer {
    background-color: #111827;
    border-color: #374151;
}

:deep(.dark) .pdf-modal-title {
    color: #f9fafb;
}

:deep(.dark) .pdf-modal-subtitle {
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
    .pdf-modal-container {
        width: 100vw;
        height: 100vh;
        max-width: none;
        border-radius: 0;
    }

    .pdf-modal-header {
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .pdf-modal-footer {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
    }
}
</style>
