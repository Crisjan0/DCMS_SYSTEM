<?php

namespace App\Services\Forecasting;

use App\Services\Forecasting\Strategies\ForecastStrategy;
use App\Services\Forecasting\Strategies\HoltWintersStrategy;
use App\Services\Forecasting\Strategies\MovingAverageStrategy;
use Carbon\Carbon;

class ForecastEngine
{
    public const ALGORITHMS = [
        'holt_winters' => HoltWintersStrategy::class,
        'moving_average' => MovingAverageStrategy::class,
    ];

    public function __construct(private string $algorithm = 'holt_winters') {}

    /**
     * Turn a daily historical series into a forecast for the next $horizon days.
     *
     * @param  array<int, array{date: Carbon, value: float}>  $rows  consecutive daily rows
     */
    public function run(
        array $rows,
        int $horizon = 90,
        int $backtestDays = 90,
        float $peakThreshold = 1.2,
    ): ForecastResult {
        $strategy = $this->makeStrategy();

        $values = array_map(static fn (array $row) => (float) $row['value'], $rows);
        $count = count($values);
        $lastDate = $count > 0 ? Carbon::parse($rows[$count - 1]['date']) : now();

        // ── Backtest: hide the last N days, train without them, then score ──
        $mape = null;
        $holdout = min($backtestDays, intdiv($count, 3));

        if ($holdout >= 14) {
            $backtestStrategy = $this->makeStrategy();
            $backtestStrategy->fit(array_slice($values, 0, $count - $holdout));

            $scored = $backtestStrategy->forecast($holdout);
            $mape = $this->mape(array_slice($values, -$holdout), $scored);
        }

        // ── Train on the full history and predict the future ──
        $strategy->fit($values);
        $predictions = $strategy->forecast($horizon);
        $sigma = $strategy->sigma();

        $points = [];
        $average = array_sum($predictions) / max(1, count($predictions));

        foreach ($predictions as $index => $predicted) {
            $stepsAhead = $index + 1; // uncertainty grows the further out we go
            $band = 1.28 * $sigma * (1 + 0.03 * $stepsAhead); // ≈80% interval

            $points[] = [
                'date' => $lastDate->copy()->addDays($stepsAhead),
                'predicted' => $predicted,
                'lower' => max(0.0, $predicted - $band),
                'upper' => $predicted + $band,
                'is_peak' => false,
            ];
        }

        $this->markPeaks($points, $average, $peakThreshold);

        return new ForecastResult(
            $strategy->name(),
            $points,
            $mape,
            $count,
            $horizon,
        );
    }

    /**
     * Flag predicted days well above the average volume as "seasonal spikes".
     * Guarantees at least the top 5 busiest predicted days get flagged.
     */
    private function markPeaks(array &$points, float $average, float $threshold): void
    {
        $byThreshold = 0;

        foreach ($points as $index => $point) {
            if ($point['predicted'] >= $average * $threshold) {
                $points[$index]['is_peak'] = true;
                $byThreshold++;
            }
        }

        if ($byThreshold === 0 && $points !== []) {
            $indices = array_keys($points);

            usort($indices, static fn (int $a, int $b) => $points[$b]['predicted'] <=> $points[$a]['predicted']);

            foreach (array_slice($indices, 0, min(5, count($points))) as $index) {
                $points[$index]['is_peak'] = true;
            }
        }
    }

    /**
     * Mean Absolute Percentage Error — how wrong were we, on average (%).
     */
    private function mape(array $actual, array $predicted): ?float
    {
        $count = min(count($actual), count($predicted));

        if ($count === 0) {
            return null;
        }

        $total = 0.0;
        $scored = 0;

        for ($i = 0; $i < $count; $i++) {
            // Closed days (0 actual visits) are structural zeros — the standard
            // practice is to score MAPE on open clinic days only.
            if ($actual[$i] <= 0) {
                continue;
            }

            $total += abs($actual[$i] - $predicted[$i]) / max(1.0, (float) $actual[$i]);
            $scored++;
        }

        return $scored === 0 ? null : ($total / $scored) * 100;
    }

    private function makeStrategy(): ForecastStrategy
    {
        $class = self::ALGORITHMS[$this->algorithm] ?? HoltWintersStrategy::class;

        return new $class;
    }
}
