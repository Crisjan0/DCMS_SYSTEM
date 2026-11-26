<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\ForecastRun;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thesis objective #4 — administrative dashboard for real-time
 * monitoring of patient flow, staff activity, and resource allocation.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $isStaffView = $request->user()->hasRole('staff', 'admin', 'dentist');

        if (! $isStaffView) {
            return $this->patientDashboard($request);
        }

        $today = today()->toDateString();
        $totalSlots = count(Appointment::timeSlots());

        // ── Today's status breakdown ────────────────────────────────────
        $statusCounts = Appointment::query()
            ->whereDate('scheduled_date', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $metrics = [
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
            'completed' => (int) ($statusCounts['completed'] ?? 0),
            'no_show' => (int) ($statusCounts['no_show'] ?? 0),
            'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            'scheduled_today' => (int) (($statusCounts['pending'] ?? 0) + ($statusCounts['confirmed'] ?? 0) + ($statusCounts['completed'] ?? 0)),
            'new_patients' => Patient::query()
                ->where('role', 'patient')
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->count(),
            'receivable' => (float) Invoice::query()
                ->whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')
                ->value('due'),
            'collected_today' => (float) Payment::query()->whereDate('paid_at', $today)->sum('amount'),
            'collected_month' => (float) Payment::query()
                ->whereBetween('paid_at', [now()->startOfMonth(), now()])
                ->sum('amount'),
        ];

        // ── Staff activity feed (audit trail) ───────────────────────────
        $activity = ActivityLog::query()
            ->with('user')
            ->latest('id')
            ->limit(8)
            ->get();

        // ── Resource allocation: per-dentist utilization ────────────────
        $bookedToday = Appointment::query()
            ->whereDate('scheduled_date', $today)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->selectRaw('dentist_id, count(*) as bookings')
            ->groupBy('dentist_id')
            ->pluck('bookings', 'dentist_id');

        $upcomingWeek = Appointment::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('scheduled_date', '>=', $today)
            ->whereDate('scheduled_date', '<=', today()->addDays(6)->toDateString())
            ->selectRaw('dentist_id, count(*) as bookings')
            ->groupBy('dentist_id')
            ->pluck('bookings', 'dentist_id');

        $dentists = Dentist::query()
            ->with('user')
            ->orderBy('lastname')
            ->get()
            ->map(function (Dentist $dentist) use ($bookedToday, $upcomingWeek, $totalSlots) {
                $bookings = (int) ($bookedToday[$dentist->id] ?? 0);

                return [
                    'id' => $dentist->id,
                    'name' => $dentist->fullname(),
                    'bookings_today' => $bookings,
                    'utilization' => $bookings >= $totalSlots ? 100 : round($bookings / $totalSlots * 100),
                    'busy_slots' => max(0, min($totalSlots, $bookings)),
                    'free_slots' => max(0, $totalSlots - $bookings),
                    'week_bookings' => (int) ($upcomingWeek[$dentist->id] ?? 0),
                ];
            });

        // ── Forecast snapshot (latest run) ──────────────────────────────
        $latestRun = ForecastRun::query()->latest('id')->first();
        $forecastNext7 = $latestRun
            ? (float) $latestRun->points()
                ->whereBetween('date', [$today, today()->addDays(6)->toDateString()])
                ->sum('predicted')
            : null;

        return view('dashboard', [
            'staffView' => true,
            'metrics' => $metrics,
            'activity' => $activity,
            'dentists' => $dentists,
            'totalSlots' => $totalSlots,
            'forecastNext7' => $forecastNext7,
        ]);
    }

    /**
     * Simple daily-use view for patients: their next visit, balance,
     * and quick actions.
     */
    private function patientDashboard(Request $request): View
    {
        $patient = $request->user()->patient;

        $nextAppointment = $patient?->appointments()
            ->with('dentist.user')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('scheduled_date', '>=', today())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->first();

        $outstanding = $patient
            ? (float) $patient->invoices()
                ->whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')
                ->value('due')
            : 0.0;

        $completedVisits = $patient
            ? $patient->appointments()->where('status', 'completed')->count()
            : 0;

        return view('dashboard', [
            'staffView' => false,
            'nextAppointment' => $nextAppointment,
            'outstanding' => $outstanding,
            'completedVisits' => $completedVisits,
        ]);
    }
}
