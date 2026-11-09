<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ForecastRun;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * Standalone forecast page: the clinic volume prediction chart
 * (shared partial with the staff dashboard) plus an on-demand
 * Regenerate action for admins/staff.
 */
class ForecastPageController extends Controller
{
    public function index(): View
    {
        $latestRun = ForecastRun::query()
            ->where('metric', 'total_visits')
            ->orderByDesc('ran_at')
            ->first();

        return view('forecast.index', [
            'showForecast' => true,
            'latestRun' => $latestRun,
        ]);
    }

    /**
     * Re-run the volume forecast on demand. A non-zero exit code
     * (e.g. not enough history) surfaces as a friendly flash
     * message instead of a stack trace.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $exitCode = Artisan::call('forecast:generate', [
            '--metric' => 'total_visits',
            '--algorithm' => 'holt_winters',
            '--horizon' => 90,
        ]);

        if ($exitCode === Command::SUCCESS) {
            return back()->with('success', 'Forecast regenerated — the chart now shows the latest prediction.');
        }

        return back()->with('error', 'Forecast could not be generated yet — the clinic needs at least 30 days of recorded visit history first.');
    }
}
