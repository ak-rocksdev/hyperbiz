<script setup>
    import { Head, useForm } from '@inertiajs/vue3';
    import { onMounted } from 'vue';
    import AuthenticationCard from '@/Components/AuthenticationCard.vue';
    import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';

    defineProps({
        status: String,
    });

    const form = useForm({
        email: '',
    });

    const submit = () => {
        form.post(route('password.email'));
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
    <Head title="Forgot Password" />

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">
            <form @submit.prevent="submit" class="card-body flex flex-col gap-5 p-10" id="reset_password_enter_email_form"
                method="post">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900">
                        Your Email
                    </h3>
                    <span class="text-2sm text-gray-700">
                        Enter your email to reset password
                    </span>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Email
                    </label>
                    <input class="input" 
                        placeholder="email@email.com" 
                        id="email"
                        v-model="form.email"
                        type="email"
                        autofocus
                        autocomplete="username" />
                    <InputError class="mt-2" :message="form.errors.email" />
                    <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                        {{ status }}
                    </div>
                </div>
                <button :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="btn btn-primary flex justify-center grow">
                    Continue
                    <i class="ki-filled ki-black-right"></i>
                </button>
                <div class="text-center">
                    <a class="text-2sm link shrink-0" href="/login" >Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <!-- <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

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

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Email Password Reset Link
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard> -->
</template>
