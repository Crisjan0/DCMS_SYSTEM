<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pollable JSON feed of today's patient flow, consumed by the
 * admin dashboard timeline (auto-refresh every 30 seconds).
 */
class FlowController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['patient', 'dentist.user'])
            ->whereDate('scheduled_date', today())
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();

        return response()->json([
            'generated_at' => now()->format('g:i:s A'),
            'summary' => [
                'total' => $appointments->count(),
                'pending' => $appointments->where('status', 'pending')->count(),
                'confirmed' => $appointments->where('status', 'confirmed')->count(),
                'completed' => $appointments->where('status', 'completed')->count(),
                'no_show' => $appointments->where('status', 'no_show')->count(),
                'cancelled' => $appointments->where('status', 'cancelled')->count(),
            ],
            'appointments' => $appointments->map(fn (Appointment $appointment) => [
                'time' => substr($appointment->scheduled_time, 0, 5),
                'patient' => $appointment->patient?->fullname(),
                'service' => $appointment->service,
                'dentist' => $appointment->dentist ? 'Dr. '.$appointment->dentist->fullname() : null,
                'status' => $appointment->status,
                'status_label' => str_replace('_', ' ', ucfirst($appointment->status)),
            ]),
        ]);
    }
}
