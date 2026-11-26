<?php

namespace App\Services\Forecasting\Strategies;

interface ForecastStrategy
{
    /**
     * Unique name stored on each forecast run.
     */
    public function name(): string;

    /**
     * Train the model on a chronologically ordered daily series.
     */
    public function fit(array $values): void;

    /**
     * Predict the next $horizon daily values.
     *
     * @return float[]
     */
    public function forecast(int $horizon): array;

    /**
     * One-step residual deviation, used for the confidence band.
     */
    public function sigma(): float;
}
