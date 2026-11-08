<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Searchable list of registered patients (staff, admin, dentists).
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));

        $patients = Patient::query()
            ->where('role', 'patient')
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('lastname', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"));
                });
            })
            ->withCount(['appointments as visits_count' => fn ($query) => $query->where('status', 'completed')])
            ->withCount(['appointments as upcoming_count' => fn ($query) => $query
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('scheduled_date', '>=', today()->toDateString()),
            ])
            ->withCount('toothRecords')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate(10)
            ->withQueryString();

        return view('clinic.patients.index', [
            'patients' => $patients,
            'search' => $search,
        ]);
    }

    /**
     * A patient's full clinic record, organized as tabs:
     * overview · medical history · tooth chart · treatments · billing.
     */
    public function show(Request $request, Patient $patient): View
    {
        abort_unless($patient->role === 'patient', 404);

        $tab = (string) $request->string('tab');
        $tab = in_array($tab, ['overview', 'medical-history', 'tooth-chart', 'treatments', 'billing'], true)
            ? $tab
            : 'overview';

        $today = today()->toDateString();

        return view('clinic.patients.show', [
            'patient' => $patient->load('user'),
            'tab' => $tab,
            'medicalHistory' => $patient->medicalHistory,
            'toothRecords' => $patient->toothRecords->keyBy('tooth_number'),
            'treatments' => $patient->treatmentRecords()
                ->with(['dentist.user', 'invoiceItems'])
                ->orderByDesc('treatment_date')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'upcoming' => $patient->appointments()
                ->with('dentist.user')
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('scheduled_date', '>=', $today)
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get(),
            'history' => $patient->appointments()
                ->with('dentist.user')
                ->whereIn('status', ['completed', 'cancelled', 'no_show'])
                ->whereDate('scheduled_date', '<', $today)
                ->orderByDesc('scheduled_date')
                ->orderByDesc('scheduled_time')
                ->limit(8)
                ->get(),
            'invoices' => $patient->invoices()->latest('id')->limit(6)->get(),
            'outstanding' => (float) $patient->invoices()
                ->whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')
                ->value('due'),
        ]);
    }

    /**
     * Staff/dentist version of the health declaration form.
     */
    public function storeMedicalHistory(Request $request, Patient $patient): RedirectResponse
    {
        $data = $this->validateMedicalHistory($request);

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
                'Updated the health declaration of '.$patient->fullname(),
                $patient,
            );
        });

        return redirect()
            ->route('clinic.patients.show', ['patient' => $patient, 'tab' => 'medical-history'])
            ->with('success', 'Medical history saved for '.$patient->fullname().'.');
    }

    /**
     * Shared validation rules for the health declaration.
     */
    private function validateMedicalHistory(Request $request): array
    {
        $data = $request->validate([
            'allergies' => ['nullable', 'string', 'max:255'],
            'medications' => ['nullable', 'string', 'max:255'],
            'other_conditions' => ['nullable', 'string', 'max:1000'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (array_keys(MedicalHistory::CONDITIONS) as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
