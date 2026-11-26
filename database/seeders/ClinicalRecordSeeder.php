<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo clinical data, anchored on the completed visits seeded by
 * AppointmentSeeder: health declarations, odontogram findings,
 * treatment records, and demo invoices/payments.
 */
class ClinicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first() ?? User::first();
        $staff = User::whereHas('patient', fn ($query) => $query->where('role', 'staff'))->first() ?? $admin;

        $patients = Patient::query()
            ->where('role', 'patient')
            ->whereHas('user') // patient profile is linked to a login account
            ->orderBy('id')
            ->limit(5)
            ->get();

        // ── Health declarations ─────────────────────────────────────────
        $histories = [
            ['allergies' => 'Penicillin, Latex', 'hypertension' => true],
            ['medications' => 'Metformin 500mg', 'diabetes' => true],
            ['asthma' => true, 'smoker' => true],
            ['allergies' => 'None', 'pregnant' => false],
            [],
        ];

        foreach ($patients as $index => $patient) {
            $data = $histories[$index % count($histories)];

            foreach (array_keys(MedicalHistory::CONDITIONS) as $flag) {
                $data[$flag] = (bool) ($data[$flag] ?? false);
            }

            $patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $patient->id],
                $data + [
                    'updated_by' => $admin?->id,
                    'declared_at' => now()->subDays(3 + $index),
                ],
            );
        }

        // ── Odontogram findings ─────────────────────────────────────────
        $toothFindings = [
            [
                ['tooth_number' => '16', 'status' => 'caries', 'note' => 'Deep occlusal decay, needs RCT evaluation'],
                ['tooth_number' => '26', 'status' => 'filled', 'note' => null],
            ],
            [
                ['tooth_number' => '36', 'status' => 'root_canal', 'note' => 'Completed 2 visits ago'],
                ['tooth_number' => '11', 'status' => 'caries', 'note' => 'Small mesial cavity'],
            ],
            [
                ['tooth_number' => '48', 'status' => 'missing', 'note' => 'Extracted years ago'],
                ['tooth_number' => '17', 'status' => 'crown', 'note' => 'Porcelain crown'],
            ],
        ];

        foreach ($patients->take(3) as $index => $patient) {
            foreach ($toothFindings[$index] as $finding) {
                $patient->toothRecords()->updateOrCreate(
                    ['tooth_number' => $finding['tooth_number']],
                    ['status' => $finding['status'], 'note' => $finding['note']],
                );
            }
        }

        // ── Treatment records from seeded completed visits ──────────────
        $visits = Appointment::query()
            ->where('status', 'completed')
            ->whereNotNull('dentist_id')
            ->with('dentist')
            ->orderByDesc('scheduled_date')
            ->get();

        $usedPerPatient = [];
        $counter = 0;

        foreach ($visits as $visit) {
            if (($usedPerPatient[$visit->patient_id] ?? 0) >= 2 || $counter >= 10) {
                continue;
            }

            $usedPerPatient[$visit->patient_id] = ($usedPerPatient[$visit->patient_id] ?? 0) + 1;
            $counter++;

            $treatment = TreatmentRecord::create([
                'patient_id' => $visit->patient_id,
                'dentist_id' => $visit->dentist_id,
                'appointment_id' => $visit->id,
                'treatment_date' => $visit->scheduled_date->toDateString(),
                'tooth_number' => in_array($visit->service, ['Tooth Extraction', 'Composite Filling', 'Root Canal Treatment'], true)
                    ? ['16', '26', '36', '11'][$counter % 4]
                    : null,
                'procedure' => $visit->service,
                'diagnosis' => 'Initial exam findings',
                'treatment_notes' => 'Seeded demo record for '.$visit->service.'.',
                'recorded_by' => $admin?->id ?? $visit->dentist?->user_id,
            ]);

            // ── Demo invoices: paid / partial / unpaid mix ──────────────
            $price = Appointment::SERVICE_PRICES[$visit->service] ?? 500;

            $invoice = $treatment->patient->invoices()->create([
                'issued_by' => $staff?->id,
                'discount' => $counter % 4 === 0 ? 200 : 0,
                'notes' => 'Seeded demo billing for '.$visit->service.'.',
            ]);

            $invoice->items()->create([
                'treatment_record_id' => $treatment->id,
                'description' => $treatment->procedure.($treatment->tooth_number ? ' — Tooth '.$treatment->tooth_number : ''),
                'quantity' => 1,
                'unit_price' => $price,
            ]);

            $invoice->recalculate();

            if ($invoice->total <= 0) {
                continue;
            }

            if ($counter % 3 === 1) {
                // Fully paid in cash on the same day
                $invoice->payments()->create([
                    'amount' => $invoice->total,
                    'method' => 'cash',
                    'received_by' => $staff?->id,
                    'paid_at' => $invoice->created_at,
                ]);
            } elseif ($counter % 3 === 2 && $invoice->total >= 300) {
                // Half paid via GCash
                $invoice->payments()->create([
                    'amount' => round($invoice->total / 2, 2),
                    'method' => 'gcash',
                    'reference_no' => 'GC-DEMO'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
                    'received_by' => $staff?->id,
                    'paid_at' => $invoice->created_at,
                ]);
            }

            $invoice->recalculate();
        }

        $this->command?->info('Clinical demo data seeded: '.$patients->count().' patient profiles, '.$counter.' treatment records.');
    }
}
