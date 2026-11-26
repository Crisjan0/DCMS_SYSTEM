<?php

namespace App\Services\Forecasting;

use Carbon\Carbon;

/**
 * Immutable value object returned by the ForecastEngine.
 */
final class ForecastResult
{
    /**
     * @param  array<int, array{date: Carbon, predicted: float, lower: float, upper: float, is_peak: bool}>  $points
     */
    public function __construct(
        public readonly string $algorithm,
        public readonly array $points,
        public readonly ?float $mape,
        public readonly int $historyDays,
        public readonly int $horizonDays,
    ) {}

    /**
     * @return array<int, array{date: Carbon, predicted: float, lower: float, upper: float, is_peak: bool}>
     */
    public function peaks(): array
    {
        return array_values(array_filter($this->points, fn (array $point) => $point['is_peak']));
    }

    public function averagePrediction(): float
    {
        $count = count($this->points);

        return $count === 0 ? 0.0 : array_sum(array_column($this->points, 'predicted')) / $count;
    }

    public function sumForNextDays(int $days): float
    {
        return array_sum(array_column(array_slice($this->points, 0, $days), 'predicted'));
    }

    /**
     * Force predicted visits to zero on declared clinic-closure dates
     * (holidays, maintenance) — the clinic simply doesn't operate then.
     *
     * @param  list<string>  $closureDates  Y-m-d strings
     */
    public function withClosureDaysZeroed(array $closureDates): self
    {
        $closureSet = array_flip($closureDates);

        $points = array_map(function (array $point) use ($closureSet) {
            if (isset($closureSet[$point['date']->toDateString()])) {
                return [
                    'date' => $point['date'],
                    'predicted' => 0.0,
                    'lower' => 0.0,
                    'upper' => 0.0,
                    'is_peak' => false,
                ];
            }

            return $point;
        }, $this->points);

        return new self($this->algorithm, $points, $this->mape, $this->historyDays, $this->horizonDays);
    }
}
