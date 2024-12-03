<script setup>
    import { ref, onMounted } from 'vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import ApplicationMark from '@/Components/ApplicationMark.vue';
    import Banner from '@/Components/Banner.vue';
    import Sidebar from '@/Components/Metronic/Sidebar.vue';
    import Header from '@/Components/Metronic/Header.vue';
    import Footer from '@/Components/Metronic/Footer.vue';
    import GlobalSearchModal from '@/Components/Metronic/GlobalSearchModal.vue';
    import Dropdown from '@/Components/Dropdown.vue';
    import DropdownLink from '@/Components/DropdownLink.vue';
    import NavLink from '@/Components/NavLink.vue';
    import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

    defineProps({
        title: String,
    });

    const showingNavigationDropdown = ref(false);

    const switchToTeam = (team) => {
        router.put(route('current-team.update'), {
            team_id: team.id,
        }, {
            preserveState: false,
        });
    };

    // Load external scripts on mount
    onMounted(() => {
        const loadScript = (src) => {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.body.appendChild(script);
            });
        };

        const loadScriptsSequentially = async () => {
            try {
                await loadScript('/assets/js/core.bundle.js');
                await loadScript('/assets/vendors/apexcharts/apexcharts.min.js');
                await loadScript('/assets/js/widgets/general.js');
                await loadScript('/assets/js/layouts/demo1.js');
                KTDom.ready(() => {
                    KTComponents.init();
                });
            } catch (err) {
                console.error('Failed to load script:', err);
            }
        };

        loadScriptsSequentially();

        document.documentElement.setAttribute('data-theme', 'true');
        document.documentElement.setAttribute('dir', 'ltr');
        document.documentElement.setAttribute('lang', 'en');
        document.documentElement.setAttribute('data-theme-mode', 'light');

        document.body.classList.add('antialiased', 'flex', 'h-full', 'text-base', 'text-gray-700', '[--tw-page-bg:#fefefe]', '[--tw-page-bg-dark:var(--tw-coal-500)]', 'demo1', 'sidebar-fixed', 'header-fixed', 'bg-[--tw-page-bg]', 'dark:bg-[--tw-page-bg-dark]');

        document.getElementById('app').className = 'h-full w-full';

        const loadStylesheet = (href) => {
            const link = document.createElement('link');
            link.href = href;
            link.rel = 'stylesheet';
            document.head.appendChild(link);
        };

        loadStylesheet('/assets/vendors/keenicons/styles.bundle.css');
        loadStylesheet('/assets/css/styles.css');
        loadStylesheet('/assets/vendors/apexcharts/apexcharts.css');
    });
</script>

<style scoped>
    .channel-stats-bg {
        background-image: url('/assets/media/images/2600x1600/bg-3.png');
    }

    .dark .channel-stats-bg {
        background-image: url('/assets/media/images/2600x1600/bg-3-dark.png');
    }

    .entry-callout-bg {
        background-image: url('/assets/media/images/2600x1600/2.png');
    }

    .dark .entry-callout-bg {
        background-image: url('/assets/media/images/2600x1600/2-dark.png');
    }
</style>

<template>
    <Head :title="title" />

    <Sidebar />

    <main>
        <div class="flex grow">
            <!-- Wrapper -->
            <div class="wrapper flex grow flex-col">
                <main class="grow content pt-5" id="content" role="content">
                    <!-- Header -->
                    <Header />
                    <!-- End of Header -->
                    <div style="padding-bottom: 4.5rem;">
                        <slot />
                    </div>
                    <!-- Footer -->
                    <Footer />
                    <!-- End of Footer -->
                </main>
            </div>
            <!-- End of Wrapper -->
        </div>
    </main>
    
    <GlobalSearchModal />
</template>
