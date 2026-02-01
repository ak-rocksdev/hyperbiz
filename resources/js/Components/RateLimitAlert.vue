<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    retryAfter: {
        type: Number,
        default: 60
    }
});

const emit = defineEmits(['expire', 'close']);

const countdown = ref(props.retryAfter);
const initialTime = ref(props.retryAfter);
let intervalId = null;

// Progress percentage (100 to 0)
const progress = computed(() => {
    return (countdown.value / initialTime.value) * 100;
});

// SVG circle calculations for the circular progress
const circleRadius = 38;
const circleCircumference = 2 * Math.PI * circleRadius;
const strokeDashoffset = computed(() => {
    return circleCircumference * (1 - progress.value / 100);
});

// Format countdown display
const formattedCountdown = computed(() => {
    const minutes = Math.floor(countdown.value / 60);
    const seconds = countdown.value % 60;
    if (minutes > 0) {
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
    return `${seconds}s`;
});

// Start/stop countdown based on show prop
watch(() => props.show, (newVal) => {
    if (newVal) {
        countdown.value = props.retryAfter;
        initialTime.value = props.retryAfter;
        startCountdown();
    } else {
        stopCountdown();
    }
}, { immediate: true });

// Update if retryAfter changes
watch(() => props.retryAfter, (newVal) => {
    if (props.show) {
        countdown.value = newVal;
        initialTime.value = newVal;
    }
});

function startCountdown() {
    stopCountdown();
    intervalId = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        }
        if (countdown.value <= 0) {
            stopCountdown();
            emit('expire');
        }
    }, 1000);
}

function stopCountdown() {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

function handleClose() {
    emit('close');
}

onUnmounted(() => {
    stopCountdown();
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="opacity-0 -translate-y-full"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-full"
    >
        <div v-if="show" class="rate-limit-alert">
            <!-- Ambient glow effect -->
            <div class="alert-glow"></div>

            <!-- Main container -->
            <div class="alert-container">
                <!-- Left section: Circular countdown -->
                <div class="countdown-section">
                    <div class="countdown-ring">
                        <!-- Background ring -->
                        <svg class="ring-svg" viewBox="0 0 100 100">
                            <circle
                                class="ring-track"
                                cx="50"
                                cy="50"
                                :r="circleRadius"
                                fill="none"
                                stroke-width="6"
                            />
                            <circle
                                class="ring-progress"
                                cx="50"
                                cy="50"
                                :r="circleRadius"
                                fill="none"
                                stroke-width="6"
                                stroke-linecap="round"
                                :stroke-dasharray="circleCircumference"
                                :stroke-dashoffset="strokeDashoffset"
                            />
                        </svg>
                        <!-- Center content -->
                        <div class="ring-center">
                            <i class="ki-filled ki-time countdown-icon"></i>
                            <span class="countdown-number">{{ formattedCountdown }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right section: Content -->
                <div class="content-section">
                    <div class="content-header">
                        <div class="title-row">
                            <i class="ki-filled ki-shield-cross title-icon"></i>
                            <h4 class="alert-title">Too Many Login Attempts</h4>
                        </div>
                        <button @click="handleClose" class="close-btn" aria-label="Close">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>

                    <p class="alert-message">
                        For your security, we've temporarily locked login attempts.
                        Please wait for the timer to complete before trying again.
                    </p>

                    <!-- Linear progress bar (secondary visual) -->
                    <div class="progress-bar-container">
                        <div class="progress-bar-track">
                            <div
                                class="progress-bar-fill"
                                :style="{ width: `${progress}%` }"
                            ></div>
                        </div>
                        <span class="progress-label">{{ Math.round(progress) }}% remaining</span>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.rate-limit-alert {
    position: fixed;
    top: 1rem;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 2rem);
    max-width: 480px;
    z-index: 9999;
    border-radius: 1rem;
    overflow: hidden;
}

/* Ambient glow behind the alert */
.alert-glow {
    position: absolute;
    inset: -2px;
    background: linear-gradient(
        135deg,
        rgba(251, 191, 36, 0.4) 0%,
        rgba(245, 158, 11, 0.3) 50%,
        rgba(217, 119, 6, 0.4) 100%
    );
    filter: blur(20px);
    opacity: 0.6;
    animation: pulse-glow 3s ease-in-out infinite;
    border-radius: 1rem;
}

@keyframes pulse-glow {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.02); }
}

/* Main container */
.alert-container {
    position: relative;
    display: flex;
    gap: 1.25rem;
    padding: 1.25rem;
    background: linear-gradient(
        135deg,
        rgba(255, 251, 235, 0.95) 0%,
        rgba(254, 243, 199, 0.95) 100%
    );
    border: 1px solid rgba(251, 191, 36, 0.4);
    border-radius: 1rem;
    backdrop-filter: blur(10px);
    box-shadow:
        0 4px 6px -1px rgba(251, 191, 36, 0.1),
        0 2px 4px -2px rgba(251, 191, 36, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

/* Countdown section */
.countdown-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.countdown-ring {
    position: relative;
    width: 90px;
    height: 90px;
}

.ring-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.ring-track {
    stroke: rgba(251, 191, 36, 0.2);
}

.ring-progress {
    stroke: url(#amberGradient);
    stroke: #f59e0b;
    transition: stroke-dashoffset 1s linear;
    filter: drop-shadow(0 0 6px rgba(245, 158, 11, 0.5));
}

.ring-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
}

.countdown-icon {
    font-size: 1.25rem;
    color: #d97706;
    animation: pulse-icon 2s ease-in-out infinite;
}

@keyframes pulse-icon {
    0%, 100% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.1); opacity: 1; }
}

.countdown-number {
    font-size: 1.125rem;
    font-weight: 700;
    color: #92400e;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.025em;
}

/* Content section */
.content-section {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.content-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.title-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.title-icon {
    font-size: 1.125rem;
    color: #d97706;
}

.alert-title {
    font-size: 1rem;
    font-weight: 600;
    color: #92400e;
    margin: 0;
    line-height: 1.4;
}

.close-btn {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    border: none;
    background: rgba(217, 119, 6, 0.1);
    color: #b45309;
    cursor: pointer;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: rgba(217, 119, 6, 0.2);
    color: #92400e;
    transform: scale(1.05);
}

.close-btn:active {
    transform: scale(0.95);
}

.alert-message {
    font-size: 0.875rem;
    color: #a16207;
    line-height: 1.5;
    margin: 0;
}

/* Progress bar */
.progress-bar-container {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.progress-bar-track {
    height: 6px;
    background: rgba(251, 191, 36, 0.2);
    border-radius: 999px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(
        90deg,
        #fbbf24 0%,
        #f59e0b 50%,
        #d97706 100%
    );
    border-radius: 999px;
    transition: width 1s linear;
    box-shadow: 0 0 8px rgba(245, 158, 11, 0.4);
}

.progress-label {
    font-size: 0.75rem;
    color: #b45309;
    font-weight: 500;
}

/* Dark mode support */
:root[data-theme-mode="dark"] .alert-container,
.dark .alert-container {
    background: linear-gradient(
        135deg,
        rgba(120, 53, 15, 0.9) 0%,
        rgba(146, 64, 14, 0.85) 100%
    );
    border-color: rgba(251, 191, 36, 0.3);
}

:root[data-theme-mode="dark"] .alert-title,
.dark .alert-title {
    color: #fef3c7;
}

:root[data-theme-mode="dark"] .countdown-number,
.dark .countdown-number {
    color: #fde68a;
}

:root[data-theme-mode="dark"] .alert-message,
.dark .alert-message {
    color: #fcd34d;
}

:root[data-theme-mode="dark"] .progress-label,
.dark .progress-label {
    color: #fbbf24;
}

:root[data-theme-mode="dark"] .countdown-icon,
:root[data-theme-mode="dark"] .title-icon,
.dark .countdown-icon,
.dark .title-icon {
    color: #fbbf24;
}

:root[data-theme-mode="dark"] .close-btn,
.dark .close-btn {
    background: rgba(251, 191, 36, 0.15);
    color: #fcd34d;
}

:root[data-theme-mode="dark"] .close-btn:hover,
.dark .close-btn:hover {
    background: rgba(251, 191, 36, 0.25);
    color: #fef3c7;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .alert-container {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 1rem;
    }

    .countdown-ring {
        width: 80px;
        height: 80px;
    }

    .content-header {
        flex-direction: column;
        align-items: center;
    }

    .close-btn {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
    }

    .progress-bar-container {
        align-items: center;
    }
}
</style>
