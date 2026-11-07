<?php

namespace App\Services\Forecasting;

/**
 * Immutable value object returned by the ForecastEngine.
 */
final class ForecastResult
{
    /**
     * @param array<int, array{date: \Carbon\Carbon, predicted: float, lower: float, upper: float, is_peak: bool}> $points
     */
    public function __construct(
        public readonly string $algorithm,
        public readonly array $points,
        public readonly ?float $mape,
        public readonly int $historyDays,
        public readonly int $horizonDays,
    ) {}

    /**
     * @return array<int, array{date: \Carbon\Carbon, predicted: float, lower: float, upper: float, is_peak: bool}>
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
}
