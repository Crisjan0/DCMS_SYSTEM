<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DailyClinicStat;
use App\Models\ForecastRun;

class ForecastController extends Controller
{
    /**
     * JSON feed for the dashboard forecast chart.
     */
    public function __invoke()
    {
        $run = ForecastRun::query()
            ->where('metric', 'total_visits')
            ->orderByDesc('ran_at')
            ->first();

        if ($run === null) {
            return response()->json([
                'meta' => null,
                'message' => 'No forecast generated yet — run: php artisan forecast:generate',
            ]);
        }

        $history = DailyClinicStat::query()
            ->whereBetween('date', [now()->subDays(120)->toDateString(), now()->subDay()->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn (DailyClinicStat $row) => [
                'date' => $row->date->toDateString(),
                'value' => (int) $row->total_visits,
            ]);

        $points = $run->points()
            ->orderBy('date')
            ->get()
            ->map(fn ($point) => [
                'date' => $point->date->toDateString(),
                'predicted' => round((float) $point->predicted, 1),
                'lower' => round((float) ($point->lower ?? $point->predicted), 1),
                'upper' => round((float) ($point->upper ?? $point->predicted), 1),
                'is_peak' => (bool) $point->is_peak,
            ]);

        $average = collect($points)->avg('predicted');

        $peaks = collect($points)
            ->filter(fn (array $point) => $point['is_peak'])
            ->sortByDesc('predicted')
            ->take(10)
            ->values()
            ->map(fn (array $point) => $point + [
                'percent_above_normal' => round((($point['predicted'] / max(0.1, $average)) - 1) * 100),
            ]);

        return response()->json([
            'meta' => [
                'algorithm' => $run->algorithm,
                'horizon_days' => $run->horizon_days,
                'mape' => $run->mape !== null ? round($run->mape, 2) : null,
                'generated_at' => $run->ran_at?->toDateTimeString(),
            ],
            'history' => $history,
            'forecast' => $points,
            'peaks' => $peaks,
            'next_7_days_total' => (int) round(collect($points)->take(7)->sum('predicted')),
            'next_30_days_total' => (int) round(collect($points)->take(30)->sum('predicted')),
        ]);
    }
}
