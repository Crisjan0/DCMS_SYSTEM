<?php

namespace Tests\Unit\Services\Forecasting;

use App\Services\Forecasting\ForecastEngine;
use App\Services\Forecasting\Strategies\ArimaStrategy;
use Tests\TestCase;

class ArimaStrategyTest extends TestCase
{
    /**
     * Deterministic daily clinic series: weekly Mon..Sun pattern
     * (Sunday ≈ closed) with slow growth and a smooth wobble.
     */
    private function weeklyClinicSeries(int $days = 120, float $weeklyGrowth = 0.015): array
    {
        $pattern = [12.0, 9.0, 10.0, 11.0, 13.0, 8.0, 2.0];
        $series = [];

        for ($t = 0; $t < $days; $t++) {
            $growth = 1.0 + $weeklyGrowth * intdiv($t, 7);
            $ripple = 1.0 + 0.05 * sin($t * 0.35);

            $series[] = max(0.0, $pattern[$t % 7] * $growth * $ripple);
        }

        return $series;
    }

    public function test_identity_and_sigma_are_sane(): void
    {
        $strategy = new ArimaStrategy;

        $this->assertSame('arima', $strategy->name());

        $strategy->fit($this->weeklyClinicSeries());

        $this->assertGreaterThanOrEqual(0.0, $strategy->sigma());
        $this->assertStringContainsString('ARIMA(', $strategy->order());
    }

    public function test_arima_projects_a_growing_weekly_pattern(): void
    {
        $series = $this->weeklyClinicSeries();

        $strategy = new ArimaStrategy;
        $strategy->fit($series);

        $forecast = $strategy->forecast(7);

        $this->assertCount(7, $forecast);

        foreach ($forecast as $value) {
            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThanOrEqual(30.0, $value);
        }

        // ≈65 visits/week grown ~27% → roughly 80; wide sane window.
        $weekTotal = array_sum($forecast);
        $this->assertGreaterThanOrEqual(45.0, $weekTotal);
        $this->assertLessThanOrEqual(140.0, $weekTotal);

        // The weekly rhythm survives: the slow day stays far below the busiest.
        $this->assertLessThan(0.4 * max($forecast), min($forecast));
    }

    public function test_longer_horizon_stays_plausible(): void
    {
        $strategy = new ArimaStrategy;
        $strategy->fit($this->weeklyClinicSeries());

        $forecast = $strategy->forecast(90);

        $this->assertCount(90, $forecast);

        foreach ($forecast as $value) {
            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThanOrEqual(60.0, $value);
        }
    }

    public function test_all_zero_series_forecasts_zero_without_exploding(): void
    {
        $strategy = new ArimaStrategy;
        $strategy->fit(array_fill(0, 200, 0.0));

        $forecast = $strategy->forecast(30);

        $this->assertCount(30, $forecast);

        foreach ($forecast as $value) {
            $this->assertSame(0.0, $value);
        }

        $this->assertGreaterThanOrEqual(0.0, $strategy->sigma());
    }

    public function test_short_series_falls_back_to_seasonal_naive(): void
    {
        $strategy = new ArimaStrategy;
        // 10 days < the 14-day minimum → repeat the last observed week.
        $strategy->fit([5.0, 3.0, 5.0, 3.0, 4.0, 2.0, 5.0, 3.0, 4.0, 2.0]);

        $this->assertSame([3.0, 4.0, 2.0, 5.0, 3.0], $strategy->forecast(5));
    }

    public function test_minimum_history_window_still_forecasts(): void
    {
        $strategy = new ArimaStrategy;
        $strategy->fit($this->weeklyClinicSeries(30));

        $forecast = $strategy->forecast(14);

        $this->assertCount(14, $forecast);

        foreach ($forecast as $value) {
            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThanOrEqual(30.0, $value);
        }
    }

    public function test_forecast_is_clamped_to_a_sane_ceiling(): void
    {
        // Mostly quiet days with an occasional busier day — 2 × max(9) = 18.
        $series = [];

        for ($t = 0; $t < 90; $t++) {
            $series[] = $t % 21 === 0 ? 9.0 : 2.0;
        }

        $strategy = new ArimaStrategy;
        $strategy->fit($series);

        foreach ($strategy->forecast(60) as $value) {
            $this->assertLessThanOrEqual(18.0, $value);
            $this->assertGreaterThanOrEqual(0.0, $value);
        }
    }

    /**
     * Regression: sparse clinic logs (long zero stretches + sporadic few
     * visits) must never produce an explosive ARIMA → a 5×10¹⁶% MAPE badge.
     */
    public function test_mape_backtest_stays_sane_on_sparse_clinic_data(): void
    {
        $rows = [];
        $start = now()->subDays(90)->startOfDay();

        for ($i = 0; $i < 90; $i++) {
            $value = 0.0;

            if (in_array($i, [7, 15, 16, 30, 44, 45, 61, 75], true)) {
                $value = ($i + 1) % 4 + 1; // 1..4 sporadic visits
            } elseif ($i % 7 === 0) {
                $value = 1.0; // occasional walk-in day
            }

            $rows[] = ['date' => $start->copy()->addDays($i), 'value' => $value];
        }

        $result = (new ForecastEngine('arima'))->run($rows, 30);

        $this->assertNotNull($result->mape);
        $this->assertLessThan(2000.0, $result->mape);

        foreach ($result->points as $point) {
            $this->assertLessThanOrEqual(8.0, $point['predicted']); // cap = max(5, 2×4)
            $this->assertGreaterThanOrEqual(0.0, $point['predicted']);
        }
    }
}
