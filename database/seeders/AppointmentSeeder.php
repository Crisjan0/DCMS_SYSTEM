<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates ~3 years of realistic dental-clinic appointment history:
 *   - Weekday pattern (busy weekdays, slow Saturdays, closed Sundays)
 *   - Year-over-year growth
 *   - "Ber months" seasonal spike (Oct–Dec) + January backlog
 *   - Philippine holiday closures
 *   - Mostly completed visits in the past, pending/confirmed recently
 */
class AppointmentSeeder extends Seeder
{
    private const HOLIDAYS = [
        [1, 1],    // New Year
        [2, 25],   // EDSA
        [4, 9],    // Araw ng Kagitingan
        [5, 1],    // Labor Day
        [6, 12],   // Independence Day
        [8, 21],   // Ninoy Aquino Day
        [11, 1],   // All Saints
        [12, 25],  // Christmas
        [12, 30],  // Rizal Day
        [12, 31],  // New Year's Eve
    ];

    public function run(): void
    {
        $patientIds = DB::table('patients')->where('role', 'patient')->pluck('id')->all();
        $doctorIds = DB::table('dentists')->pluck('id')->all();

        if ($patientIds === [] || $doctorIds === []) {
            $this->command?->error('Seed PatientSeeder first.');

            return;
        }

        $services = [
            'Consultation', 'Oral Prophylaxis (Cleaning)', 'Tooth Extraction',
            'Composite Filling', 'Root Canal Treatment', 'Braces Adjustment',
            'Teeth Whitening', 'Wisdom Tooth Removal', 'Denture Fitting',
        ];

        $pastWeights = [['completed', 85], ['cancelled', 7], ['no_show', 8]];
        $recentWeights = [['completed', 30], ['confirmed', 30], ['pending', 30], ['cancelled', 5], ['no_show', 5]];

        $start = now()->subYears(3)->startOfDay();
        $end = now()->subDay()->startOfDay();

        $rows = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $day = $cursor->copy();
            $isHoliday = in_array([$day->month, $day->day], self::HOLIDAYS, true);

            // Clinic closed Sundays & holidays
            if ($day->dayOfWeek === Carbon::SUNDAY || $isHoliday) {
                $cursor->addDay();

                continue;
            }

            // Base volume with year-over-year growth
            $clinicYear = min(3, intdiv($start->diffInDays($day), 365) + 1);
            $volume = [1 => 9, 2 => 12, 3 => 15][$clinicYear];

            // Weekday pattern: slow Saturdays
            $volume *= match ($day->dayOfWeek) {
                Carbon::SATURDAY => 0.6,
                default => 1.0,
            };

            // Seasonal spikes: ber months + January backlog
            $volume *= match (true) {
                $day->month === 12 => 1.45,
                $day->month === 11 => 1.35,
                $day->month === 10 => 1.25,
                $day->month === 1 => 1.1,
                default => 1.0,
            };

            $volume = (int) min(30, round($volume * random_int(75, 125) / 100));

            // Only the final couple of days keeps unconfirmed bookings — older days
            // are naturally mostly completed, otherwise the model would "see"
            // a fake sudden drop and turn the trend negative.
            $isPastBooking = $day->copy()->addDays(2)->lessThan(now());
            $weights = $isPastBooking ? $pastWeights : $recentWeights;

            for ($i = 0; $i < $volume; $i++) {
                $rows[] = [
                    'patient_id' => $patientIds[array_rand($patientIds)],
                    'dentist_id' => $doctorIds[array_rand($doctorIds)],
                    'scheduled_date' => $day->toDateString(),
                    'scheduled_time' => sprintf('%02d:%02d:00', random_int(9, 16), random_int(0, 1) ? 30 : 0),
                    'service' => $services[array_rand($services)],
                    'notes' => null,
                    'status' => $this->weightedPick($weights),
                    'created_at' => $day->copy()->subDays(random_int(1, 10)),
                    'updated_at' => $day,
                ];
            }

            $cursor->addDay();
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('appointments')->insert($chunk);
        }

        $this->command?->info('Seeded '.count($rows).' appointments across '.$start->diffInDays($end).' days.');
    }

    /**
     * @param array<int, array{0: string, 1: int}> $weights
     */
    private function weightedPick(array $weights): string
    {
        $total = array_sum(array_column($weights, 1));
        $roll = random_int(1, max(1, $total));

        foreach ($weights as [$status, $weight]) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $status;
            }
        }

        return $weights[0][0];
    }
}
