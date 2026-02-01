<script setup>
import { onMounted, ref, nextTick } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const recovery = ref(false);
const codeInput = ref(null);
const recoveryCodeInput = ref(null);

const form = useForm({
    code: '',
    recovery_code: '',
});

const toggleRecovery = async () => {
    recovery.value = !recovery.value;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value?.focus();
        form.code = '';
    } else {
        codeInput.value?.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};

onMounted(() => {
    // Function to load a script dynamically
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });
    }

    // Load each Metronic script
    loadScript('/assets/js/core.bundle.js')
        .then(() => loadScript('/assets/vendors/apexcharts/apexcharts.min.js'))
        .then(() => {
            KTDom.ready(() => {
                KTComponents.init();
            });
        })
        .catch(err => console.error('Failed to load script:', err));

    document.documentElement.setAttribute('data-theme', 'true');
    document.documentElement.setAttribute('dir', 'ltr');
    document.documentElement.setAttribute('lang', 'en');
    document.documentElement.setAttribute('data-theme-mode', 'light');

    const link1 = document.createElement('link');
    link1.href = '/assets/vendors/keenicons/styles.bundle.css';
    link1.rel = 'stylesheet';
    document.head.appendChild(link1);

    const link2 = document.createElement('link');
    link2.href = '/assets/css/styles.css';
    link2.rel = 'stylesheet';
    document.head.appendChild(link2);

    document.body.className = 'antialiased flex h-full text-base text-gray-700 dark:bg-coal-500';
    document.getElementById('app').className = 'h-full w-full';
    document.querySelector('.page-bg').style.height = '100vh';
});
</script>

<style scoped>
.page-bg {
    background-image: url('/assets/media/images/2600x1200/bg-10.png');
}

.dark .page-bg {
    background-image: url('/assets/media/images/2600x1200/bg-10-dark.png');
}
</style>

<template>
    <Head title="Two-Factor Authentication" />
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg h-full">
        <div class="card max-w-[400px] w-full">
            <form class="card-body flex flex-col gap-5 p-10" @submit.prevent="submit">
                <!-- Header -->
                <div class="flex items-center gap-2">
                    <span class="border-t border-gray-200 w-full"></span>
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary-light shrink-0">
                        <i class="ki-filled ki-shield-tick text-3xl text-primary"></i>
                    </div>
                    <span class="border-t border-gray-200 w-full"></span>
                </div>

                <div class="text-center mb-2">
                    <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                        Two-Factor Authentication
                    </h3>
                    <p class="text-sm text-gray-600" v-if="!recovery">
                        Enter the 6-digit code from your authenticator app to continue.
                    </p>
                    <p class="text-sm text-gray-600" v-else>
                        Enter one of your emergency recovery codes to continue.
                    </p>
                </div>

                <!-- Code Input -->
                <div v-if="!recovery" class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Authentication Code
                    </label>
                    <input
                        ref="codeInput"
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        class="input text-center text-xl tracking-[0.5em] font-mono"
                        placeholder="000000"
                        autocomplete="one-time-code"
                        autofocus
                        maxlength="6"
                    />
                    <InputError class="mt-2" :message="form.errors.code" />
                </div>

                <!-- Recovery Code Input -->
                <div v-else class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Recovery Code
                    </label>
                    <input
                        ref="recoveryCodeInput"
                        v-model="form.recovery_code"
                        type="text"
                        class="input font-mono"
                        placeholder="xxxxx-xxxxx"
                        autocomplete="one-time-code"
                    />
                    <InputError class="mt-2" :message="form.errors.recovery_code" />
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="btn btn-primary flex justify-center grow"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <i class="ki-filled ki-entrance-right me-2" v-if="!form.processing"></i>
                    <span v-if="form.processing">Verifying...</span>
                    <span v-else>Verify & Login</span>
                </button>

                <!-- Toggle Recovery -->
                <div class="text-center">
                    <button
                        type="button"
                        class="text-sm text-primary hover:text-primary-active transition-colors"
                        @click.prevent="toggleRecovery"
                    >
                        <template v-if="!recovery">
                            <i class="ki-filled ki-key me-1"></i>
                            Use a recovery code instead
                        </template>
                        <template v-else>
                            <i class="ki-filled ki-phone me-1"></i>
                            Use authenticator code instead
                        </template>
                    </button>
                </div>

                <!-- Help Text -->
                <div class="bg-gray-50 dark:bg-coal-400 rounded-lg p-4 mt-2">
                    <div class="flex items-start gap-3">
                        <i class="ki-filled ki-information-2 text-gray-500 mt-0.5"></i>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <p v-if="!recovery">
                                Open your authenticator app (Google Authenticator, Authy, etc.) and enter the current 6-digit code shown for HyperBiz.
                            </p>
                            <p v-else>
                                Recovery codes were provided when you enabled two-factor authentication. Each code can only be used once.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
