<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\ToothRecord;
use App\Models\TreatmentRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TreatmentRecordController extends Controller
{
    /**
     * Chart a treatment for a patient, optionally from an existing visit.
     */
    public function create(Request $request, Patient $patient): View
    {
        abort_unless($patient->role === 'patient', 404);

        $appointmentId = $request->integer('appointment');

        return view('clinic.treatments.create', [
            'patient' => $patient,
            'appointment' => $appointmentId
                ? $patient->appointments()->with('dentist.user')->findOrFail($appointmentId)
                : null,
            'visitOptions' => $patient->appointments()
                ->with('dentist.user')
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->orderByDesc('scheduled_date')
                ->limit(15)
                ->get(),
            'dentists' => Dentist::with('user')->orderBy('lastname')->get(),
        ]);
    }

    /**
     * Store a treatment record. When it is charted against an
     * appointment, that appointment is marked as completed.
     */
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $data = $this->validateTreatment($request);

        $appointment = $data['appointment_id']
            ? $patient->appointments()->findOrFail($data['appointment_id'])
            : null;

        DB::transaction(function () use ($patient, $request, $data, $appointment) {
            $record = $patient->treatmentRecords()->create($data + [
                'recorded_by' => $request->user()->id,
            ]);

            // Charting from a visit closes it out.
            if ($appointment && in_array($appointment->status, ['pending', 'confirmed'], true)) {
                $appointment->update(['status' => 'completed']);

                ActivityLog::record(
                    'appointment_completed',
                    'Marked '.$patient->fullname()."'s visit (".$appointment->scheduled_date->format('M d, Y').') as completed — treatment charted',
                    $appointment,
                );
            }

            ActivityLog::record(
                'treatment_charted',
                'Charted '.$data['procedure'].($data['tooth_number'] ? ' on tooth '.$data['tooth_number'] : '').' for '.$patient->fullname(),
                $record,
            );
        });

        return redirect()
            ->route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments'])
            ->with('success', 'Treatment record charted for '.$patient->fullname().'.');
    }

    /**
     * Edit an existing treatment record.
     */
    public function edit(TreatmentRecord $treatment): View
    {
        $treatment->load(['patient.user', 'dentist.user', 'appointment']);

        return view('clinic.treatments.edit', [
            'treatment' => $treatment,
            'patient' => $treatment->patient,
            'appointment' => $treatment->appointment,
            'visitOptions' => $treatment->patient->appointments()
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->orderByDesc('scheduled_date')
                ->limit(15)
                ->get(),
            'dentists' => Dentist::with('user')->orderBy('lastname')->get(),
        ]);
    }

    /**
     * Update an existing treatment record.
     */
    public function update(Request $request, TreatmentRecord $treatment): RedirectResponse
    {
        $data = $this->validateTreatment($request);

        $appointment = $data['appointment_id'] && (int) $data['appointment_id'] !== (int) $treatment->appointment_id
            ? $treatment->patient->appointments()->findOrFail($data['appointment_id'])
            : null;

        DB::transaction(function () use ($treatment, $request, $data, $appointment) {
            $treatment->update($data + [
                'recorded_by' => $request->user()->id,
            ]);

            if ($appointment && in_array($appointment->status, ['pending', 'confirmed'], true)) {
                $appointment->update(['status' => 'completed']);

                ActivityLog::record(
                    'appointment_completed',
                    'Marked '.$treatment->patient->fullname()."'s visit (".$appointment->scheduled_date->format('M d, Y').') as completed — treatment updated',
                    $appointment,
                );
            }

            ActivityLog::record(
                'treatment_updated',
                'Updated the '.$data['procedure'].' record of '.$treatment->patient->fullname(),
                $treatment,
            );
        });

        return redirect()
            ->route('clinic.patients.show', ['patient' => $treatment->patient, 'tab' => 'treatments'])
            ->with('success', 'Treatment record updated.');
    }

    /**
     * Remove a treatment record. Existing invoice items keep their
     * snapshot description — only the link is detached.
     */
    public function destroy(TreatmentRecord $treatment): RedirectResponse
    {
        $patient = $treatment->patient;

        ActivityLog::record(
            'treatment_deleted',
            'Deleted the '.$treatment->procedure.' record of '.$patient->fullname(),
            $treatment,
        );

        $treatment->delete();

        return redirect()
            ->route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments'])
            ->with('success', 'Treatment record removed.');
    }

    /**
     * Shared validation rules for treatment records.
     */
    private function validateTreatment(Request $request): array
    {
        return $request->validate([
            'appointment_id' => ['nullable', Rule::exists('appointments', 'id')],
            'dentist_id' => ['required', Rule::exists('dentists', 'id')],
            'treatment_date' => ['required', 'date', 'before_or_equal:today'],
            'tooth_number' => ['nullable', 'string', Rule::in(array_map('strval', ToothRecord::teeth()))],
            'procedure' => ['required', 'string', 'max:150'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'treatment_notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date'],
        ]);
    }
}
