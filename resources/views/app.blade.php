<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
        <title inertia>{{ config('app.name', 'LaundryHub') }}</title>

        <!-- Favicon & PWA Icons -->
        <link rel="icon" type="image/png" href="/favicon.png">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#0284c7">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="LaundryHub">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Theme Initialization -->
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Scripts -->
        <script>
            window.PUSHER_APP_KEY = "{{ config('broadcasting.connections.pusher.key') ?: env('PUSHER_APP_KEY') }}";
            window.PUSHER_APP_CLUSTER = "{{ config('broadcasting.connections.pusher.options.cluster') ?: env('PUSHER_APP_CLUSTER', 'mt1') }}";
            window.VAPID_PUBLIC_KEY = "{{ config('webpush.vapid.public_key') ?: env('VAPID_PUBLIC_KEY') }}";
        </script>
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-slate-950 dark:text-slate-100 min-h-screen">
        @inertia

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(err => {
                        console.log('SW registration failed: ', err);
                    });
                });
            }
        </script>
    </body>
</html>
