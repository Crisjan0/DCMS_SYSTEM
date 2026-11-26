<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                    {{ __('My Schedule') }}
                </h2>
                @if ($dentist)
                    <p class="text-sm text-slate-500">
                        Dr. {{ $dentist->fullname() }} — patients assigned to you
                    </p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (! $dentist)
                <div class="p-10 rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 text-center text-slate-400">
                    👤 This account is not linked to a dentist profile.
                </div>
            @else
                @php
                    $badgeFor = function (string $status) {
                        return match ($status) {
                            'pending' => ['border-amber-200 bg-amber-50 text-amber-700', '🟡 Pending'],
                            'confirmed' => ['border-emerald-200 bg-emerald-50 text-emerald-700', '🟢 Confirmed'],
                            'cancelled' => ['border-slate-200 bg-slate-100 text-slate-500', 'Cancelled'],
                            'completed' => ['border-sky-200 bg-sky-50 text-sky-700', 'Completed'],
                            default => ['border-rose-200 bg-rose-50 text-rose-700', 'No show'],
                        };
                    };
                @endphp

                <!-- Upcoming assigned appointments -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">
                            🟢 Upcoming Patients
                        </h3>
                        <span class="text-sm text-slate-500">{{ $upcoming->total() }} coming up</span>
                    </div>

                    <!-- ── Desktop table (≥ md) ── -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3">Patient</th>
                                    <th class="px-6 py-3">Service</th>
                                    <th class="px-6 py-3">Date &amp; Time</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($upcoming as $appointment)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-slate-800">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</span>
                                            <div class="text-xs text-slate-400">{{ $appointment->patient?->mobile_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">{{ $appointment->service }}</td>
                                        <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                            📅 {{ $appointment->scheduled_date->format('M d, Y') }} ·
                                            🕐 {{ $appointment->scheduled_time }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeFor($appointment->status)[0] }}">
                                                {{ $badgeFor($appointment->status)[1] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                            No assigned appointments yet — staff will route bookings to you.
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
                                <button type="button" @click="open = ! open" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 leading-snug">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            🦷 {{ $appointment->service }} · 📅 {{ $appointment->scheduled_date->format('M d') }} · 🕐 {{ $appointment->scheduled_time }}
                                        </p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $badgeFor($appointment->status)[0] }}">
                                        {{ $badgeFor($appointment->status)[1] }}
                                    </span>
                                </button>

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
                                            <span class="text-slate-400 shrink-0">Date &amp; time</span>
                                            <span class="text-slate-600 text-right whitespace-nowrap">
                                                📅 {{ $appointment->scheduled_date->format('M d, Y') }} · 🕐 {{ $appointment->scheduled_time }}
                                            </span>
                                        </div>

                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-slate-400 shrink-0">Notes</span>
                                            <p class="text-slate-600 text-right">{{ $appointment->notes ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                                No assigned appointments yet — staff will route bookings to you.
                            </div>
                        @endforelse
                    </div>

                    @if ($upcoming->hasPages())
                        <div class="px-6 py-4 border-t border-slate-100">
                            {{ $upcoming->links() }}
                        </div>
                    @endif
                </div>

                <!-- Past appointments -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">
                            🕘 Past Appointments
                        </h3>
                        <span class="text-sm text-slate-500">{{ $past->total() }} records</span>
                    </div>

                    <!-- ── Desktop table (≥ md) ── -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3">Patient</th>
                                    <th class="px-6 py-3">Service</th>
                                    <th class="px-6 py-3">Date &amp; Time</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($past as $appointment)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-slate-800">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</span>
                                            <div class="text-xs text-slate-400">{{ $appointment->patient?->mobile_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">{{ $appointment->service }}</td>
                                        <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                            📅 {{ $appointment->scheduled_date->format('M d, Y') }} ·
                                            🕐 {{ $appointment->scheduled_time }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeFor($appointment->status)[0] }}">
                                                {{ $badgeFor($appointment->status)[1] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                            No past appointments yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- ── Mobile cards (< md) ── -->
                    <div class="p-4 space-y-3 md:hidden">
                        @forelse ($past as $appointment)
                            <div x-data="{ open: false }" class="overflow-hidden rounded-2xl bg-white ring-1 ring-clinic-950/10 shadow-[0_16px_36px_-24px_rgba(10,47,37,0.5)]">
                                <button type="button" @click="open = ! open" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 leading-snug">{{ $appointment->patient?->fullname() ?? 'Unknown' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            🦷 {{ $appointment->service }} · 📅 {{ $appointment->scheduled_date->format('M d') }} · 🕐 {{ $appointment->scheduled_time }}
                                        </p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $badgeFor($appointment->status)[0] }}">
                                        {{ $badgeFor($appointment->status)[1] }}
                                    </span>
                                </button>

                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0">
                                    <div class="border-t border-slate-100 px-4 py-3 space-y-2.5 text-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-slate-400 shrink-0">Date &amp; time</span>
                                            <span class="text-slate-600 text-right whitespace-nowrap">
                                                📅 {{ $appointment->scheduled_date->format('M d, Y') }} · 🕐 {{ $appointment->scheduled_time }}
                                            </span>
                                        </div>

                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-slate-400 shrink-0">Notes</span>
                                            <p class="text-slate-600 text-right">{{ $appointment->notes ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                                No past appointments yet.
                            </div>
                        @endforelse
                    </div>

                    @if ($past->hasPages())
                        <div class="px-6 py-4 border-t border-slate-100">
                            {{ $past->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
