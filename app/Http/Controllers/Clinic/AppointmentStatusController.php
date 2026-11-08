<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentStatusController extends Controller
{
    /**
     * Admin/staff/dentist mark a finished visit as completed or a
     * missed visit as a no-show (the enum's unused statuses).
     */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:completed,no_show'],
        ]);

        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true)) {
            return back()->with('error', 'This appointment was already closed.');
        }

        // Can't close a visit that hasn't happened yet.
        if ($appointment->scheduled_date->greaterThan(today())) {
            return back()->with('error', 'Future appointments can only be closed on or after their date.');
        }

        $label = $data['status'] === 'completed' ? 'completed' : 'marked as no-show';

        $appointment->update(['status' => $data['status']]);

        ActivityLog::record(
            $data['status'] === 'completed' ? 'appointment_completed' : 'appointment_no_show',
            'Marked '.$appointment->patient?->fullname()."'s ".$appointment->service.' visit ('.$appointment->scheduled_date->format('M d, Y').') as '.$label,
            $appointment,
        );

        return back()
            ->with('success', 'Appointment '.$label.'.');
    }
}
