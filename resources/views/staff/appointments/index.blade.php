<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-gray-800">
                        🟡 Pending Requests
                    </h3>
                    <span class="text-sm text-slate-500">{{ $pending->count() }} waiting for assignment</span>
                </div>
                <div class="overflow-x-auto">
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
                                           class="inline-flex items-center px-3 py-1.5 bg-clinic-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
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
            </div>

            <!-- Upcoming confirmed -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-gray-800">
                        🟢 Upcoming Confirmed
                    </h3>
                    <span class="text-sm text-slate-500">{{ $upcoming->count() }} scheduled</span>
                </div>
                <div class="overflow-x-auto">
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
                                        <form action="{{ route('staff.appointments.cancel', $appointment) }}" method="POST"
                                              onsubmit="return confirm('Cancel this appointment for {{ $appointment->patient?->fullname() }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Cancel</button>
                                        </form>
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
            </div>
        </div>
    </div>
</x-app-layout>
