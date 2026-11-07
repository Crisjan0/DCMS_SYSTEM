<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Appointments') }}
            </h2>

            <a href="{{ route('appointments.create') }}" class="inline-flex items-center px-4 py-2 bg-clinic-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500/40 transition">
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
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
                                @php
                                    $badge = match ($appointment->status) {
                                        'pending' => ['border-amber-200 bg-amber-50 text-amber-700', '🟡 Pending'],
                                        'confirmed' => ['border-emerald-200 bg-emerald-50 text-emerald-700', '🟢 Confirmed'],
                                        'cancelled' => ['border-slate-200 bg-slate-100 text-slate-500', 'Cancelled'],
                                        'completed' => ['border-sky-200 bg-sky-50 text-sky-700', 'Completed'],
                                        default => ['border-rose-200 bg-rose-50 text-rose-700', 'No show'],
                                    };
                                @endphp
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badge[0] }}">
                                            {{ $badge[1] }}
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
            </div>
        </div>
    </div>
</x-app-layout>
