<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php($showForecast = auth()->user()->hasRole('admin', 'staff'))

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>

            @if($showForecast)
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <h3 class="font-semibold text-lg text-gray-800">📈 Appointment Forecast &amp; Clinic Volume</h3>
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
