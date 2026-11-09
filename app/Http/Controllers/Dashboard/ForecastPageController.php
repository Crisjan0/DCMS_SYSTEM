<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ForecastRun;
use App\Services\Forecasting\ForecastEngine;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * Standalone forecast page: the clinic volume prediction chart
 * (shared partial with the staff dashboard) plus an on-demand
 * Regenerate action for admins/staff, with a model selector
 * (ARIMA / Holt-Winters) for accuracy comparison.
 */
class ForecastPageController extends Controller
{
    /** Human-friendly model names shown to the user. */
    private const LABELS = [
        'arima' => 'ARIMA',
        'holt_winters' => 'Holt-Winters',
    ];

    public function index(): View
    {
        $latestRun = ForecastRun::query()
            ->where('metric', 'total_visits')
            ->orderByDesc('ran_at')
            ->first();

        return view('forecast.index', [
            'showForecast' => true,
            'latestRun' => $latestRun,
            'algorithms' => array_keys(ForecastEngine::ALGORITHMS),
            'labels' => self::LABELS,
        ]);
    }

    /**
     * Re-run the volume forecast on demand. A non-zero exit code
     * (e.g. not enough history) surfaces as a friendly flash
     * message instead of a stack trace.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $algorithm = (string) $request->input('algorithm', 'arima');

        if (! array_key_exists($algorithm, ForecastEngine::ALGORITHMS)) {
            $algorithm = 'arima';
        }

        $exitCode = Artisan::call('forecast:generate', [
            '--metric' => 'total_visits',
            '--algorithm' => $algorithm,
            '--horizon' => 90,
        ]);

        $label = self::LABELS[$algorithm] ?? $algorithm;

        if ($exitCode === Command::SUCCESS) {
            return back()->with("success", "{$label} forecast regenerated — the chart now shows the latest prediction.");
        }

        return back()->with('error', "The {$label} forecast could not be generated yet — the clinic needs at least 30 days of recorded visit history first.");
    }
}
