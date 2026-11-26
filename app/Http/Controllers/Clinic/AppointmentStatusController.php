<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Notifications\NotificationType;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentStatusController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

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

        $when = $appointment->scheduled_date->format('M d, Y').' at '.$appointment->scheduled_time;

        if ($data['status'] === 'completed') {
            $this->notifications->toPatient(
                $appointment->patient,
                NotificationType::APPOINTMENT_COMPLETED,
                'Visit completed',
                'Your '.$appointment->service.' visit on '.$when.' was completed. Charted treatments now appear in your health record.',
                route('my-record.index', absolute: false),
            );
        } else {
            $this->notifications->toPatient(
                $appointment->patient,
                NotificationType::APPOINTMENT_NO_SHOW,
                'Visit recorded as no-show',
                'Your '.$appointment->service.' visit on '.$when.' was recorded as a no-show. You can book a new slot anytime.',
                route('appointments.index', absolute: false),
            );
        }

        return back()
            ->with('success', 'Appointment '.$label.'.');
    }
}
