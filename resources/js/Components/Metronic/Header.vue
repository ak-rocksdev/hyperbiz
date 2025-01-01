<script setup>
    import Topbar from '@/Components/Metronic/Topbar.vue';
    import { usePage } from '@inertiajs/vue3';
    import { computed } from 'vue';

    const page = usePage();

    const companyLogo = computed(() => {
        const logo = page.props.company?.logo;
        return logo ? `/storage/${logo}` : null;
    });
</script>

<template>
    <header class="header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-[--tw-page-bg] dark:bg-[--tw-page-bg-dark]"
        data-sticky="true" data-sticky-class="shadow-sm" data-sticky-name="header" id="header">
        <!-- Container -->
        <div class="container-fixed flex justify-between lg:justify-end items-stretch lg:gap-4" id="header_container">
            <!-- Mobile Logo -->
            <div class="flex lg:hidden items-center -ms-1 gap-2">
                <div class="flex items-center">
                    <button class="btn btn-light btn-clear gap-2 flex items-center" data-drawer-toggle="#sidebar">
                        <i class="ki-filled ki-menu">
                        </i>
                        <span class="font-black text-sm text-gray-800 dark:text-gray-200">
                            Menu
                        </span>
                    </button>
                </div>
            </div>
            <a class="flex items-center shrink-0 lg:hidden" href="#">
                <img class="max-h-[32px] w-auto" :src="companyLogo" />
            </a>
            <!-- End of Mobile Logo -->
            <!--Megamenu Container-->
            <!-- <MegaMenu /> -->
            <!--End of Megamenu Contaoner-->
            <!-- Topbar -->
            <Topbar />
            <!-- End of Topbar -->
        </div>
        <!-- End of Container -->
    </header>
</template>