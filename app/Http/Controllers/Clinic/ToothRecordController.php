<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\ToothRecord;
use App\Notifications\NotificationType;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToothRecordController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Upsert one tooth on the patient's odontogram.
     * Marking a tooth "healthy" removes its record from the chart.
     */
    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'tooth_number' => ['required', 'string', Rule::in(array_map('strval', ToothRecord::teeth()))],
            'status' => ['required', Rule::in(['healthy', ...ToothRecord::STATUSES])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['status'] === 'healthy') {
            $patient->toothRecords()
                ->where('tooth_number', $data['tooth_number'])
                ->delete();

            $description = 'Marked tooth '.$data['tooth_number'].' as healthy on '.$patient->fullname()."'s chart";
        } else {
            ToothRecord::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'tooth_number' => $data['tooth_number'],
                ],
                [
                    'status' => $data['status'],
                    'note' => $data['note'] ?? null,
                ],
            );

            $label = ToothRecord::STATUS_META[$data['status']]['label'] ?? $data['status'];

            $description = 'Charted tooth '.$data['tooth_number'].' ('.$label.') on '.$patient->fullname()."'s odontogram";
        }

        ActivityLog::record('tooth_chart_updated', $description, $patient);

        $this->notifications->toPatient(
            $patient,
            NotificationType::TOOTH_CHART_UPDATED,
            'Tooth chart updated',
            $data['status'] === 'healthy'
                ? 'Tooth #'.$data['tooth_number'].' on your odontogram was marked healthy by the clinic.'
                : 'Tooth #'.$data['tooth_number'].' on your odontogram is now "'.($label ?? $data['status']).'".',
            route('my-record.index', absolute: false),
        );

        return redirect()
            ->route('clinic.patients.show', ['patient' => $patient, 'tab' => 'tooth-chart'])
            ->with('success', 'Tooth #'.$data['tooth_number'].' updated.');
    }
}
