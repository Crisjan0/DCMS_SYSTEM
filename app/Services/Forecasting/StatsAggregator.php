<?php

namespace App\Services\Forecasting;

use App\Models\Appointment;
use App\Models\DailyClinicStat;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportsCarbon;

/**
 * Rebuilds the daily time series table from raw appointments.
 * One row per clinic day: visits, new patients, no-shows, cancellations.
 */
class StatsAggregator
{
    public const METRICS = ['total_visits', 'new_patients', 'no_shows', 'cancellations'];

    /**
     * @return int number of days written
     */
    public function rebuild(): int
    {
        $upTo = now()->subDay()->toDateString();

        // Per-day, per-status counts from raw appointments
        $counts = Appointment::query()
            ->selectRaw('scheduled_date as date, status, count(*) as cnt')
            ->where('scheduled_date', '<=', $upTo)
            ->groupBy('scheduled_date', 'status')
            ->get();

        // First-ever appointment per patient → "new patient" that day
        $newPatientRows = Appointment::query()
            ->selectRaw('patient_id, min(scheduled_date) as first_date')
            ->groupBy('patient_id')
            ->get();

        $newPatients = [];
        foreach ($newPatientRows as $row) {
            $date = $this->dateString($row->first_date);
            $newPatients[$date] = ($newPatients[$date] ?? 0) + 1;
        }

        $stats = [];
        foreach ($counts as $row) {
            $date = $this->dateString($row->date);

            $stats[$date] ??= ['total_visits' => 0, 'no_shows' => 0, 'cancellations' => 0];

            match ($row->status) {
                'completed' => $stats[$date]['total_visits'] += (int) $row->cnt,
                'no_show' => $stats[$date]['no_shows'] += (int) $row->cnt,
                'cancelled' => $stats[$date]['cancellations'] += (int) $row->cnt,
                default => null,
            };
        }

        $dates = array_unique(array_merge(array_keys($stats), array_keys($newPatients)));
        sort($dates);

        foreach ($dates as $date) {
            DailyClinicStat::updateOrCreate(
                ['date' => $date],
                ($stats[$date] ?? ['total_visits' => 0, 'no_shows' => 0, 'cancellations' => 0])
                    + ['new_patients' => $newPatients[$date] ?? 0]
            );
        }

        return count($dates);
    }

    private function dateString(mixed $value): string
    {
        if ($value instanceof Carbon || $value instanceof SupportsCarbon) {
            return $value->toDateString();
        }

        return (string) $value;
    }
}
