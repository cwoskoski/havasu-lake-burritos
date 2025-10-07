<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Havasu Lake Burritos">

    <title>@yield('title', 'Havasu Lake Burritos - Weekend Burrito Orders')</title>
    <meta name="description" content="@yield('description', 'Order custom burritos online for weekend pickup. Fresh ingredients, mobile-optimized ordering.')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- PWA and mobile icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom CSS variables for iOS safe areas -->
    <style>
        :root {
            --vh: 1vh;
        }

        .min-h-screen-safe {
            min-height: calc(var(--vh, 1vh) * 100);
        }
    </style>

    @stack('head')
</head>
<body class="font-sans antialiased touch-device safe-area-inset" x-data="mobileHelpers" x-init="init">
    <div class="min-h-screen-safe bg-gradient-to-br from-lake-blue-50 to-desert-sand-50">
        @yield('content')
    </div>

    <!-- Global loading indicator -->
    <div
        x-data="{ show: false }"
        x-show="show"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        style="display: none;"
        id="global-loading"
    >
        <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-6 w-6 text-lake-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-700 font-medium">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Toast notifications container -->
    <div
        id="toast-container"
        class="fixed top-4 left-4 right-4 z-50 space-y-2 pointer-events-none safe-area-inset"
    ></div>

    <!-- Global JavaScript helpers -->
    <script>
        // Global toast notification function
        window.showToast = function(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-lake-blue-600'
            };

            toast.className = `${colors[type] || colors.info} text-white px-4 py-3 rounded-xl shadow-lg transform translate-y-[-10px] opacity-0 transition-all duration-300 pointer-events-auto`;
            toast.textContent = message;

            container.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-y-[-10px]', 'opacity-0');
            }, 10);

            // Remove after duration
            setTimeout(() => {
                toast.classList.add('translate-y-[-10px]', 'opacity-0');
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
            }, duration);
        };

        // Global loading functions
        window.showGlobalLoading = function() {
            const loader = document.getElementById('global-loading');
            if (loader) {
                loader.style.display = 'flex';
                Alpine.evaluate(loader, '$data.show = true');
            }
        };

        window.hideGlobalLoading = function() {
            const loader = document.getElementById('global-loading');
            if (loader) {
                Alpine.evaluate(loader, '$data.show = false');
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 200);
            }
        };

        // Service Worker registration for offline capability
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(() => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed'));
            });
        }

        // Handle install prompt for PWA
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

            // Show install button or banner
            const installBanner = document.getElementById('pwa-install-banner');
            if (installBanner) {
                installBanner.classList.remove('hidden');
            }
        });

        window.installPWA = function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        };

        // Network status monitoring
        function updateNetworkStatus() {
            const isOnline = navigator.onLine;
            if (!isOnline) {
                showToast('You are currently offline', 'warning', 5000);
            }
        }

        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
    </script>

    @stack('scripts')
</body>
</html>