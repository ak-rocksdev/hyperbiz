<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <!-- Content -->
        
            <!-- Container -->
            <div class="container-fixed">
                <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                    <div class="flex flex-col justify-center gap-2">
                        <h1 class="text-2xl font-semibold leading-none text-gray-900 mb1.5">
                            Account
                        </h1>
                        <div class="flex sm:flex-col lg:flex-row items-center gap-2 text-sm font-medium">
                            <span class="text-gray-800 font-medium">
                                {{ $page.props.auth.user.name }}
                            </span>
                            <a class="text-gray-700 hover:text-primary" :href="`mailto:${$page.props.auth.user.email}`">
                                {{ $page.props.auth.user.email }}
                            </a>
                            <span class="size-0.75 bg-gray-600 rounded-full">
                            </span>
                            <a class="font-semibold btn btn-link link" href="html/demo1/account/members/team-info.html">
                                Personal Info
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Container -->

            <!-- Container -->
            <div class="container-fixed">
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5">
                    <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-profile-circle text-4xl link"></i>
                            </div>
                            <div class="flex flex-col gap-3">
                                <Link class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    :href="route('user.list')">
                                    User Management
                                </Link>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Efficient management of user and roles<br><span class="text-2sm text-emerald-500 font-black leading-5">(roles management is under development)</span>.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-dollar text-4xl link"></i>
                            </div>
                            <div class="flex flex-col gap-3">
                                <Link class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    :href="route('transaction.list')">
                                    Transactions
                                </Link>
                                <span id="description" class="text-2sm text-gray-700 leading-5">
                                    Manage and review all your transactions efficiently.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-user text-4xl link"></i>
                            </div>
                            <div class="flex flex-col gap-3">
                                <Link class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    :href="route('customer.list')">
                                    Customers
                                </Link>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Customer management and information.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-handcart text-4xl link"></i>
                            </div>
                            <div class="flex flex-col gap-3">
                                <Link class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    :href="route('product.list')">
                                    Products
                                </Link>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Product management and information.
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Create card for brand menu -->
                    <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-shop text-4xl link"></i>
                            </div>
                            <div class="flex flex-col gap-3">
                                <Link class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    :href="route('brand.list')">
                                    Brands
                                </Link>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Master Data for Brand Management.
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-cheque text-2xl link">
                                </i>
                                <div class="menu" data-menu="true">
                                    <div class="menu-item" data-menu-item-offset="0, 10px"
                                        data-menu-item-placement="bottom-end"
                                        data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown"
                                        data-menu-item-trigger="click|lg:click">
                                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                        <div class="menu-dropdown menu-default w-full max-w-[200px]"
                                            data-menu-dismiss="true">
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/home/settings-enterprise.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-setting-3">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Settings
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/members/import-members.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-some-files">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Import
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" href="html/demo1/account/activity.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-cloud-change">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Activity
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-dislike">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Report
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <a class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    href="html/demo1/account/billing/basic.html">
                                    Billing & Payments
                                </a>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Simplify payments today with secure, user-friendly transaction processes.
                                </span>
                            </div>
                        </div>
                    </div> -->
                    <!-- <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-dropbox text-2xl link">
                                </i>
                                <div class="menu" data-menu="true">
                                    <div class="menu-item" data-menu-item-offset="0, 10px"
                                        data-menu-item-placement="bottom-end"
                                        data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown"
                                        data-menu-item-trigger="click|lg:click">
                                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                        <div class="menu-dropdown menu-default w-full max-w-[200px]"
                                            data-menu-dismiss="true">
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/home/settings-enterprise.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-setting-3">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Settings
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/members/import-members.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-some-files">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Import
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" href="html/demo1/account/activity.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-cloud-change">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Activity
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-dislike">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Report
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <a class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    href="html/demo1/account/integrations.html">
                                    Integrations
                                </a>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Enhance Workflows with Advanced Integrations.
                                </span>
                            </div>
                        </div>
                    </div> -->
                    <!-- <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-key-square text-2xl link">
                                </i>
                                <div class="menu" data-menu="true">
                                    <div class="menu-item" data-menu-item-offset="0, 10px"
                                        data-menu-item-placement="bottom-end"
                                        data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown"
                                        data-menu-item-trigger="click|lg:click">
                                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                        <div class="menu-dropdown menu-default w-full max-w-[200px]"
                                            data-menu-dismiss="true">
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/home/settings-enterprise.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-setting-3">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Settings
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/members/import-members.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-some-files">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Import
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" href="html/demo1/account/activity.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-cloud-change">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Activity
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-dislike">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Report
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <a class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    href="html/demo1/account/api-keys.html">
                                    API Keys
                                </a>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Secure and manage Your API Keys effectively and efficiently.
                                </span>
                            </div>
                        </div>
                    </div> -->
                    <!-- <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-mouse-square text-2xl link">
                                </i>
                                <div class="menu" data-menu="true">
                                    <div class="menu-item" data-menu-item-offset="0, 10px"
                                        data-menu-item-placement="bottom-end"
                                        data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown"
                                        data-menu-item-trigger="click|lg:click">
                                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                        <div class="menu-dropdown menu-default w-full max-w-[200px]"
                                            data-menu-dismiss="true">
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/home/settings-enterprise.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-setting-3">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Settings
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/members/import-members.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-some-files">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Import
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" href="html/demo1/account/activity.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-cloud-change">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Activity
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-dislike">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Report
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <a class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    href="html/demo1/account/appearance.html">
                                    Appearance
                                </a>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Transforming your online presence with flawless appearance.
                                </span>
                            </div>
                        </div>
                    </div> -->
                    <!-- <div class="card p-5 lg:p-7.5 lg:pt-7">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-2">
                                <i class="ki-filled ki-color-swatch text-2xl link">
                                </i>
                                <div class="menu" data-menu="true">
                                    <div class="menu-item" data-menu-item-offset="0, 10px"
                                        data-menu-item-placement="bottom-end"
                                        data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown"
                                        data-menu-item-trigger="click|lg:click">
                                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                        <div class="menu-dropdown menu-default w-full max-w-[200px]"
                                            data-menu-dismiss="true">
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/home/settings-enterprise.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-setting-3">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Settings
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link"
                                                    href="html/demo1/account/members/import-members.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-some-files">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Import
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" href="html/demo1/account/activity.html">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-cloud-change">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Activity
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="menu-item">
                                                <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                                                    <span class="menu-icon">
                                                        <i class="ki-filled ki-dislike">
                                                        </i>
                                                    </span>
                                                    <span class="menu-title">
                                                        Report
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <a class="text-base font-medium leading-none text-gray-900 hover:text-primary-active"
                                    href="html/demo1/account/invite-a-friend.html">
                                    Brand
                                </a>
                                <span class="text-2sm text-gray-700 leading-5">
                                    Trending brand designs, identities, and logos.
                                </span>
                            </div>
                        </div>
                    </div> -->
                </div>
                <!-- <div class="flex grow justify-center pt-5 lg:pt-7.5">
                    <a class="btn btn-link" href="#">
                        More Account Options
                    </a>
                </div> -->
            </div>
            <!-- End of Container -->

            
        <!-- End of Content -->
    </AppLayout>
</template>
