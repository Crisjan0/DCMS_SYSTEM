<?php

namespace App\Console\Commands;

use App\Models\ForecastRun;
use App\Services\Forecasting\ForecastEngine;
use App\Services\Forecasting\HistoricalDataCollector;
use App\Services\Forecasting\StatsAggregator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateForecast extends Command
{
    protected $signature = 'forecast:generate
                            {--metric=total_visits : Which series to predict (total_visits, new_patients, no_shows, cancellations)}
                            {--algorithm=holt_winters : Forecast model (holt_winters, moving_average)}
                            {--horizon=90 : How many days ahead to predict}
                            {--history=3 : How many years of history to learn from}';

    protected $description = 'Analyze historical patient records and forecast appointment trends, seasonal spikes and clinic volume';

    public function handle(): int
    {
        $metric = (string) $this->option('metric');
        $algorithm = (string) $this->option('algorithm');
        $horizon = max(7, (int) $this->option('horizon'));
        $historyYears = max(1, (int) $this->option('history'));

        if (! in_array($metric, StatsAggregator::METRICS, true)) {
            $this->error('Unknown metric. Pick one of: '.implode(', ', StatsAggregator::METRICS));

            return self::FAILURE;
        }

        if (! isset(ForecastEngine::ALGORITHMS[$algorithm])) {
            $this->error('Unknown algorithm. Pick one of: '.implode(', ', array_keys(ForecastEngine::ALGORITHMS)));

            return self::FAILURE;
        }

        // ── 1. Rebuild the aggregated time series table ──
        $days = app(StatsAggregator::class)->rebuild();
        $this->info("✔ Rebuilt daily_clinic_stats for {$days} days.");

        // ── 2. Collect the historical series ──
        $series = app(HistoricalDataCollector::class)->collect($metric, $historyYears);

        if (count($series) < 30) {
            $this->error('Not enough history to forecast yet (need at least 30 days of stat rows).');

            return self::FAILURE;
        }

        $result = (new ForecastEngine($algorithm))->run($series, $horizon);
        $this->line("Trained {$result->algorithm} on {$result->historyDays} days of history…");

        // ── 3. Persist run + predicted points ──
        $run = DB::transaction(function () use ($metric, $result) {
            $forecastRun = ForecastRun::create([
                'metric' => $metric,
                'algorithm' => $result->algorithm,
                'horizon_days' => $result->horizonDays,
                'history_days' => $result->historyDays,
                'mape' => $result->mape,
                'ran_at' => now(),
            ]);

            $rows = array_map(fn (array $point) => [
                'forecast_run_id' => $forecastRun->id,
                'date' => $point['date']->toDateString(),
                'predicted' => $point['predicted'],
                'lower' => $point['lower'],
                'upper' => $point['upper'],
                'is_peak' => $point['is_peak'],
                'created_at' => now(),
                'updated_at' => now(),
            ], array_values($result->points));

            foreach (array_chunk($rows, 500) as $chunk) {
                $forecastRun->points()->insert($chunk);
            }

            return $forecastRun;
        });

        // ── 4. Console report ──
        $this->newLine();
        $this->info("Forecast generated — run #{$run->id}");
        $this->line("  Algorithm      : {$result->algorithm}");
        $this->line("  History used   : {$result->historyDays} days");
        $this->line('  Backtest MAPE  : '.($result->mape !== null ? round($result->mape, 2).'%' : 'n/a'));
        $this->line('  Next 7 days    : ~'.(int) round($result->sumForNextDays(7)).' visits');
        $this->line('  Next 30 days   : ~'.(int) round($result->sumForNextDays(30)).' visits');
        $this->line("  Metric         : {$metric}");

        // ── 5. Spike days ──
        $average = $result->averagePrediction();
        $peaks = collect($result->peaks())
            ->sortByDesc('predicted')
            ->take(15)
            ->all();

        if ($peaks !== []) {
            $this->newLine();
            $this->info('⚡ Predicted seasonal spikes (busiest days):');

            $table = array_map(fn (array $point) => [
                $point['date']->format('M d, Y'),
                '+'.round((($point['predicted'] / max(0.1, $average)) - 1) * 100).'%',
                number_format($point['predicted'], 1).' visits',
                number_format($point['lower'], 1).' – '.number_format($point['upper'], 1),
            ], $peaks);

            $this->table(['Date', 'vs normal', 'Predicted', 'Range'], $table);
        } else {
            $this->line('No unusually busy days predicted in this horizon.');
        }

        return self::SUCCESS;
    }
}
