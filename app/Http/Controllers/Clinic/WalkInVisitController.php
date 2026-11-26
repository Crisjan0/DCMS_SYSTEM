<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\WalkInVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Walk-in visits: unbooked patients served on the spot.
 * Counted into daily clinic stats so the forecasting model
 * reflects the clinic's true daily volume.
 */
class WalkInVisitController extends Controller
{
    public function index(): View
    {
        return view('clinic.walk_ins.index', [
            'services' => Appointment::SERVICES,
            'patients' => Patient::query()
                ->where('role', 'patient')
                ->with('user')
                ->orderBy('lastname')->orderBy('firstname')
                ->get(),
            'dentists' => Dentist::query()->with('user')->get(),
            'visits' => WalkInVisit::query()
                ->with(['patient', 'dentist.user', 'handledBy'])
                ->orderByDesc('visit_date')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'todayCount' => WalkInVisit::query()->whereDate('visit_date', today())->sum('count'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['nullable', Rule::exists('patients', 'id')->where('role', 'patient')],
            'dentist_id' => ['nullable', Rule::exists('dentists', 'id')],
            'service' => ['required', Rule::in(Appointment::SERVICES)],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $visit = WalkInVisit::create($data + ['handled_by' => $request->user()->id]);

        ActivityLog::record(
            'walk_in_recorded',
            'Recorded '.$visit->count.' walk-in visit(s) ('.$visit->service.') on '.$visit->visit_date->format('M d, Y'),
            $visit,
        );

        $patient = $visit->patient_id ? Patient::find($visit->patient_id) : null;

        return back()->with(
            'success',
            'Walk-in visit recorded for '.$visit->visit_date->format('M d, Y').($patient ? ' — '.$patient->fullname() : '')
            .'. It now counts toward the daily volume forecast.'
        );
    }

    public function destroy(WalkInVisit $walkIn): RedirectResponse
    {
        $walkIn->delete();

        return back()->with('success', 'Walk-in entry removed.');
    }
}
