<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="/assets/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
    @vite('resources/css/app.css')
    <style>
        .error-gradient-primary {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
        }
        .error-gradient-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(249, 115, 22, 0.1) 100%);
        }
        .error-gradient-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
        }
        .error-gradient-info {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
        }
        .dark .error-gradient-primary,
        .dark .error-gradient-warning,
        .dark .error-gradient-danger,
        .dark .error-gradient-info {
            background: transparent;
        }
        .icon-pulse {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="antialiased h-full bg-gray-50 dark:bg-coal-500">
    <!-- Background Pattern -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <!-- Decorative circles -->
        <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-@yield('color', 'primary')/5 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full bg-@yield('color', 'primary')/5 blur-3xl"></div>
        <!-- Grid pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%239C92AC%22%20fill-opacity%3D%220.03%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50 dark:opacity-20"></div>
    </div>

    <div class="relative min-h-full flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-lg">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
                    <img src="/assets/media/app/default-logo.svg" alt="{{ config('app.name') }}" class="h-8 sm:h-10 transition-transform group-hover:scale-105 dark:hidden">
                    <img src="/assets/media/app/default-logo-dark.svg" alt="{{ config('app.name') }}" class="h-8 sm:h-10 transition-transform group-hover:scale-105 hidden dark:block">
                </a>
            </div>

            <!-- Error Card -->
            <div class="card shadow-xl border-0 overflow-hidden">
                <!-- Gradient header bar -->
                <div class="h-1.5 bg-gradient-to-r from-@yield('color', 'primary') via-@yield('color', 'primary')/80 to-@yield('color', 'primary')/60"></div>

                <div class="card-body p-6 sm:p-10 lg:p-12">
                    <!-- Icon with decorative background -->
                    <div class="flex justify-center mb-6 sm:mb-8">
                        <div class="relative">
                            <!-- Outer ring -->
                            <div class="absolute inset-0 rounded-full error-gradient-@yield('color', 'primary') scale-150 icon-pulse"></div>
                            <!-- Icon container -->
                            <div class="relative w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 rounded-full bg-@yield('color', 'primary')-light dark:bg-@yield('color', 'primary')/20 flex items-center justify-center float-animation">
                                <i class="ki-filled @yield('icon') text-5xl sm:text-6xl lg:text-7xl text-@yield('color', 'primary')"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Error Code -->
                    <div class="text-center mb-4">
                        <span class="inline-block text-6xl sm:text-7xl lg:text-8xl font-extrabold bg-gradient-to-br from-@yield('color', 'primary') to-@yield('color', 'primary')/70 bg-clip-text text-transparent">
                            @yield('code')
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 class="text-xl sm:text-2xl lg:text-2xl font-bold text-gray-900 dark:text-white text-center mb-3">
                        @yield('title')
                    </h1>

                    <!-- Message -->
                    <p class="text-gray-600 dark:text-gray-400 text-center text-sm sm:text-base mb-8 max-w-sm mx-auto leading-relaxed">
                        @yield('message')
                    </p>

                    <!-- Additional content slot -->
                    @yield('additional_content')

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="{{ url('/') }}" class="btn btn-@yield('color', 'primary') px-6 py-2.5 shadow-sm hover:shadow-md transition-shadow">
                            <i class="ki-filled ki-home-2 me-2"></i>
                            Go to Homepage
                        </a>
                        <button onclick="history.back()" class="btn btn-light px-6 py-2.5 hover:bg-gray-100 dark:hover:bg-coal-400 transition-colors">
                            <i class="ki-filled ki-arrow-left me-2"></i>
                            Go Back
                        </button>
                    </div>
                </div>
            </div>

            <!-- Help text -->
            <div class="text-center mt-6 space-y-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Need help? <a href="mailto:support@hyperbiz.com" class="text-@yield('color', 'primary') hover:underline font-medium">Contact Support</a>
                </p>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-xs text-gray-400 dark:text-gray-500">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        // Theme handling - check localStorage and system preference
        (function() {
            const defaultThemeMode = 'light';
            let themeMode = localStorage.getItem('theme') || defaultThemeMode;
            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.classList.add(themeMode);
        })();
    </script>
</body>
</html>
