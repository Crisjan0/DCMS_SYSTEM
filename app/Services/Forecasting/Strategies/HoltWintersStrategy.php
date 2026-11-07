<?php

namespace App\Services\Forecasting\Strategies;

/**
 * Holt-Winters triple exponential smoothing (additive seasonality, period = 7 days).
 *
 * Learns three components from the clinic's daily history:
 *   - level  → the current baseline volume
 *   - trend  → whether volume is growing or shrinking
 *   - season → weekly recurring pattern (busy Mondays, slow weekends, ...)
 *
 * Parameters are tuned automatically by picking (alpha, beta, gamma)
 * with the lowest one-step-ahead squared error on the training data.
 */
class HoltWintersStrategy implements ForecastStrategy
{
    private const PERIOD = 7; // weekly seasonality

    private const ALPHAS = [0.1, 0.15, 0.2, 0.3, 0.4, 0.5];
    private const BETAS = [0.01, 0.03, 0.05, 0.1];
    private const GAMMAS = [0.05, 0.1, 0.15, 0.2, 0.3];

    private array $values = [];
    private float $level = 0.0;
    private float $trend = 0.0;
    private array $season = [];
    private float $sigma = 0.0;

    public function name(): string
    {
        return 'holt_winters';
    }

    public function fit(array $values): void
    {
        $this->values = $values;

        $n = count($values);
        $m = self::PERIOD;

        if ($n === 0) {
            $this->level = 0.0;
            $this->trend = 0.0;
            $this->season = array_fill(0, $m, 0.0);
            $this->sigma = 0.0;

            return;
        }

        // Initial level / trend / season estimates
        $level = array_sum(array_slice($values, 0, min($m, $n))) / min($m, $n);
        $trend = 0.0;

        if ($n >= 2 * $m) {
            $secondMean = array_sum(array_slice($values, $m, $m)) / $m;
            $trend = ($secondMean - $level) / $m;
        }

        $season = [];
        for ($i = 0; $i < $m; $i++) {
            $season[$i] = $n > $i ? $values[$i] - $level : 0.0;
        }

        // Grid-search smoothing parameters → lowest in-sample squared error
        $bestSse = INF;
        $bestParams = null;

        foreach (self::ALPHAS as $alpha) {
            foreach (self::BETAS as $beta) {
                foreach (self::GAMMAS as $gamma) {
                    [$lvl, $tr, $s, $sse] = $this->train(
                        $values, $level, $trend, $season, $alpha, $beta, $gamma
                    );

                    if ($sse < $bestSse) {
                        $bestSse = $sse;
                        $bestParams = [$lvl, $tr, $s, $alpha, $beta, $gamma];
                    }
                }
            }
        }

        [$this->level, $this->trend, $this->season,, ,] = $bestParams;
        $this->sigma = sqrt(max(0.0, $bestSse / max(1, $n)));
    }

    public function forecast(int $horizon): array
    {
        $predictions = [];
        $m = self::PERIOD;
        $n = count($this->values);

        for ($h = 1; $h <= $horizon; $h++) {
            $seasonIndex = ($n + $h - 1) % $m;

            $predicted = $this->level + ($h * $this->trend) + $this->season[$seasonIndex];

            $predictions[] = max(0.0, $predicted);
        }

        return $predictions;
    }

    public function sigma(): float
    {
        return $this->sigma;
    }

    /**
     * @param array<float> $values
     * @param array<float> $season
     * @return array{0: float, 1: float, 2: array<float>, 3: float} final level, trend, season, SSE
     */
    private function train(
        array $values,
        float $level,
        float $trend,
        array $season,
        float $alpha,
        float $beta,
        float $gamma,
    ): array {
        $m = count($season);
        $sse = 0.0;

        foreach ($values as $i => $actual) {
            // One-step-ahead prediction BEFORE updating the components
            $forecast = $level + $trend + $season[$i % $m];
            $error = $actual - $forecast;
            $sse += $error * $error;

            // Update level, trend and seasonality with exponential smoothing
            $newLevel = ($alpha * ($actual - $season[$i % $m]))
                + ((1 - $alpha) * ($level + $trend));

            $trend = ($beta * ($newLevel - $level)) + ((1 - $beta) * $trend);
            $season[$i % $m] = ($gamma * ($actual - $newLevel))
                + ((1 - $gamma) * $season[$i % $m]);
            $level = $newLevel;
        }

        return [$level, $trend, $season, $sse];
    }
}
