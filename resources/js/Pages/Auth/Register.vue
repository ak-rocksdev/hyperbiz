<script setup>
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import { onMounted } from 'vue';
    import AuthenticationCard from '@/Components/AuthenticationCard.vue';
    import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
    import Checkbox from '@/Components/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';

    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const submit = () => {
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
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
    <Head title="Register" />

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card sm:max-w-[300px] md:max-w-[700px] w-full">
            <form @submit.prevent="submit" class="card-body grid sm:grid-cols-1 md:grid-cols-2 gap-5 p-10" id="sign_up_form">
                <div class="flex items-center gap-2">
                    <span class="border-t border-gray-200 w-full"></span>
                    <img alt="or" class="size-20 shrink-0" src="/assets/media/app/bkpi_square_logo.png"/>
                    <span class="border-t border-gray-200 w-full"></span>
                </div>
                <div class="text-center mb-2.5">
                    <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                        Sign up
                    </h3>
                    <div class="flex items-center justify-center">
                        <span class="text-2sm text-gray-700 me-1.5">
                            Already have an Account ?
                        </span>
                        <Link class="text-2sm link" :href="route('login')">
                            Sign In
                        </Link>
                    </div>
                </div>
                <!-- <div class="grid grid-cols-2 gap-2.5">
                    <a class="btn btn-light btn-sm justify-center" href="#">
                        <img alt="" class="size-3.5 shrink-0" src="/assets/media/brand-logos/google.svg" />
                        Use Google
                    </a>
                    <a class="btn btn-light btn-sm justify-center" href="#">
                        <img alt="" class="size-3.5 shrink-0" src="/assets/media/brand-logos/apple-black.svg" />
                        Use Apple
                    </a>
                </div> -->
                <div class="flex flex-col gap-1">
                    <label class="form-label text-gray-900">
                        Name
                    </label>
                    <input class="input" 
                        id="name"
                        v-model="form.name"
                        type="text"
                        name="name" 
                        placeholder="Your Full Name" />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="form-label text-gray-900">
                        Email
                    </label>
                    <input class="input" 
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="user_email" 
                        placeholder="email@email.com" />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Password
                    </label>
                    <div class="input" data-toggle-password="true">
                        <input name="user_password" 
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Enter Password" />
                        <button class="btn btn-icon" data-toggle-password-trigger="true" type="button">
                            <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                            <i class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Confirm Password
                    </label>
                    <div class="input" data-toggle-password="true">
                        <input name="user_password" 
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Re-enter Password" 
                            type="password" />
                        <button class="btn btn-icon" data-toggle-password-trigger="true" type="button">
                            <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                            <i class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
                <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="flex flex-col gap-1">
                    <label class="checkbox-group">
                        <input class="checkbox checkbox-sm" v-model="form.terms" type="checkbox" />
                        <span class="checkbox-label">
                            I accept
                            <Link class="text-2sm link" :href="'/terms-of-service'">
                                Terms & Conditions
                            </Link>
                        </span>
                    </label>
                    <InputError :message="form.errors.terms" />
                </div>
                <button class="btn btn-primary flex justify-center grow" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Sign up
                </button>
                <div class="flex justify-center mt-4">
                    <Link href="/privacy-policy" class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        Read Privacy Policy
                    </Link>
                </div>
            </form>
        </div>
    </div>

    
</template>
