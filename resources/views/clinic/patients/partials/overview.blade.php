<div class="space-y-6">
    <!-- Upcoming visits -->
    <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-lg text-clinic-950">🟢 Upcoming &amp; Today's Visits</h3>
            <span class="text-sm text-slate-500">{{ $upcoming->count() }} scheduled</span>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">Date &amp; Time</th>
                        <th class="px-6 py-3">Service</th>
                        <th class="px-6 py-3">Dentist</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($upcoming as $appointment)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                📅 {{ $appointment->scheduled_date->format('M d, Y') }} · 🕐 {{ $appointment->scheduled_time }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $appointment->service }}</td>
                            <td class="px-6 py-4 text-slate-600">Dr. {{ $appointment->dentist?->fullname() ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('clinic.patients.treatments.create', ['patient' => $patient, 'appointment' => $appointment->id]) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-clinic-600 rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                                        Chart Treatment
                                    </a>
                                    @if ($appointment->scheduled_date->lessThanOrEqualTo(today()))
                                        <form action="{{ route('clinic.appointments.status.update', $appointment) }}" method="POST"
                                              onsubmit="return confirm('Mark this visit as completed?');">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 ring-1 ring-emerald-200 rounded-full font-semibold text-xs text-emerald-700 uppercase tracking-wider hover:bg-emerald-100 transition">
                                                Completed
                                            </button>
                                        </form>
                                        <form action="{{ route('clinic.appointments.status.update', $appointment) }}" method="POST"
                                              onsubmit="return confirm('Mark this visit as a no-show?');">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="no_show">
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold text-xs text-slate-500 uppercase tracking-wider hover:text-rose-600 transition">
                                                No-show
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">No upcoming visits.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="p-4 space-y-3 md:hidden">
            @forelse ($upcoming as $appointment)
                <div class="rounded-2xl ring-1 ring-clinic-950/10 px-4 py-3.5">
                    <p class="font-semibold text-slate-800">{{ $appointment->service }}</p>
                    <p class="text-sm text-slate-500 mt-0.5">📅 {{ $appointment->scheduled_date->format('M d, Y') }} · {{ $appointment->scheduled_time }} · Dr. {{ $appointment->dentist?->fullname() ?? '—' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('clinic.patients.treatments.create', ['patient' => $patient, 'appointment' => $appointment->id]) }}"
                           class="rounded-full bg-clinic-600 px-3 py-1.5 text-xs font-semibold text-white uppercase">Chart Treatment</a>
                        @if ($appointment->scheduled_date->lessThanOrEqualTo(today()))
                            <form action="{{ route('clinic.appointments.status.update', $appointment) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="rounded-full bg-emerald-50 ring-1 ring-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 uppercase">Completed</button>
                            </form>
                            <form action="{{ route('clinic.appointments.status.update', $appointment) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="no_show">
                                <button type="submit" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">No-show</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-slate-400">No upcoming visits.</div>
            @endforelse
        </div>
    </div>

    <!-- Past visits -->
    <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-lg text-clinic-950">🕘 Recent Visit History</h3>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($history as $item)
                <li class="flex flex-wrap items-center justify-between gap-2 px-6 py-3.5 text-sm">
                    <span class="text-slate-700 whitespace-nowrap">📅 {{ $item->scheduled_date->format('M d, Y') }} · {{ $item->scheduled_time }}</span>
                    <span class="text-slate-600">{{ $item->service }}</span>
                    <span class="text-slate-500">Dr. {{ $item->dentist?->fullname() ?? '—' }}</span>
                    <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-emerald-50 text-emerald-700' => $item->status === 'completed',
                            'bg-rose-50 text-rose-700' => $item->status === 'cancelled',
                            'bg-amber-50 text-amber-700' => $item->status === 'no_show',
                        ])>
                        {{ str_replace('_', ' ', ucfirst($item->status)) }}
                    </span>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-slate-400">No past visits recorded.</li>
            @endforelse
        </ul>
    </div>
</div>
