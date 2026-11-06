<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: 'Inter', 'Figtree', ui-sans-serif, system-ui, sans-serif;
            }
        </style>
    </head>
    <body class="relative min-h-screen bg-clinic-50 font-sans text-slate-800 antialiased">
        <!-- Decorative background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 top-10 h-[420px] w-[420px] rounded-full bg-emerald-400/20 blur-3xl"></div>
            <div class="absolute -right-24 bottom-10 h-[380px] w-[380px] rounded-full bg-teal-400/20 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#0a2f25_1px,transparent_1px)] [background-size:26px_26px] opacity-[0.04]"></div>
        </div>

        <div class="relative z-10 flex min-h-screen flex-col">
            <!-- Brand -->
            <header class="px-5 pt-9 text-center sm:pt-12">
                <a href="{{ url('/') }}" class="inline-flex flex-col items-center gap-3 sm:flex-row sm:gap-3.5">
                    <x-clinic-logo ring="ring-[4px] ring-white" class="h-14 w-14 shadow-[0_16px_30px_-16px_rgba(16,146,106,0.6)] sm:h-12 sm:w-12" />
                    <span class="text-[22px] font-extrabold leading-7 tracking-tight text-clinic-950 sm:leading-6 sm:text-[20px]">Sombilon-Sabuga Dental Clinic</span>
                </a>
                <p class="mt-2.5 text-[15px] font-medium text-clinic-950/70">
                    Your smile, our passion. Expert care for a healthier you.
                </p>
            </header>

            <!-- Card -->
            <main class="flex flex-1 items-start justify-center px-5 py-8 sm:py-10">
                <div class="w-full max-w-md rounded-3xl bg-white/85 p-7 shadow-[0_25px_60px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 backdrop-blur-xl sm:p-9">
                    {{ $slot }}
                </div>
            </main>

            <footer class="px-5 pb-8 text-center text-sm text-clinic-950/50">
                &copy; {{ date('Y') }} Sombilon-Sabuga Dental Clinic. All rights reserved.
            </footer>
        </div>
    </body>
</html>
