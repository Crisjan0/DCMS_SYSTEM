<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('Appointments') }} — Staff Console
        </h2>
    </x-slot>

    @if (session('success'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                ✅ {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- Pending: waiting for dentist assignment -->
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-clinic-950">
                        🟡 Pending Requests
                    </h3>
                    <span class="text-sm text-slate-500">{{ $pending->total() }} waiting for assignment</span>
                </div>

                <!-- ── Desktop table (≥ md) ── -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Patient</th>
                                <th class="px-6 py-3">Service</th>
                                <th class="px-6 py-3">Date &amp; Time</th>
                                <th class="px-6 py-3">Notes</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pending as $appointment)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-800">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</span>
                                        <div class="text-xs text-slate-400">{{ $appointment->patient?->user?->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $appointment->service }}</td>
                                    <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                        📅 {{ $appointment->scheduled_date->format('M d, Y') }} ·
                                        🕐 {{ $appointment->scheduled_time }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 max-w-[220px] truncate" title="{{ $appointment->notes }}">
                                        {{ $appointment->notes ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('staff.appointments.assign.form', $appointment) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-clinic-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                                            Assign Dentist
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                        🎉 No pending requests — all caught up!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ── Mobile cards (< md) ── -->
                <div class="p-4 space-y-3 md:hidden">
                    @forelse ($pending as $appointment)
                        <div x-data="{ open: false }" class="overflow-hidden rounded-2xl bg-white ring-1 ring-clinic-950/10 shadow-[0_16px_36px_-24px_rgba(10,47,37,0.5)]">
                            <!-- Summary row (tap to open) -->
                            <button type="button" @click="open = ! open" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 leading-snug">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        🦷 {{ $appointment->service }} · 📅 {{ $appointment->scheduled_date->format('M d') }} · 🕐 {{ $appointment->scheduled_time }}
                                    </p>
                                </div>
                                <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 text-slate-400 mt-1 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <!-- Details (expanded) -->
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0">
                                <div class="border-t border-slate-100 px-4 py-3 space-y-2.5 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Mobile number</span>
                                        <span class="text-slate-600">{{ $appointment->patient?->mobile_number ?? '—' }}</span>
                                    </div>

                                    <div class="flex items-start justify-between gap-3">
                                        <span class="text-slate-400 shrink-0">Notes</span>
                                        <p class="text-slate-600 text-right">{{ $appointment->notes ?? '—' }}</p>
                                    </div>
                                </div>

                                <div class="px-4 pb-4">
                                    <a href="{{ route('staff.appointments.assign.form', $appointment) }}"
                                       class="block w-full rounded-full bg-clinic-600 px-4 py-2.5 text-center text-sm font-semibold text-white uppercase tracking-wide hover:bg-clinic-700 transition">
                                        Assign Dentist
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                            🎉 No pending requests — all caught up!
                        </div>
                    @endforelse
                </div>

                @if ($pending->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $pending->links() }}
                    </div>
                @endif
            </div>

            <!-- Upcoming confirmed -->
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-clinic-950">
                        🟢 Upcoming Confirmed
                    </h3>
                    <span class="text-sm text-slate-500">{{ $upcoming->total() }} scheduled</span>
                </div>

                <!-- ── Desktop table (≥ md) ── -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Patient</th>
                                <th class="px-6 py-3">Dentist</th>
                                <th class="px-6 py-3">Service</th>
                                <th class="px-6 py-3">Date &amp; Time</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($upcoming as $appointment)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4 font-medium text-slate-800">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-700">Dr. {{ $appointment->dentist?->fullname() ?? '—' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $appointment->service }}</td>
                                    <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                        📅 {{ $appointment->scheduled_date->format('M d, Y') }} ·
                                        🕐 {{ $appointment->scheduled_time }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="{{ route('staff.appointments.reschedule.form', $appointment) }}"
                                               class="text-clinic-700 hover:text-clinic-900 font-medium">Reschedule</a>
                                            <form action="{{ route('staff.appointments.cancel', $appointment) }}" method="POST"
                                                  onsubmit="return confirm('Cancel this appointment for {{ $appointment->patient?->fullname() }}?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                        No upcoming confirmed appointments.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ── Mobile cards (< md) ── -->
                <div class="p-4 space-y-3 md:hidden">
                    @forelse ($upcoming as $appointment)
                        <div x-data="{ open: false }" class="overflow-hidden rounded-2xl bg-white ring-1 ring-clinic-950/10 shadow-[0_16px_36px_-24px_rgba(10,47,37,0.5)]">
                            <!-- Summary row (tap to open) -->
                            <button type="button" @click="open = ! open" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 leading-snug">
                                        {{ $appointment->patient?->fullname() ?? 'Unknown' }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        🩺 Dr. {{ $appointment->dentist?->fullname() ?? '—' }} · 📅 {{ $appointment->scheduled_date->format('M d') }} · {{ $appointment->scheduled_time }}
                                    </p>
                                </div>
                                <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 text-slate-400 mt-1 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <!-- Details (expanded) -->
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0">
                                <div class="border-t border-slate-100 px-4 py-3 space-y-2.5 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Service</span>
                                        <span class="text-slate-600">{{ $appointment->service }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Date &amp; time</span>
                                        <span class="text-slate-600 whitespace-nowrap">
                                            📅 {{ $appointment->scheduled_date->format('M d, Y') }} · 🕐 {{ $appointment->scheduled_time }}
                                        </span>
                                    </div>
                                </div>

                                <div class="px-4 pb-4 space-y-2">
                                    <a href="{{ route('staff.appointments.reschedule.form', $appointment) }}"
                                       class="block w-full rounded-full bg-clinic-50 px-4 py-2.5 text-center text-sm font-semibold text-clinic-700 ring-1 ring-clinic-200 hover:bg-clinic-100 transition">
                                        Reschedule
                                    </a>
                                    <form action="{{ route('staff.appointments.cancel', $appointment) }}" method="POST"
                                          onsubmit="return confirm('Cancel this appointment for {{ $appointment->patient?->fullname() }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full rounded-full bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100 transition">
                                            Cancel Appointment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                            No upcoming confirmed appointments.
                        </div>
                    @endforelse
                </div>

                @if ($upcoming->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $upcoming->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
