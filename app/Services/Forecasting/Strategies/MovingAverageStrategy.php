<?php

namespace App\Services\Forecasting\Strategies;

/**
 * Baseline model: predicts the average of the recent window for every day.
 * Great as an accuracy benchmark — anything better than this is a win.
 */
class MovingAverageStrategy implements ForecastStrategy
{
    private int $window = 28;

    private float $mean = 0.0;

    private float $sigma = 0.0;

    public function name(): string
    {
        return 'moving_average';
    }

    public function fit(array $values): void
    {
        $recent = array_slice($values, -$this->window);
        $count = max(1, count($recent));

        $this->mean = array_sum($recent) / $count;

        $variance = 0.0;
        foreach ($recent as $value) {
            $variance += ($value - $this->mean) ** 2;
        }
        $this->sigma = sqrt($variance / $count);
    }

    public function forecast(int $horizon): array
    {
        return array_fill(0, $horizon, max(0.0, $this->mean));
    }

    public function sigma(): float
    {
        return $this->sigma;
    }
}
