<?php

namespace App\Services\Forecasting\Strategies;

/**
 * ARIMA(p, 1, q) with weekly seasonal differencing — (0, 1, 0)₇.
 *
 * Pure-PHP Box–Jenkins style estimation (no external ML/statistics packages):
 *
 *   1. Seasonal difference:  wₜ = yₜ − yₜ₋₇            (weekly clinic cycle)
 *   2. First difference:     zₜ = wₜ − wₜ₋₁            (the "Integrated" part)
 *   3. ARMA(p, q) on z:      grid p,q ≤ 2, Hannan–Rissanen
 *                            two-stage OLS, best order picked by AIC
 *   4. Forecast recursively in z, then inverse-difference
 *      (cumulative reconstruction) back to daily visits.
 *
 * Degenerate inputs (a very short series, or an all-zero clinic) degrade
 * gracefully to a seasonal-naive forecast: repeat the last observed week.
 */
class ArimaStrategy implements ForecastStrategy
{
    private const SEASON = 7;

    private const MAX_ORDER = 2;

    private const EPS = 1e-10;

    /** @var float[] raw historical daily values, 0-based timeline */
    private array $values = [];

    /** @var float[] seasonal differences w; w[i] ↔ original t = i + SEASON */
    private array $w = [];

    /** @var float[] first differences of w; z[j] ↔ original t = j + SEASON + 1 */
    private array $z = [];

    /** ARMA intercept — expected drift of the differenced series. */
    private float $intercept = 0.0;

    /** @var float[] AR weights φ, index = lag (1-based) */
    private array $phi = [];

    /** @var float[] MA weights θ, index = lag (1-based) */
    private array $theta = [];

    /** @var array<int, float> one-step residuals of the chosen model, keyed by original t */
    private array $residuals = [];

    private float $sigma = 0.0;

    private int $orderP = 0;

    private int $orderQ = 0;

    private bool $degenerate = false;

    public function name(): string
    {
        return 'arima';
    }

    public function fit(array $values): void
    {
        $this->reset();

        $this->values = array_map('floatval', array_values($values));
        $n = count($this->values);

        if ($n < self::SEASON * 2) {
            $this->degenerate = true;

            return;
        }

        // ── Steps 1+2: seasonal + first difference ("Integrated") ──
        $this->buildDifferencedSeries();

        if (count($this->z) < 8) {
            $this->degenerate = true;

            return;
        }

        // Constant differences (e.g. an entirely empty clinic): the best
        // ARIMA collapses to a zero-drift walk → repeat the last week.
        if (self::variance($this->z) < self::EPS) {
            $this->intercept = self::mean($this->z);

            return;
        }

        $this->fitArma();
    }

    /**
     * Order (p, d, q) of the fitted model — handy for console/debug output.
     */
    public function order(): string
    {
        return sprintf('ARIMA(%d,1,%d)(0,1,0)%d', $this->orderP, $this->orderQ, self::SEASON);
    }

    public function forecast(int $horizon): array
    {
        if ($this->degenerate) {
            return $this->seasonalNaiveForecast($horizon);
        }

        $n = count($this->values);

        /** @var array<int, float> $zFuture predicted z beyond n−1, keyed by t */
        $zFuture = [];
        /** @var array<int, float> $wFuture reconstructed w beyond n−1, keyed by t */
        $wFuture = [];
        /** @var array<int, float> $yFuture reconstructed visits beyond n−1, keyed by t */
        $yFuture = [];

        $predictions = [];

        for ($h = 1; $h <= $horizon; $h++) {
            $t = $n + $h - 1;

            // ── ARMA recursion in the differenced space ──
            // Past z values are taken as-is; past MA terms are the in-sample
            // residuals; future residuals are zero-mean → 0.0.
            $zHat = $this->intercept;

            for ($i = 1; $i <= $this->orderP; $i++) {
                $zHat += $this->phi[$i] * $this->zAt($t - $i, $zFuture);
            }

            for ($j = 1; $j <= $this->orderQ; $j++) {
                $zHat += $this->theta[$j] * ($this->residuals[$t - $j] ?? 0.0);
            }

            $zFuture[$t] = $zHat;

            // ── Inverse first difference: wₜ = wₜ₋₁ + zₜ ──
            $wHat = $this->inverseFirstDifference($t, $wFuture, $zHat);
            $wFuture[$t] = $wHat;

            // ── Inverse seasonal difference: yₜ = wₜ + yₜ₋₇ ──
            $yHat = max(0.0, $wHat + $this->yAt($t - self::SEASON, $yFuture));
            $yFuture[$t] = $yHat;

            $predictions[] = $yHat;
        }

        return $predictions;
    }

    public function sigma(): float
    {
        return $this->sigma;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Fitting internals
    // ────────────────────────────────────────────────────────────────────────

    private function reset(): void
    {
        $this->values = [];
        $this->w = [];
        $this->z = [];
        $this->intercept = 0.0;
        $this->phi = [];
        $this->theta = [];
        $this->residuals = [];
        $this->sigma = 0.0;
        $this->orderP = 0;
        $this->orderQ = 0;
        $this->degenerate = false;
    }

    /**
     * Seasonal difference (lag 7) then first difference — the "integrated"
     * part of the model. Dense arrays align as: w[i] ↔ t = i+7, z[j] ↔ t = j+8.
     */
    private function buildDifferencedSeries(): void
    {
        $season = self::SEASON;
        $n = count($this->values);

        $this->w = [];
        for ($t = $season; $t < $n; $t++) {
            $this->w[] = $this->values[$t] - $this->values[$t - $season];
        }

        $this->z = [];
        $last = count($this->w) - 1;
        for ($i = 0; $i < $last; $i++) {
            $this->z[] = $this->w[$i + 1] - $this->w[$i];
        }
    }

    /**
     * Hannan–Rissanen estimation:
     *   stage 1 — fit a long AR to bootstrap residual estimates,
     *   stage 2 — regress z on its own lags + lagged residuals for every
     *             candidate (p, q), pick the lowest AIC.
     */
    private function fitArma(): void
    {
        $nz = count($this->z);
        $pLong = min(4, max(1, intdiv($nz, 4)));

        $phiLong = self::yuleWalker($this->z, $pLong);
        $warmUp = max(1, count($phiLong));

        // Stage-1 residuals over the dense z indexes: j < warmUp unusable.
        $stageOne = array_fill(0, $warmUp, 0.0);
        for ($j = $warmUp; $j < $nz; $j++) {
            $error = $this->z[$j];

            foreach ($phiLong as $lag => $weight) {
                $error -= $weight * $this->z[$j - $lag];
            }

            $stageOne[] = $error;
        }

        $best = null;
        $bestAic = INF;

        foreach (range(0, self::MAX_ORDER) as $p) {
            foreach (range(0, self::MAX_ORDER) as $q) {
                $candidate = $this->estimateArma($p, $q, $stageOne, $warmUp);

                if ($candidate === null) {
                    continue;
                }

                if ($candidate['aic'] < $bestAic) {
                    $bestAic = $candidate['aic'];
                    $best = $candidate;
                }
            }
        }

        if ($best === null) {
            // Nothing estimable: drift-only ARIMA(0, 1, 0).
            $this->intercept = self::mean($this->z);

            return;
        }

        $this->intercept = $best['intercept'];
        $this->phi = $best['phi'];
        $this->theta = $best['theta'];
        $this->residuals = $best['residuals'];
        $this->orderP = $best['p'];
        $this->orderQ = $best['q'];
        $this->sigma = $best['sigma'];
    }

    /**
     * Stage-2 OLS regression for one (p, q) candidate, plus the resulting
     * in-sample residuals and a visitor-space sigma (confidence band input).
     *
     * Rows regress zₜ on [1, zₜ₋₁..zₜ₋ₚ, eₜ₋₁..eₜ₋ₒ], where e are the
     * stage-1 bootstrap residuals (available from dense index warmUp).
     *
     * @param array<int, float> $stageOne
     * @return array{intercept: float, phi: array<float>, theta: array<float>,
     *               residuals: array<int, float>, aic: float, p: int, q: int,
     *               sigma: float}|null
     */
    private function estimateArma(int $p, int $q, array $stageOne, int $warmUp): ?array
    {
        $nz = count($this->z);
        $n = count($this->values);

        $start = max($p, $warmUp + $q);
        $rowCount = $nz - $start;

        if ($rowCount < $p + $q + 3) {
            return null;
        }

        $k = 1 + $p + $q;
        $a = [];
        $b = [];
        for ($i = 0; $i < $k; $i++) {
            $a[$i] = array_fill(0, $k, 0.0);
            $b[$i] = 0.0;
        }

        // Normal equations: XᵀX · coef = Xᵀy
        for ($j = $start; $j < $nz; $j++) {
            $xs = [1.0];

            for ($lag = 1; $lag <= $p; $lag++) {
                $xs[] = $this->z[$j - $lag];
            }

            for ($lag = 1; $lag <= $q; $lag++) {
                $xs[] = $stageOne[$j - $lag];
            }

            $target = $this->z[$j];

            for ($x1 = 0; $x1 < $k; $x1++) {
                $b[$x1] += $xs[$x1] * $target;

                for ($x2 = 0; $x2 < $k; $x2++) {
                    $a[$x1][$x2] += $xs[$x1] * $xs[$x2];
                }
            }
        }

        for ($i = 0; $i < $k; $i++) {
            $a[$i][$i] += 1e-9; // tiny ridge for numerical stability
        }

        $coefs = $this->solveNormal($a, $b, $k);

        if ($coefs === null) {
            return null;
        }

        // ── In-sample residuals (keyed by original t) + visitor-space sigma ──
        $ssr = 0.0;
        $sigmaSsr = 0.0;
        $residuals = [];

        for ($j = $start; $j < $nz; $j++) {
            $pred = $coefs[0];

            for ($lag = 1; $lag <= $p; $lag++) {
                $pred += $coefs[$lag] * $this->z[$j - $lag];
            }

            for ($lag = 1; $lag <= $q; $lag++) {
                $pred += $coefs[$p + $lag] * $stageOne[$j - $lag];
            }

            $error = $this->z[$j] - $pred;
            $ssr += $error * $error;
            $residuals[$j + self::SEASON + 1] = $error;

            // One-step fitted visits: ŷₜ = wₜ₋₁ + ẑₜ + yₜ₋₇
            $wFit = $this->w[$j] + $pred;
            $yFit = $wFit + $this->values[$j + 1];
            $visitError = $this->values[$j + self::SEASON + 1] - $yFit;
            $sigmaSsr += $visitError * $visitError;
        }

        $variance = $ssr / $rowCount;

        if ($variance < self::EPS) {
            return null; // degenerate fit — reject rather than cry ln(0)
        }

        $sigma = sqrt($sigmaSsr / $rowCount);

        $phi = [];
        for ($lag = 1; $lag <= $p; $lag++) {
            $phi[$lag] = $coefs[$lag];
        }

        $theta = [];
        for ($lag = 1; $lag <= $q; $lag++) {
            $theta[$lag] = $coefs[$p + $lag];
        }

        return [
            'intercept' => $coefs[0],
            'phi' => $phi,
            'theta' => $theta,
            'residuals' => $residuals,
            'aic' => $rowCount * log($variance) + 2 * $k,
            'p' => $p,
            'q' => $q,
            'sigma' => $sigma,
        ];
    }

    /**
     * Durbin–Levinson recursion solving the Yule–Walker equations for an
     * AR(order) model. Returns φ weights keyed 1..order (1-based lags).
     */
    private static function yuleWalker(array $series, int $order): array
    {
        $n = count($series);

        if ($n <= $order + 1) {
            return [];
        }

        $mean = self::mean($series);

        $cov = [];
        for ($k = 0; $k <= $order; $k++) {
            $sum = 0.0;

            for ($i = 0; $i + $k < $n; $i++) {
                $sum += ($series[$i] - $mean) * ($series[$i + $k] - $mean);
            }

            $cov[$k] = $sum / $n;
        }

        if (($cov[0] ?? 0.0) <= self::EPS) {
            return [];
        }

        $prev = [];

        for ($step = 1; $step <= $order; $step++) {
            $num = $cov[$step];
            $den = $cov[0];

            for ($j = 1; $j < $step; $j++) {
                $num -= $prev[$j] * $cov[$step - $j];
                $den -= $prev[$j] * $cov[$j];
            }

            if ($den <= self::EPS) {
                break;
            }

            $phiStep = max(-1.0, min(1.0, $num / $den));

            $next = [];
            for ($j = 1; $j < $step; $j++) {
                $next[$j] = $prev[$j] - $phiStep * $prev[$step - $j];
            }
            $next[$step] = $phiStep;
            ksort($next);

            $prev = $next;
        }

        return $prev;
    }

    /**
     * Solve AX = b by Gaussian elimination with partial pivoting.
     * Returns null when the system is numerically singular.
     */
    private function solveNormal(array $matrix, array $vector, int $k): ?array
    {
        for ($col = 0; $col < $k; $col++) {
            $pivotRow = $col;
            $pivotVal = abs($matrix[$col][$col]);

            for ($row = $col + 1; $row < $k; $row++) {
                $value = abs($matrix[$row][$col]);

                if ($value > $pivotVal) {
                    $pivotVal = $value;
                    $pivotRow = $row;
                }
            }

            if ($pivotVal < 1e-12) {
                return null;
            }

            if ($pivotRow !== $col) {
                [$matrix[$col], $matrix[$pivotRow]] = [$matrix[$pivotRow], $matrix[$col]];
                [$vector[$col], $vector[$pivotRow]] = [$vector[$pivotRow], $vector[$col]];
            }

            for ($row = $col + 1; $row < $k; $row++) {
                $factor = $matrix[$row][$col] / $matrix[$col][$col];

                for ($c = $col; $c < $k; $c++) {
                    $matrix[$row][$c] -= $factor * $matrix[$col][$c];
                }

                $vector[$row] -= $factor * $vector[$col];
            }
        }

        $solution = array_fill(0, $k, 0.0);

        for ($row = $k - 1; $row >= 0; $row--) {
            $sum = $vector[$row];

            for ($c = $row + 1; $c < $k; $c++) {
                $sum -= $matrix[$row][$c] * $solution[$c];
            }

            $solution[$row] = $sum / $matrix[$row][$row];
        }

        return $solution;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Forecast helpers (original 0-based t timeline)
    // ────────────────────────────────────────────────────────────────────────

    /** z value at original time t: actual until n−1, recursive after. */
    private function zAt(int $t, array $zFuture): float
    {
        if ($t <= count($this->values) - 1) {
            return $this->z[$t - self::SEASON - 1];
        }

        return $zFuture[$t] ?? 0.0;
    }

    /** y value at original time t: actual until n−1, recursive after. */
    private function yAt(int $t, array $yFuture): float
    {
        if ($t < 0) {
            return 0.0;
        }

        if ($t <= count($this->values) - 1) {
            return $this->values[$t];
        }

        return $yFuture[$t] ?? 0.0;
    }

    /** w reconstruction: wₜ = wₜ₋₁ + zₜ (actual w until n−1, recursive after). */
    private function inverseFirstDifference(int $t, array $wFuture, float $zHat): float
    {
        $previous = $t - 1 <= count($this->values) - 1
            ? $this->w[$t - 1 - self::SEASON]
            : ($wFuture[$t - 1] ?? 0.0);

        return $previous + $zHat;
    }

    /** Fallback prediction: repeat the pattern of the last observed week. */
    private function seasonalNaiveForecast(int $horizon): array
    {
        $lastWeek = array_slice($this->values, -self::SEASON);

        if ($lastWeek === []) {
            return array_fill(0, $horizon, 0.0);
        }

        $predictions = [];

        for ($h = 1; $h <= $horizon; $h++) {
            $predictions[] = max(0.0, $lastWeek[($h - 1) % self::SEASON]);
        }

        return $predictions;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Small numeric helpers
    // ────────────────────────────────────────────────────────────────────────

    private static function mean(array $values): float
    {
        return $values === [] ? 0.0 : array_sum($values) / count($values);
    }

    private static function variance(array $values): float
    {
        $n = count($values);

        if ($n === 0) {
            return 0.0;
        }

        $mean = self::mean($values);
        $sum = 0.0;

        foreach ($values as $value) {
            $sum += ($value - $mean) ** 2;
        }

        return $sum / $n;
    }
}
