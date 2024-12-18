<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Metronic -->
        <!-- <meta content="follow, index" name="robots"/> -->

        <!-- no index on search engine -->
        <meta content="noindex" name="robots"/>
        <!-- <link href="https://127.0.0.1:8001/metronic-tailwind-html/demo1/index.html" rel="canonical"/> -->
        <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
        <meta content="BKPI - Berkah Khidmah Pangan Indonesia" name="description"/>
        <meta content="@ak_rocks" name="twitter:site"/>
        <meta content="@ak_rocks" name="twitter:creator"/>
        <meta content="summary_large_image" name="twitter:card"/>
        <meta content="BKPI " name="twitter:title"/>
        <meta content="" name="twitter:description"/>
        <meta content="/assets/media/app/og-image.png" name="twitter:image"/>
        <!-- <meta content="https://127.0.0.1:8001/metronic-tailwind-html/demo1/index.html" property="og:url"/> -->
        <meta content="en_US" property="og:locale"/>
        <meta content="website" property="og:type"/>
        <meta content="BKPI" property="og:site_name"/>
        <meta content="BKPI - Berkah Khidmah Pangan Indonesia " property="og:title"/>
        <meta content="" property="og:description"/>
        <meta content="/assets/media/app/og-image.png" property="og:image"/>
        <link href="/assets/media/app/apple-touch-icon.png" rel="apple-touch-icon" sizes="180x180"/>
        <link href="/assets/media/app/favicon-32x32.png" rel="icon" sizes="32x32" type="image/png"/>
        <link href="/assets/media/app/favicon-16x16.png" rel="icon" sizes="16x16" type="image/png"/>
        <link href="/assets/media/app/favicon.ico" rel="shortcut icon"/>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
        <link href="/assets/vendors/apexcharts/apexcharts.css" rel="stylesheet"/>
        <link href="/assets/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
        <!-- <link href="/assets/css/styles.css" rel="stylesheet"/> -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @vite('resources/css/app.css')
        @inertiaHead
    </head>
    <body class="antialiased flex h-full text-base text-gray-700 demo1 sidebar-fixed header-fixed bg-[--tw-page-bg]">
        <script>
            const defaultThemeMode = 'light'; // light|dark|system
            let themeMode;
            
            if ( document.documentElement ) {
                if ( localStorage.getItem('theme')) {
                        themeMode = localStorage.getItem('theme');
                        
                } else if ( document.documentElement.hasAttribute('data-theme-mode')) {
                    themeMode = document.documentElement.getAttribute('data-theme-mode');
                } else {
                    themeMode = defaultThemeMode;
                }

                if (themeMode === 'system') {
                    themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }

                document.documentElement.classList.add(themeMode);
            }
        </script>
        
        @inertia
    </body>
</html>
