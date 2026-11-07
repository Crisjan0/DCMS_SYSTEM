<?php

namespace App\Services\Forecasting;

use App\Models\DailyClinicStat;
use Carbon\Carbon;

/**
 * Loads a metric's history as one continuous daily series
 * (gaps like closed Sundays are filled with zero so the
 * seasonal index stays aligned to real weekdays).
 */
class HistoricalDataCollector
{
    public function __construct(private int $years = 3) {}

    /**
     * @return array<int, array{date: Carbon, value: float}> chronological, gap-free daily rows
     */
    public function collect(string $metric = 'total_visits', ?int $years = null): array
    {
        $years ??= $this->years;

        $metric = in_array($metric, StatsAggregator::METRICS, true)
            ? $metric
            : 'total_visits';

        $start = now()->subYears((float) $years)->startOfDay()->toDateString();
        $end = now()->subDay()->startOfDay();

        $valueByDate = DailyClinicStat::query()
            ->whereBetween('date', [$start, $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn (DailyClinicStat $row) => [
                $row->date->toDateString() => (float) $row->{$metric},
            ]);

        $series = [];

        for ($cursor = Carbon::parse($start); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $series[] = [
                'date' => $cursor->copy(),
                'value' => $valueByDate[$cursor->toDateString()] ?? 0.0,
            ];
        }

        return $series;
    }
}
