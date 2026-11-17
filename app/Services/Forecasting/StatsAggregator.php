<?php

namespace App\Services\Forecasting;

use App\Models\Appointment;
use App\Models\DailyClinicStat;
use App\Models\WalkInVisit;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportsCarbon;

/**
 * Rebuilds the daily time series table from raw appointments and walk-ins.
 * One row per clinic day: visits (booked + walk-ins), new patients,
 * no-shows, cancellations.
 */
class StatsAggregator
{
    public const METRICS = ['total_visits', 'walk_ins', 'new_patients', 'no_shows', 'cancellations'];

    /**
     * @return int number of days written
     */
    public function rebuild(): int
    {
        $upTo = now()->subDay()->endOfDay()->toDateTimeString();

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

            $stats[$date] ??= ['total_visits' => 0, 'walk_ins' => 0, 'no_shows' => 0, 'cancellations' => 0];

            match ($row->status) {
                'completed' => $stats[$date]['total_visits'] += (int) $row->cnt,
                'no_show' => $stats[$date]['no_shows'] += (int) $row->cnt,
                'cancelled' => $stats[$date]['cancellations'] += (int) $row->cnt,
                default => null,
            };
        }

        // Walk-ins served without a booking count toward real daily volume
        $walkInRows = WalkInVisit::query()
            ->selectRaw('visit_date, COALESCE(SUM(count), 0) as cnt')
            ->groupBy('visit_date')
            ->get();

        foreach ($walkInRows as $row) {
            $date = $this->dateString($row->visit_date);

            $stats[$date] ??= ['total_visits' => 0, 'walk_ins' => 0, 'no_shows' => 0, 'cancellations' => 0];
            $stats[$date]['walk_ins'] += (int) $row->cnt;
            $stats[$date]['total_visits'] += (int) $row->cnt;
        }

        $dates = array_unique(array_merge(array_keys($stats), array_keys($newPatients)));
        sort($dates);

        foreach ($dates as $date) {
            DailyClinicStat::updateOrCreate(
                ['date' => $date],
                ($stats[$date] ?? ['total_visits' => 0, 'walk_ins' => 0, 'no_shows' => 0, 'cancellations' => 0])
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

        // Some grammars (SQLite) return "Y-m-d 00:00:00" for date expressions —
        // normalize to a bare Y-m-d key so one calendar day stays one key.
        $string = (string) $value;

        return preg_match('/^\d{4}-\d{2}-\d{2}/', $string) === 1
            ? substr($string, 0, 10)
            : $string;
    }
}
