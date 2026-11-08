<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sombilon-Sabuga Dental Clinic') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="relative min-h-screen bg-clinic-50 font-sans text-slate-800 antialiased">
        <!-- Decorative background (matches the welcome page hero) -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 top-10 h-[420px] w-[420px] rounded-full bg-emerald-400/15 blur-3xl"></div>
            <div class="absolute -right-24 bottom-10 h-[380px] w-[380px] rounded-full bg-teal-400/10 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#0a2f25_1px,transparent_1px)] [background-size:26px_26px] opacity-[0.03]"></div>
        </div>

        <div x-data="{ open: false }" class="relative min-h-screen">
            @include('layouts.navigation')

            <!-- Content column (offset by the sidebar on desktop) -->
            <div class="flex min-h-screen flex-col lg:pl-64">
                <!-- Page Heading -->
                @isset($header)
                    <header class="no-print border-b border-clinic-950/5 bg-white/70 shadow-[0_10px_30px_-20px_rgba(10,47,37,0.35)] backdrop-blur-xl">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>

                <!-- Footer -->
                <footer class="no-print border-t border-white/10 bg-clinic-950 py-6 text-center text-[14px] text-emerald-100/50">
                    &copy; {{ date('Y') }} Sombilon-Sabuga Dental Clinic. All rights reserved.
                </footer>
            </div>
        </div>
    </body>
</html>
