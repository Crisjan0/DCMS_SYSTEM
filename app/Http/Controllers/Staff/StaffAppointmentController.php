<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Dentist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffAppointmentController extends Controller
{
    /**
     * Pending requests (waiting for dentist assignment) + upcoming confirmed schedule.
     */
    public function index(): View
    {
        $pending = Appointment::with(['patient.user'])
            ->where('status', 'pending')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $upcoming = Appointment::with(['patient.user', 'dentist.user'])
            ->where('status', 'confirmed')
            ->whereDate('scheduled_date', '>=', today()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate(10, ['*'], 'upcoming_page')
            ->withQueryString();

        return view('staff.appointments.index', [
            'pending' => $pending,
            'upcoming' => $upcoming,
        ]);
    }

    /**
     * Assignment screen: pick a dentist free at this appointment's date + time.
     */
    public function assignForm(Appointment $appointment): View|RedirectResponse
    {
        if ($appointment->status !== 'pending') {
            return redirect()
                ->route('staff.appointments.index')
                ->with('error', 'Only pending requests can be assigned a dentist.');
        }

        // Dentists occupied by another active appointment in the same slot.
        $busyIds = Appointment::conflictingAt(
            $appointment->scheduled_date->toDateString(),
            $appointment->scheduled_time
        )
            ->where('id', '!=', $appointment->id)
            ->whereNotNull('dentist_id')
            ->pluck('dentist_id')
            ->unique();

        return view('staff.appointments.assign', [
            'appointment' => $appointment->load('patient.user'),
            'dentists' => Dentist::with('user')->orderBy('lastname')->get(),
            'busyIds' => $busyIds,
        ]);
    }

    /**
     * Assign a dentist to a pending request → status becomes confirmed.
     */
    public function assign(Request $request, Appointment $appointment): RedirectResponse
    {
        if ($appointment->status !== 'pending') {
            return redirect()
                ->route('staff.appointments.index')
                ->with('error', 'Only pending requests can be assigned a dentist.');
        }

        $data = $request->validate([
            'dentist_id' => ['required', Rule::exists('dentists', 'id')],
        ]);

        $isBusy = Appointment::conflictingAt(
            $appointment->scheduled_date->toDateString(),
            $appointment->scheduled_time
        )
            ->where('id', '!=', $appointment->id)
            ->where('dentist_id', $data['dentist_id'])
            ->exists();

        if ($isBusy) {
            return back()->withErrors([
                'dentist_id' => 'That dentist is already booked at this time slot. Pick another available dentist.',
            ]);
        }

        $dentist = Dentist::with('user')->findOrFail($data['dentist_id']);

        DB::transaction(function () use ($appointment, $dentist) {
            $appointment->update([
                'dentist_id' => $dentist->id,
                'status' => 'confirmed',
            ]);
        });

        return redirect()
            ->route('staff.appointments.index')
            ->with('success', 'Assigned Dr. '.$dentist->fullname().' — appointment confirmed.');
    }

    /**
     * Cancel a pending or confirmed appointment on behalf of the patient.
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        if (! in_array($appointment->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Only pending or confirmed appointments can be cancelled.');
        }

        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Appointment cancelled.');
    }
}
