<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                {{ __('My Appointments') }}
            </h2>

            <a href="{{ route('appointments.create') }}" class="inline-flex items-center px-4 py-2 bg-clinic-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500/40 transition">
                + Book Appointment
            </a>
        </div>
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
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

                <!-- ── Desktop table (≥ md) ── -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Service</th>
                                <th class="px-6 py-3">Date &amp; Time</th>
                                <th class="px-6 py-3">Dentist</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Notes</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($appointments as $appointment)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4 font-medium text-slate-800">{{ $appointment->service }}</td>
                                    <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                        📅 {{ $appointment->scheduled_date->format('M d, Y') }} ·
                                        🕐 {{ $appointment->scheduled_time }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($appointment->dentist)
                                            <span class="font-medium text-slate-700">Dr. {{ $appointment->dentist->fullname() }}</span>
                                        @elseif ($appointment->status === 'pending')
                                            <span class="text-amber-700 text-xs">⏳ Waiting for staff assignment</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeFor($appointment->status)[0] }}">
                                            {{ $badgeFor($appointment->status)[1] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 max-w-[200px] truncate" title="{{ $appointment->notes }}">
                                        {{ $appointment->notes ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if ($appointment->isCancellable())
                                            <form action="{{ route('appointments.cancel', $appointment) }}" method="POST"
                                                  onsubmit="return confirm('Cancel your {{ $appointment->service }} on {{ $appointment->scheduled_date->format('M d, Y') }} at {{ $appointment->scheduled_time }}?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Cancel</button>
                                            </form>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        No appointments yet.
                                        <a href="{{ route('appointments.create') }}" class="text-clinic-600 font-medium hover:underline">Book your first appointment</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ── Mobile cards (< md) ── -->
                <div class="p-4 space-y-3 md:hidden">
                    @forelse ($appointments as $appointment)
                        <div x-data="{ open: false }" class="overflow-hidden rounded-2xl bg-white ring-1 ring-clinic-950/10 shadow-[0_16px_36px_-24px_rgba(10,47,37,0.5)]">
                            <!-- Summary row (tap to open) -->
                            <button type="button" @click="open = ! open" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 leading-snug">{{ $appointment->service }}</p>
                                    <p class="mt-1 text-sm text-slate-500 whitespace-nowrap">
                                        📅 {{ $appointment->scheduled_date->format('M d, Y') }} ·
                                        🕐 {{ $appointment->scheduled_time }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $badgeFor($appointment->status)[0] }}">
                                        {{ $badgeFor($appointment->status)[1] }}
                                    </span>
                                    <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
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
                                        <span class="text-slate-400">Dentist</span>
                                        @if ($appointment->dentist)
                                            <span class="font-medium text-slate-700 text-right">Dr. {{ $appointment->dentist->fullname() }}</span>
                                        @elseif ($appointment->status === 'pending')
                                            <span class="text-amber-700 text-right">⏳ Waiting for staff</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Request submitted</span>
                                        <span class="text-slate-600">{{ $appointment->created_at?->format('M d, Y') ?? '—' }}</span>
                                    </div>

                                    <div>
                                        <span class="text-slate-400">Notes</span>
                                        <p class="mt-0.5 text-slate-600">{{ $appointment->notes ?? '—' }}</p>
                                    </div>
                                </div>

                                @if ($appointment->isCancellable())
                                    <div class="px-4 pb-4">
                                        <form action="{{ route('appointments.cancel', $appointment) }}" method="POST"
                                              onsubmit="return confirm('Cancel your {{ $appointment->service }} on {{ $appointment->scheduled_date->format('M d, Y') }} at {{ $appointment->scheduled_time }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-full rounded-full bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100 transition">
                                                Cancel Appointment
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                            No appointments yet.
                            <a href="{{ route('appointments.create') }}" class="text-clinic-600 font-medium hover:underline">Book your first appointment</a>
                        </div>
                    @endforelse
                </div>

                @if ($appointments->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $appointments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
