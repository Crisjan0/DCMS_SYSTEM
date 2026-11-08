<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Dentist;
use Carbon\Carbon;
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

            ActivityLog::record(
                'appointment_assigned',
                'Assigned Dr. '.$dentist->fullname().' to '.$appointment->patient?->fullname()."'s ".$appointment->service.' visit on '.$appointment->scheduled_date->format('M d, Y').' at '.$appointment->scheduled_time,
                $appointment,
            );
        });

        return redirect()
            ->route('staff.appointments.index')
            ->with('success', 'Assigned Dr. '.$dentist->fullname().' — appointment confirmed.');
    }

    /**
     * Reschedule screen: move a pending/confirmed appointment to a new slot.
     */
    public function rescheduleForm(Request $request, Appointment $appointment): View|RedirectResponse
    {
        if (! $this->isReschedulable($appointment)) {
            return redirect()
                ->route('staff.appointments.index')
                ->with('error', 'Only upcoming pending or confirmed appointments can be rescheduled.');
        }

        return view('staff.appointments.reschedule', [
            'appointment' => $appointment->load(['patient.user', 'dentist.user']),
            'timeSlots' => Appointment::timeSlots(),
        ]);
    }

    /**
     * Move an appointment to a new date/time. Status and dentist stay
     * unless the chosen dentist is busy at the new slot.
     */
    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        if (! $this->isReschedulable($appointment)) {
            return redirect()
                ->route('staff.appointments.index')
                ->with('error', 'Only upcoming pending or confirmed appointments can be rescheduled.');
        }

        $data = $request->validate([
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', Rule::in(Appointment::timeSlots())],
        ]);

        $date = Carbon::parse($data['scheduled_date'])->startOfDay();
        $time = $data['scheduled_time'];

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return back()->withErrors(['scheduled_date' => 'The clinic is closed on Sundays, please pick another date.']);
        }

        if ($date->isSameDay(now()) && $time <= now()->format('H:i')) {
            return back()->withErrors(['scheduled_time' => 'This time slot has already passed — pick a later one.']);
        }

        // Clinic-wide capacity for the new slot (excluding this appointment).
        $dentistCount = Dentist::count();
        $bookedCount = Appointment::query()
            ->where('id', '!=', $appointment->id)
            ->conflictingAt($date->toDateString(), $time)
            ->count();

        if ($dentistCount === 0 || $bookedCount >= $dentistCount) {
            return back()->withErrors(['scheduled_time' => 'This time slot is fully booked — pick another slot.']);
        }

        // If a dentist is assigned, they must also be free at the new slot.
        if ($appointment->dentist_id) {
            $dentistBusy = Appointment::query()
                ->where('id', '!=', $appointment->id)
                ->conflictingAt($date->toDateString(), $time)
                ->where('dentist_id', $appointment->dentist_id)
                ->exists();

            if ($dentistBusy) {
                return back()->withErrors([
                    'scheduled_time' => 'Dr. '.$appointment->dentist?->fullname().' is already booked at that slot — pick a free slot or cancel the assignment first.',
                ]);
            }
        }

        $from = $appointment->scheduled_date->format('M d, Y').' '.$appointment->scheduled_time;

        $appointment->update([
            'scheduled_date' => $date->toDateString(),
            'scheduled_time' => $time.':00',
        ]);

        ActivityLog::record(
            'appointment_rescheduled',
            'Moved '.$appointment->patient?->fullname()."'s ".$appointment->service.' visit from '.$from.' to '.$date->format('M d, Y').' at '.$time,
            $appointment,
        );

        return redirect()
            ->route('staff.appointments.index')
            ->with('success', 'Appointment moved to '.$date->format('M d, Y').' at '.$time.'.');
    }

    private function isReschedulable(Appointment $appointment): bool
    {
        return in_array($appointment->status, ['pending', 'confirmed'], true)
            && $appointment->scheduled_date->greaterThanOrEqualTo(today());
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

        ActivityLog::record(
            'appointment_cancelled',
            'Cancelled '.$appointment->patient?->fullname()."'s ".$appointment->service.' visit on '.$appointment->scheduled_date->format('M d, Y'),
            $appointment,
        );

        return back()->with('success', 'Appointment cancelled.');
    }
}
