<script>
    export default {
        data() {
            return {
                year: new Date().getFullYear(),
            };
        },
    };
</script>

<template>
    <footer class="footer fixed bottom-0 w-full [--tw-page-bg-dark:var(--tw-coal-500)] dark:bg-[--tw-page-bg-dark] bg-white shadow-lg" data-sticky="true" data-sticky-class="shadow-lg" data-sticky-name="footer">
        <!-- Container -->
        <div class="container-fixed" style="margin-left: 0;">
            <div class="flex flex-col md:flex-row justify-center md:justify-between items-center gap-3 py-5">
                <div class="flex order-2 md:order-1 gap-2 font-normal text-2sm">
                    <span class="text-gray-500">
                        {{ year }}©
                    </span>
                    <span class="text-gray-600 hover:text-primary">
                        Enterprise Resource Planning System
                    </span>
                </div>
                <nav class="flex order-1 md:order-2 gap-4 font-normal text-2sm text-gray-600">
                    <!-- <a class="hover:text-primary" href="https://keenthemes.com/metronic/tailwind/docs">
                        Docs
                    </a>
                    <a class="hover:text-primary" href="https://1.envato.market/Vm7VRE">
                        Purchase
                    </a>
                    <a class="hover:text-primary"
                        href="https://keenthemes.com/metronic/tailwind/docs/getting-started/license">
                        FAQ
                    </a>
                    <a class="hover:text-primary" href="https://devs.keenthemes.com">
                        Support
                    </a>
                    <a class="hover:text-primary"
                        href="https://keenthemes.com/metronic/tailwind/docs/getting-started/license">
                        License
                    </a> -->
                </nav>
            </div>
        </div>
        <!-- End of Container -->
    </footer>
</template>