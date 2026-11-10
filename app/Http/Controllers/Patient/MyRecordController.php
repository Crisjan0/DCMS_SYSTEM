<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MedicalHistory;
use App\Notifications\NotificationType;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MyRecordController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * The patient's self-service health record: medical history form,
     * read-only tooth chart, and their treatment timeline.
     */
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;

        return view('patient.my-record', [
            'patient' => $patient,
            'medicalHistory' => $patient?->medicalHistory,
            'submissions' => $patient
                ? ActivityLog::query()
                    ->with('user')
                    ->where('action', 'medical_history_updated')
                    ->where('subject_type', 'Patient')
                    ->where('subject_id', $patient->id)
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
                : collect(),
            'toothRecords' => $patient
                ? $patient->toothRecords()->get()->keyBy('tooth_number')
                : collect(),
            'treatments' => $patient
                ? $patient->treatmentRecords()
                    ->with('dentist.user')
                    ->orderByDesc('treatment_date')
                    ->orderByDesc('id')
                    ->limit(30)
                    ->get()
                : collect(),
        ]);
    }

    /**
     * Patient updates their own health declaration.
     */
    public function saveMedicalHistory(Request $request): RedirectResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Complete your patient profile first before filing a health declaration.');
        }

        $data = $request->validate([
            'allergies' => ['nullable', 'string', 'max:255'],
            'medications' => ['nullable', 'string', 'max:255'],
            'other_conditions' => ['nullable', 'string', 'max:1000'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (array_keys(MedicalHistory::CONDITIONS) as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        DB::transaction(function () use ($patient, $data, $request) {
            $patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $patient->id],
                $data + [
                    'updated_by' => $request->user()->id,
                    'declared_at' => now(),
                ],
            );

            ActivityLog::record(
                'medical_history_updated',
                'Self-declared their health record (patient: '.$patient->fullname().')',
                $patient,
            );
        });

        $this->notifications->toClinic(
            NotificationType::MEDICAL_HISTORY_UPDATED,
            'Health declaration updated',
            $patient->fullname().' self-declared and updated their medical history.',
            route('clinic.patients.show', $patient, absolute: false),
        );

        return redirect()
            ->route('my-record.index')
            ->with('success', 'Health declaration saved. Thank you!');
    }
}
