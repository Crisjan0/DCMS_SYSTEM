<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php($showForecast = auth()->user()->hasRole('admin', 'staff'))

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

@if($staffView)
            {{-- ── Objective #4: metric cards ── --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl bg-clinic-950 px-5 py-4 text-emerald-50 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.55)]">
                    <p class="text-sm text-emerald-100/70">🗓 Patient flow today</p>
                    <p class="mt-1 text-3xl font-bold text-white">{{ $metrics['scheduled_today'] }}</p>
                    <p class="mt-1 text-xs text-emerald-100/70">
                        {{ $metrics['pending'] }} pending · {{ $metrics['confirmed'] }} confirmed · {{ $metrics['completed'] }} done
                    </p>
                </div>
                <div class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <p class="text-sm text-slate-500">🆕 New patients this month</p>
                    <p class="mt-1 text-3xl font-bold text-clinic-700">{{ $metrics['new_patients'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">Since {{ now()->startOfMonth()->format('M d') }}</p>
                </div>
                <div class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <p class="text-sm text-slate-500">💰 Collected</p>
                    <p class="mt-1 text-3xl font-bold text-clinic-700">₱{{ number_format($metrics['collected_today'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">₱{{ number_format($metrics['collected_month'], 2) }} this month</p>
                </div>
                <div class="rounded-3xl bg-amber-50 px-5 py-4 ring-1 ring-amber-200">
                    <p class="text-sm text-amber-700/80">🧾 Outstanding receivables</p>
                    <p class="mt-1 text-3xl font-bold text-amber-700">₱{{ number_format($metrics['receivable'], 2) }}</p>
                    <p class="mt-1 text-xs text-amber-600/80">Across unpaid &amp; partial invoices</p>
                </div>
            </div>

            {{-- ── Row: live patient flow + staff activity ── --}}
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Live patient flow (auto-refresh) -->
                <section class="lg:col-span-2 overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5"
                         x-data="{
                            rows: [],
                            summary: null,
                            updatedAt: '',
                            loading: false,
                            statusClass(status) {
                                return {
                                    pending: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
                                    confirmed: 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
                                    completed: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                    no_show: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                    cancelled: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                }[status] || 'bg-slate-100 text-slate-600';
                            },
                            async load() {
                                this.loading = true;
                                try {
                                    const response = await fetch('{{ route('dashboard.flow') }}');
                                    const payload = await response.json();
                                    this.rows = payload.appointments;
                                    this.summary = payload.summary;
                                    this.updatedAt = payload.generated_at;
                                } finally {
                                    this.loading = false;
                                }
                            },
                            init() {
                                this.load();
                                setInterval(() => this.load(), 30000);
                            }
                         }">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5" title="Live — refreshes every 30 seconds">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            </span>
                            <h3 class="font-semibold text-lg text-clinic-950">Today's Patient Flow</h3>
                        </div>
                        <span class="text-xs text-slate-400" x-show="updatedAt" x-text="'Updated ' + updatedAt"></span>
                    </div>

                    <template x-if="summary">
                        <div class="flex flex-wrap gap-2 px-6 pt-4 text-xs">
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 font-semibold text-sky-700"><span x-text="summary.pending"></span> pending</span>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 font-semibold text-indigo-700"><span x-text="summary.confirmed"></span> confirmed</span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700"><span x-text="summary.completed"></span> completed</span>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700"><span x-text="summary.no_show"></span> no-shows</span>
                        </div>
                    </template>

                    <!-- Desktop rows -->
                    <div class="hidden md:block px-6 pb-6 pt-4">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="py-2 font-semibold">Time</th>
                                    <th class="py-2 font-semibold">Patient</th>
                                    <th class="py-2 font-semibold">Service</th>
                                    <th class="py-2 font-semibold">Dentist</th>
                                    <th class="py-2 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="appt in rows" :key="appt.time + appt.patient">
                                    <tr>
                                        <td class="py-2.5 font-mono text-slate-700" x-text="appt.time"></td>
                                        <td class="py-2.5 font-medium text-slate-800" x-text="appt.patient ?? '—'"></td>
                                        <td class="py-2.5 text-slate-600" x-text="appt.service"></td>
                                        <td class="py-2.5 text-slate-600" x-text="appt.dentist ?? 'Unassigned'"></td>
                                        <td class="py-2.5">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                  :class="statusClass(appt.status)" x-text="appt.status_label"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p x-show="rows.length === 0 && !loading" class="py-10 text-center text-slate-400">
                            No visits scheduled today.
                        </p>
                    </div>

                    <!-- Mobile rows -->
                    <div class="md:hidden px-6 pb-6 pt-4 space-y-3">
                        <template x-for="appt in rows" :key="'m-' + appt.time + appt.patient">
                            <div class="rounded-2xl ring-1 ring-clinic-950/10 px-4 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 truncate" x-text="appt.patient ?? '—'"></p>
                                    <p class="text-xs text-slate-500 truncate" x-text="appt.time + ' · ' + appt.service + (appt.dentist ? ' · ' + appt.dentist : '')"></p>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap"
                                      :class="statusClass(appt.status)" x-text="appt.status_label"></span>
                            </div>
                        </template>
                        <p x-show="rows.length === 0 && !loading" class="py-8 text-center text-slate-400">No visits today.</p>
                    </div>
                </section>

                <!-- Staff activity feed -->
                <section class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">🖥 Staff Activity</h3>
                        <span class="text-xs text-slate-400">last 8 actions</span>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($activity as $log)
                            <li class="flex items-start gap-3 px-6 py-3.5">
                                <span class="mt-0.5 text-lg leading-none">{{ $log->icon() }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm text-slate-700 leading-snug">{{ $log->description }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans(short: true) }}
                                    </p>
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-10 text-center text-slate-400">No activity logged yet — actions will appear here as staff work.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            {{-- ── Resource allocation: per-dentist utilization ── --}}
            <section class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-clinic-950">📊 Resource Allocation — Dentist Utilization</h3>
                    <span class="text-xs text-slate-400">{{ $totalSlots }} slot capacity per day</span>
                </div>
                <div class="px-6 py-5 space-y-4">
                    @forelse ($dentists as $dentist)
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="font-medium text-slate-800">🧑‍⚕️ Dr. {{ $dentist['name'] }}</span>
                                <span class="text-slate-500">
                                    {{ $dentist['bookings_today'] }}/{{ $totalSlots }} slots today
                                    · {{ $dentist['free_slots'] }} free
                                    · {{ $dentist['week_bookings'] }} booked this week
                                </span>
                            </div>
                            <div class="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $dentist['utilization'] >= 80 ? 'bg-emerald-500' : ($dentist['utilization'] >= 40 ? 'bg-sky-500' : 'bg-slate-400') }}"
                                     style="width: {{ max(4, $dentist['utilization']) }}%"></div>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $dentist['utilization'] }}% utilized</p>
                        </div>
                    @empty
                        <p class="py-4 text-center text-slate-400">No dentists registered yet.</p>
                    @endforelse

                    @if ($forecastNext7 !== null)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-clinic-50 px-4 py-3 text-sm text-clinic-800">
                            <span>🔮 Predicted visits for the next 7 days (latest forecast run)</span>
                            <span class="font-bold">~{{ number_format($forecastNext7, 1) }}</span>
                        </div>
                    @endif
                </div>
            </section>

            @if($showForecast)
                <div class="mt-0 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <h3 class="font-semibold text-lg text-clinic-950">📈 Appointment Forecast &amp; Clinic Volume</h3>
                            <span id="forecast-accuracy" class="text-sm text-gray-500"></span>
                        </div>
                        <p class="text-sm text-gray-500">
                            Last 120 days of actual visits (solid blue) vs predicted (dashed amber) · shaded band = ~80% range · 🔴 red dots = predicted spike days
                        </p>

                        <div id="forecast-loading" class="mt-4 text-sm text-gray-400">Loading forecast…</div>
                        <div id="forecast-empty" class="hidden mt-4 text-sm text-gray-500">
                            No forecast data yet — run <code>php artisan forecast:generate</code>.
                        </div>
                        <canvas id="forecast-chart" class="hidden mt-4" height="120"></canvas>

                        <div id="forecast-summary" class="hidden mt-4 text-sm text-gray-700 space-y-1"></div>
                        <div id="forecast-peaks" class="hidden mt-4"></div>
                    </div>
                </div>
            @endif

@else
            {{-- ── Patient home ── --}}
            <div class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-clinic-950 px-5 py-5 text-emerald-50 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.55)]">
                        <p class="text-sm text-emerald-100/70">🦷 Completed visits</p>
                        <p class="mt-1 text-3xl font-bold text-white">{{ $completedVisits }}</p>
                        <p class="mt-1 text-xs text-emerald-100/70">At Sombilon-Sabuga Dental Clinic</p>
                    </div>
                    <div class="rounded-3xl bg-white px-5 py-5 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 @if($outstanding > 0) ring-amber-200 bg-amber-50/50 @endif">
                        <p class="text-sm text-slate-500">🧾 Outstanding balance</p>
                        <p class="mt-1 text-3xl font-bold {{ $outstanding > 0 ? 'text-amber-600' : 'text-clinic-700' }}">₱{{ number_format($outstanding, 2) }}</p>
                        <a href="{{ route('my-invoices.index') }}" class="mt-2 inline-block text-xs font-semibold text-clinic-700 hover:text-clinic-800">View my invoices →</a>
                    </div>
                </div>

                {{-- Next appointment card --}}
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">📅 Your next visit</h3>
                    </div>
                    @if ($nextAppointment)
                        <div class="px-6 py-5">
                            <p class="text-lg font-semibold text-slate-800">{{ $nextAppointment->service }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $nextAppointment->scheduled_date->format('l, M d, Y') }} at
                                {{ \Illuminate\Support\Carbon::parse($nextAppointment->scheduled_time)->format('g:i A') }}
                                @if ($nextAppointment->dentist)
                                    with Dr. {{ $nextAppointment->dentist->fullname() }}
                                @endif
                            </p>
                            <span @class([
                                    'mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-sky-50 text-sky-700' => $nextAppointment->status === 'pending',
                                    'bg-indigo-50 text-indigo-700' => $nextAppointment->status === 'confirmed',
                                ])>
                                {{ ucfirst($nextAppointment->status) }}
                            </span>
                        </div>
                    @else
                        <div class="px-6 py-10 text-center">
                            <p class="text-slate-500">No upcoming visit yet.</p>
                            <a href="{{ route('appointments.create') }}"
                               class="mt-3 inline-flex items-center rounded-full bg-clinic-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-clinic-700 transition">
                                📅 Book an appointment
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Quick actions --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <a href="{{ route('appointments.index') }}"
                       class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 hover:ring-clinic-400 transition">
                        <p class="font-semibold text-clinic-800">📅 My Appointments</p>
                        <p class="mt-1 text-xs text-slate-400">View, cancel or reschedule</p>
                    </a>
                    <a href="{{ route('my-record.index') }}"
                       class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 hover:ring-clinic-400 transition">
                        <p class="font-semibold text-clinic-800">❤️ My Health Record</p>
                        <p class="mt-1 text-xs text-slate-400">Health declaration &amp; tooth chart</p>
                    </a>
                    <a href="{{ route('profile.edit') }}"
                       class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 hover:ring-clinic-400 transition">
                        <p class="font-semibold text-clinic-800">👤 My Profile</p>
                        <p class="mt-1 text-xs text-slate-400">Keep contact details updated</p>
                    </a>
                </div>
            </div>
@endif
        </div>
    </div>

    @if($showForecast)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                fetch("{{ route('dashboard.forecast') }}")
                    .then(function (res) { return res.json(); })
                    .then(function (payload) {
                        document.getElementById('forecast-loading').classList.add('hidden');

                        if (!payload.meta || payload.forecast.length === 0) {
                            document.getElementById('forecast-empty').classList.remove('hidden');
                            return;
                        }

                        // ── Accuracy badge ──
                        var mape = payload.meta.mape;
                        var grade = mape == null ? '' : (mape < 15 ? 'good ✔' : mape < 30 ? 'fair' : 'needs tuning');
                        document.getElementById('forecast-accuracy').textContent =
                            payload.meta.algorithm.replace(/_/g, ' ') +
                            ' · MAPE ' + (mape == null ? 'n/a' : mape + '%') + (grade ? ' (' + grade + ')' : '');

                        // ── Quick summary ──
                        var summary = document.getElementById('forecast-summary');
                        summary.classList.remove('hidden');
                        summary.innerHTML =
                            '📅 <strong>Next 7 days:</strong> ~' + payload.next_7_days_total + ' predicted visits &nbsp;·&nbsp; ' +
                            '<strong>Next 30 days:</strong> ~' + payload.next_30_days_total + ' predicted visits';

                        // ── Chart ──
                        var gap = payload.history.length;             // where the forecast begins
                        var labels = payload.history.map(function (d) { return d.date; })
                            .concat(payload.forecast.map(function (d) { return d.date; }));
                        var nulls = payload.history.map(function () { return null; });

                        var historyData = payload.history.map(function (d) { return d.value; })
                            .concat(payload.forecast.map(function () { return null; }));
                        var lineData = payload.history.map(function (d, i) { return i === gap - 1 ? d.value : null; })
                            .concat(payload.forecast.map(function (d) { return d.predicted; }));
                        var upperData = nulls.concat(payload.forecast.map(function (d) { return d.upper; }));
                        var lowerData = nulls.concat(payload.forecast.map(function (d) { return d.lower; }));
                        var radii = lineData.map(function (v, i) {
                            return v === null ? 0 : (i >= gap && payload.forecast[i - gap].is_peak ? 5 : 0);
                        });

                        if (typeof Chart === 'undefined') {
                            document.getElementById('forecast-empty').classList.remove('hidden');
                            document.getElementById('forecast-empty').innerHTML =
                                'Chart library unavailable (offline?). JSON data still available at <code>GET /dashboard/forecast</code>.';
                            return;
                        }

                        document.getElementById('forecast-chart').classList.remove('hidden');

                        new Chart(document.getElementById('forecast-chart'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Actual visits',
                                        data: historyData,
                                        borderColor: '#3b82f6',
                                        backgroundColor: '#3b82f6',
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        tension: 0.25
                                    },
                                    {
                                        label: 'Forecast',
                                        data: lineData,
                                        borderColor: '#f59e0b',
                                        borderWidth: 2,
                                        borderDash: [6, 4],
                                        pointRadius: radii,
                                        pointBackgroundColor: '#ef4444',
                                        pointBorderColor: '#ef4444',
                                        pointHoverRadius: 6,
                                        tension: 0.25,
                                        fill: false
                                    },
                                    {
                                        label: 'Upper range',
                                        data: upperData,
                                        borderColor: 'rgba(245, 158, 11, 0.35)',
                                        borderWidth: 1,
                                        pointRadius: 0,
                                        fill: false
                                    },
                                    {
                                        label: 'Lower range',
                                        data: lowerData,
                                        borderColor: 'rgba(245, 158, 11, 0.35)',
                                        borderWidth: 1,
                                        pointRadius: 0,
                                        backgroundColor: 'rgba(245, 158, 11, 0.10)',
                                        fill: '-1'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { position: 'bottom' },
                                    tooltip: { filter: function (item) { return item.raw !== null; } }
                                },
                                scales: {
                                    x: {
                                        ticks: { autoSkip: true, maxTicksLimit: 14, maxRotation: 0 }
                                    },
                                    y: { beginAtZero: true }
                                }
                            }
                        });

                        // ── Spike day badges ──
                        if (payload.peaks.length > 0) {
                            var box = document.getElementById('forecast-peaks');
                            box.classList.remove('hidden');
                            box.innerHTML =
                                '<div class="font-medium text-gray-700 mb-2">⚡ Predicted spike days:</div>' +
                                payload.peaks.map(function (p) {
                                    return '<span class="inline-block mr-2 mb-2 px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs">' +
                                        '⚡ ' + p.date + ' · ~' + p.predicted + ' visits (+' + p.percent_above_normal + '%)' +
                                        '</span>';
                                }).join('');
                        }
                    })
                    .catch(function () {
                        document.getElementById('forecast-loading').classList.add('hidden');
                        document.getElementById('forecast-empty').classList.remove('hidden');
                    });
            })();
        </script>
    @endif
</x-app-layout>
