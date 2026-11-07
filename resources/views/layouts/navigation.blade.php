@php
    // Single source of truth for nav links — shared by the desktop sidebar
    // and the responsive drawer.
    $navItems = [
        [
            'label' => __('Dashboard'),
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'dashboard',
        ],
    ];

    if (Auth::user()->hasRole('staff', 'admin')) {
        $navItems[] = [
            'label' => __('Appointments'),
            'href' => route('staff.appointments.index'),
            'active' => request()->routeIs('staff.appointments.*'),
            'icon' => 'calendar',
        ];
    } else {
        $navItems[] = [
            'label' => __('Book Appointment'),
            'href' => route('appointments.create'),
            'active' => request()->routeIs('appointments.create'),
            'icon' => 'plus',
        ];
        $navItems[] = [
            'label' => __('My Appointments'),
            'href' => route('appointments.index'),
            'active' => request()->routeIs('appointments.index'),
            'icon' => 'calendar',
        ];
    }

    if (Auth::user()->hasRole('admin')) {
        $navItems[] = [
            'label' => __('Dentists'),
            'href' => route('admin.dentists.index'),
            'active' => request()->routeIs('admin.dentists.*'),
            'icon' => 'users',
        ];
    }

    $navItems[] = [
        'label' => __('Profile'),
        'href' => route('profile.edit'),
        'active' => request()->routeIs('profile.*'),
        'icon' => 'user',
    ];
@endphp

<!-- ── Desktop sidebar (≥ lg) ── -->
<aside class="fixed inset-y-0 start-0 z-50 hidden w-64 flex-col overflow-y-auto bg-clinic-950 text-emerald-50 lg:flex">
    <!-- Brand -->
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-6 pb-6 pt-7">
        <x-clinic-logo ring="ring-4 ring-white/10" class="h-11 w-11" />
        <span class="leading-5">
            <span class="block text-[15px] font-bold tracking-tight text-white">Sombilon-Sabuga</span>
            <span class="block text-[13px] font-semibold text-emerald-300/80">Dental Clinic</span>
        </span>
    </a>

    <!-- Section label -->
    <p class="px-8 pb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-200/40">
        Clinic Portal
    </p>

    <!-- Links -->
    <div class="flex flex-1 flex-col gap-1 px-4">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}"
               @class([
                   'inline-flex items-center gap-3 rounded-full px-4 py-2.5 text-[15px] font-medium transition-colors duration-150',
                   'bg-clinic-600 text-white shadow-[0_12px_25px_-12px_rgba(26,148,107,0.8)]' => $item['active'],
                   'text-emerald-50/70 hover:bg-white/10 hover:text-white focus:outline-none focus:bg-white/10' => ! $item['active'],
               ])>
                <x-nav-icon :icon="$item['icon']" :class="$item['active'] ? 'text-white' : 'text-emerald-300/80'" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    <!-- User block -->
    <div class="border-t border-white/10 p-4">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-2xl bg-white/10 px-3 py-2.5 transition-colors hover:bg-white/15">
            <x-user-avatar class="h-9 w-9" />
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[14px] font-semibold text-white">{{ Auth::user()->name }}</span>
                <span class="block truncate text-xs text-emerald-100/60">{{ Auth::user()->email }}</span>
            </span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="inline-flex w-full items-center gap-3 rounded-full px-4 py-2.5 text-[15px] font-medium text-emerald-100/80 transition-colors hover:bg-rose-500/15 hover:text-rose-100 focus:outline-none focus:ring-2 focus:ring-clinic-500">
                <x-nav-icon icon="logout" class="text-rose-300/80" />
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</aside>

<!-- ── Mobile top bar (< lg) ── -->
<header class="sticky top-0 z-40 flex h-16 items-center gap-3 border-b border-clinic-950/5 bg-white/80 px-4 shadow-[0_10px_30px_-20px_rgba(10,47,37,0.35)] backdrop-blur-xl sm:px-5 lg:hidden">
    <button type="button" @click="open = ! open" class="inline-flex items-center justify-center rounded-xl p-2 text-clinic-800 transition-colors hover:bg-clinic-100 focus:outline-none focus:ring-2 focus:ring-clinic-500" aria-label="Toggle navigation">
        <span class="relative block h-6 w-6">
            <!-- Hamburger -->
            <svg
                x-show="! open"
                class="absolute inset-0 h-6 w-6 transition-all duration-300 ease-out"
                x-transition:enter="transition-all duration-300 ease-out"
                x-transition:enter-start="opacity-0 rotate-90 scale-50"
                x-transition:enter-end="opacity-100 rotate-0 scale-100"
                x-transition:leave="transition-all duration-200 ease-in"
                x-transition:leave-start="opacity-100 rotate-0 scale-100"
                x-transition:leave-end="opacity-0 -rotate-90 scale-50"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <!-- Close -->
            <svg
                x-show="open"
                x-cloak
                class="absolute inset-0 h-6 w-6 transition-all duration-300 ease-out"
                x-transition:enter="transition-all duration-300 ease-out"
                x-transition:enter-start="opacity-0 -rotate-90 scale-50"
                x-transition:enter-end="opacity-100 rotate-0 scale-100"
                x-transition:leave="transition-all duration-200 ease-in"
                x-transition:leave-start="opacity-100 rotate-0 scale-100"
                x-transition:leave-end="opacity-0 rotate-90 scale-50"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </span>
    </button>

    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
        <x-clinic-logo ring="ring-1 ring-clinic-600/15" class="h-9 w-9" />
        <span class="text-[16px] font-bold tracking-tight text-clinic-950">Sombilon-Sabuga</span>
    </a>
</header>

<!-- ── Mobile drawer (< lg) ── -->
<div x-show="open"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 lg:hidden">

    <!-- Backdrop -->
    <div @click="open = false" class="absolute inset-0 bg-clinic-950/50 backdrop-blur-sm"></div>

    <!-- Drawer panel -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="absolute inset-y-0 start-0 flex w-72 flex-col overflow-y-auto bg-clinic-950 text-emerald-50 shadow-[0_35px_70px_-35px_rgba(10,47,37,0.75)]">

        <!-- Brand -->
        <a href="{{ route('dashboard') }}" @click="open = false" class="flex items-center gap-3 px-6 pb-6 pt-7">
            <x-clinic-logo ring="ring-4 ring-white/10" class="h-11 w-11" />
            <span class="leading-5">
                <span class="block text-[15px] font-bold tracking-tight text-white">Sombilon-Sabuga</span>
                <span class="block text-[13px] font-semibold text-emerald-300/80">Dental Clinic</span>
            </span>
        </a>

        <!-- Links -->
        <div class="flex flex-1 flex-col gap-1 px-4">
            @foreach ($navItems as $item)
                <a href="{{ $item['href'] }}" @click="open = false"
                   @class([
                       'inline-flex items-center gap-3 rounded-full px-4 py-2.5 text-[15px] font-medium transition-colors duration-150',
                       'bg-clinic-600 text-white shadow-[0_12px_25px_-12px_rgba(26,148,107,0.8)]' => $item['active'],
                       'text-emerald-50/70 hover:bg-white/10 hover:text-white' => ! $item['active'],
                   ])>
                    <x-nav-icon :icon="$item['icon']" :class="$item['active'] ? 'text-white' : 'text-emerald-300/80'" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        <!-- User block -->
        <div class="border-t border-white/10 p-4">
            <a href="{{ route('profile.edit') }}" @click="open = false" class="flex items-center gap-3 rounded-2xl bg-white/10 px-3 py-2.5 transition-colors hover:bg-white/15">
                <x-user-avatar class="h-9 w-9" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[14px] font-semibold text-white">{{ Auth::user()->name }}</span>
                    <span class="block truncate text-xs text-emerald-100/60">{{ Auth::user()->email }}</span>
                </span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="inline-flex w-full items-center gap-3 rounded-full px-4 py-2.5 text-[15px] font-medium text-emerald-100/80 transition-colors hover:bg-rose-500/15 hover:text-rose-100 focus:outline-none focus:ring-2 focus:ring-clinic-500">
                    <x-nav-icon icon="logout" class="text-rose-300/80" />
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</div>
