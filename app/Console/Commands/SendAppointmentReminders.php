<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\NotificationType;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Day-before appointment reminders.
 *
 * Every confirmed appointment scheduled for tomorrow gets a
 * notification for the patient's user account. A dedupe key keeps
 * the run idempotent (safe against double-schedules / re-runs).
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:remind {--target= : Remind for this Y-m-d date instead of tomorrow}';

    protected $description = 'Send day-before reminders for tomorrow\'s confirmed appointments';

    public function handle(NotificationService $notifications): int
    {
        $targetDate = $this->option('target')
            ? date('Y-m-d', strtotime((string) $this->option('target')))
            : today()->addDay()->toDateString();

        $appointments = Appointment::query()
            ->with('patient.user')
            ->whereDate('scheduled_date', $targetDate)
            ->where('status', 'confirmed')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No confirmed appointments for '.$targetDate.' — nothing to remind.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($appointments as $appointment) {
            $user = $appointment->patient?->user;

            if ($user === null) {
                continue;
            }

            $dedupeKey = 'reminder-'.$targetDate.'-appoint-'.$appointment->id;

            if ($notifications->alreadySent($user, $dedupeKey)) {
                continue;
            }

            $when = $appointment->scheduled_date->format('l, M d, Y').' at '.$appointment->scheduled_time;

            $notifications->send(
                $user,
                NotificationType::APPOINTMENT_REMINDER,
                'Appointment reminder',
                'Your '.$appointment->service.' visit is scheduled tomorrow ('.$when.') at Sombilon-Sabuga Dental Clinic. Please come a little early.',
                route('appointments.index', absolute: false),
                ['dedupe_key' => $dedupeKey],
            );

            $sent++;
        }

        $this->info($sent.' of '.$appointments->count().' reminders sent for '.$targetDate.'.');

        return self::SUCCESS;
    }
}
