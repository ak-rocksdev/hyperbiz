<script setup>
    import { ref, computed, watch, watchEffect } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';

    // set reactive variable to check the dark mode
    const darkMode = ref(false);

    // watch for changes in the data-theme-mode attribute
    watchEffect(() => {
        const themeMode = document.documentElement.getAttribute('data-theme-mode');
        darkMode.value = themeMode === 'dark';
    });

    // Access the Inertia page object
    const page = usePage();

    // Access the current URL from the `page` object
    const currentPath = computed(() => page.url);

    // Function to check if a menu item is active
    const isActive = (path) => {
        if (path.endsWith('*')) {
            // Handle wildcard matching
            const basePath = path.slice(0, -1); // Remove the '*' character
            return currentPath.value.startsWith(basePath);
        }
        return currentPath.value === path;
    };

    // Get the company logo from the props
    const companyLogo = computed(() => {
        const logo = page.props.company?.logo;
        return logo ? `/storage/${logo}` : null;
    });
</script>
<template>
    <!-- Sidebar -->
    <div class="sidebar dark:bg-coal-600 bg-light border-e border-e-gray-200 dark:border-e-coal-100 fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0"
        data-drawer="true" data-drawer-class="drawer drawer-start top-0 bottom-0" data-drawer-enable="true|lg:false"
        id="sidebar">
        <div class="sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0"
            id="sidebar_header">
            <a v-if="!darkMode" href="/dashboard">
                <img v-if="companyLogo" :src="companyLogo" class="default-logo min-h-[22px] h-auto max-h-[32px] max-w-[150px]" />
                <img v-else class="default-logo min-h-[22px] max-w-[150px]" src="/assets/media/app/logo.png" />
                <img class="small-logo min-h-[22px] max-w-[35px]" src="/assets/media/app/bkpi_square_logo.png" />
            </a>
            <a v-else href="/dashboard">
                <img v-if="companyLogo" :src="companyLogo" class="default-logo min-h-[22px] max-w-[150px]" />
                <img v-else class="default-logo min-h-[22px] max-w-[150px]" src="/assets/media/app/logo.png" />
                <img class="small-logo min-h-[22px] max-w-[35px]" src="/assets/media/app/bkpi_square_logo.png" />
            </a>
            <button
                class="btn btn-icon btn-icon-md size-[30px] rounded-lg border border-gray-200 dark:border-gray-300 bg-light text-gray-500 hover:text-gray-700 toggle absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
                data-toggle="body" data-toggle-class="sidebar-collapse" id="sidebar_toggle">
                <i
                    class="ki-filled ki-black-left-line toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:toggle-active:rotate-0">
                </i>
            </button>
        </div>
        <div class="sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
            <div class="scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3" data-scrollable="true"
                data-scrollable-dependencies="#sidebar_header" data-scrollable-height="auto"
                data-scrollable-offset="0px" data-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
                <!-- Sidebar Menu -->
                <div class="menu flex flex-col grow gap-0.5" data-menu="true" data-menu-accordion-expand-all="false"
                    id="sidebar_menu">
                    <Link :class="['menu-item', isActive('/dashboard') ? 'active' : '']" :href="isActive('/dashboard') ? '#' : route('dashboard')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-element-11 text-lg">
                                </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Dashboards
                            </span>
                        </div>
                    </Link>
                    <div class="menu-item pt-2.25 pb-px">
                        <span class="menu-heading uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]">
                            Menu
                        </span>
                    </div>
                    <Link :class="['menu-item', isActive('/user*') ? 'active' : '']" :href="isActive('/user/list') ? '#' : route('user.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg">
                                </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                User Management
                            </span>
                        </div>
                    </Link>
                    <!-- Client -->
                    <Link :class="['menu-item', isActive('/client*') ? 'active' : '']" :href="isActive('/client/list') ? '#' : route('client.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-user text-lg">
                                </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Client
                            </span>
                        </div>
                    </Link>
                    <Link :class="['menu-item', isActive('/transaction*') ? 'active' : '']" :href="isActive('/transaction/list') ? '#' : route('transaction.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-dollar text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Transactions
                            </span>
                        </div>
                    </Link>
                    <Link :class="['menu-item', isActive('/brand*') ? 'active' : '']" :href="isActive('/brand/list') ? '#' : route('brand.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-shop text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Brands
                            </span>
                        </div>
                    </Link>
                    
                    <Link :class="['menu-item', isActive('/products*') ? 'active' : '']" :href="isActive('/products/list') ? '#' : route('product.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-handcart text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Products
                            </span>
                        </div>
                    </Link>
                    
                    <Link :class="['menu-item', isActive('/product-category*') ? 'active' : '']" :href="isActive('/product-category/list') ? '#' : route('product-category.list')">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-folder text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Product Categories
                            </span>
                        </div>
                    </Link>
                    <Link :class="['menu-item', isActive('/logs*') ? 'active' : '']" :href="isActive('/logs') ? '#' : '/logs'">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-message-text text-lg"></i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                System Logs
                            </span>
                        </div>
                    </Link>
                </div>
                <!-- End of Sidebar Menu -->
            </div>
        </div>
    </div>
    <!-- End of Sidebar -->
</template>