<script setup>
    import { onMounted, ref } from 'vue';
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import AuthenticationCard from '@/Components/AuthenticationCard.vue';
    import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
    import Checkbox from '@/Components/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import RateLimitAlert from '@/Components/RateLimitAlert.vue';
    import axios from 'axios';

    defineProps({
        canResetPassword: Boolean,
        status: String,
    });

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    // Rate limit state
    const showRateLimitAlert = ref(false);
    const retryAfterSeconds = ref(60);

    const submit = () => {
        // Don't submit if rate limited
        if (showRateLimitAlert.value) return;

        // Use axios directly to catch 429 errors properly
        axios.post(route('login'), {
            email: form.email,
            password: form.password,
            remember: form.remember ? 'on' : '',
        }).then((response) => {
            // Successful login - redirect to intended URL or dashboard
            window.location.href = response.data?.redirect || '/dashboard';
        }).catch((error) => {
            form.reset('password');

            if (error.response?.status === 429) {
                // Rate limit exceeded - show inline alert
                retryAfterSeconds.value = error.response.data?.retry_after || 60;
                showRateLimitAlert.value = true;
            } else if (error.response?.status === 422) {
                // Validation errors - extract first message from each error array
                const errors = error.response.data?.errors || {};
                const formattedErrors = {};
                for (const [key, messages] of Object.entries(errors)) {
                    formattedErrors[key] = Array.isArray(messages) ? messages[0] : messages;
                }
                form.setError(formattedErrors);
            } else {
                // Other errors
                form.setError('email', error.response?.data?.message || 'An error occurred. Please try again.');
            }
        });
    };

    const handleRateLimitExpire = () => {
        showRateLimitAlert.value = false;
    };

    const handleRateLimitClose = () => {
        // Optional: allow closing but keep form disabled until time expires
        // For better UX, we'll just hide the alert but the countdown continues internally
        showRateLimitAlert.value = false;
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
                    console.log('Metronic scripts loaded');
                });
            })
            .catch(err => console.error('Failed to load script:', err));

        // add this line in the <html> tag: data-theme="true" dir="ltr" lang="en" data-theme-mode="light"

        document.documentElement.setAttribute('data-theme', 'true');
        document.documentElement.setAttribute('dir', 'ltr');
        document.documentElement.setAttribute('lang', 'en');
        document.documentElement.setAttribute('data-theme-mode', 'light');

        // load CSS from <link href="/assets/vendors/keenicons/styles.bundle.css" rel="stylesheet"/> and <link href="/assets/css/styles.css" rel="stylesheet"/>
        const link1 = document.createElement('link');
        link1.href = '/assets/vendors/keenicons/styles.bundle.css';
        link1.rel = 'stylesheet';
        document.head.appendChild(link1);

        const link2 = document.createElement('link');
        link2.href = '/assets/css/styles.css';
        link2.rel = 'stylesheet';
        document.head.appendChild(link2);

        // remove current body classes, and replace with "antialiased flex h-full text-base text-gray-700 dark:bg-coal-500"
        document.body.className = 'antialiased flex h-full text-base text-gray-700 dark:bg-coal-500';

        // element with id "app" should have class "h-full w-full"
        document.getElementById('app').className = 'h-full w-full';

        // element with class "page-bg", add style 100vh on height
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
    <Head title="Log in" />
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg h-full">
        <div class="w-full max-w-[400px] px-4">
            <!-- Rate Limit Alert -->
            <RateLimitAlert
                :show="showRateLimitAlert"
                :retryAfter="retryAfterSeconds"
                @expire="handleRateLimitExpire"
                @close="handleRateLimitClose"
            />

            <div class="card w-full">
                <form action="#" class="card-body flex flex-col gap-5 p-10" id="sign_in_form" method="get" @submit.prevent="submit">
                <div class="flex items-center gap-2">
                    <span class="border-t border-gray-200 w-full"></span>
                    <img alt="or" class="size-20 shrink-0" src="/assets/media/app/bkpi_square_logo.png"/>
                    <span class="border-t border-gray-200 w-full"></span>
                </div>
                <div class="text-center mb-2">
                    <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                        Sign in
                    </h3>
                    <div class="flex items-center justify-center font-medium">
                        <span class="text-2sm text-gray-700 me-1.5">
                            Need an account?
                        </span>
                        <Link class="text-2sm link" :href="'/register'">
                            Sign up
                        </Link>
                    </div>
                </div>
                <!-- <div class="grid grid-cols-2 gap-2.5">
                    <a class="btn btn-light btn-sm justify-center" href="#">
                        <img alt="google-logo" class="size-3.5 shrink-0" src="/assets/media/brand-logos/google.svg"/>
                        Use Google
                    </a>
                    <a class="btn btn-light btn-sm justify-center" href="#">
                        <img alt="apple-logo" class="size-3.5 shrink-0" src="/assets/media/brand-logos/apple-black.svg"/>
                        Use Apple
                    </a>
                </div> -->
                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Email
                    </label>
                    <input 
                        class="input"
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="Your Email" />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="form-label font-normal text-gray-900">
                            Password
                        </label>
                        <a class="text-2sm link shrink-0" href="/forgot-password">
                            Forgot Password?
                        </a>
                    </div>
                    <div class="input" data-toggle-password="true">
                        <input 
                            name="user_password" 
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="Enter Password" 
                            autocomplete="current-password"
                            />
                        <button class="btn btn-icon" data-toggle-password-trigger="true" type="button" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                            <i class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <label class="checkbox-group">
                    <input class="checkbox checkbox-sm" name="check" type="checkbox" value="1"/>
                    <span class="checkbox-label">
                    Remember me
                    </span>
                </label>
                <button
                    class="btn btn-primary flex justify-center grow"
                    :class="{ 'opacity-50 cursor-not-allowed': showRateLimitAlert }"
                    :disabled="showRateLimitAlert"
                >
                    <span v-if="showRateLimitAlert">Please Wait...</span>
                    <span v-else>Sign In</span>
                </button>
            </form>
            </div>
        </div>
    </div>

    <!-- <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                    Forgot your password?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard> -->
</template>
