<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * The logged-in dentist's own schedule — the patients assigned to them.
     */
    public function index(Request $request): View
    {
        $dentist = $request->user()->dentist;

        $upcoming = $dentist
            ? $dentist->appointments()
                ->with('patient.user')
                ->where('status', 'confirmed')
                ->whereDate('scheduled_date', '>=', today()->toDateString())
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->paginate(10, ['*'], 'upcoming_page')
                ->withQueryString()
            : Appointment::query()->whereRaw('1 = 0')->paginate(10, ['*'], 'upcoming_page');

        $past = $dentist
            ? $dentist->appointments()
                ->with('patient.user')
                ->whereDate('scheduled_date', '<', today()->toDateString())
                ->orderByDesc('scheduled_date')
                ->orderByDesc('scheduled_time')
                ->paginate(10, ['*'], 'past_page')
                ->withQueryString()
            : Appointment::query()->whereRaw('1 = 0')->paginate(10, ['*'], 'past_page');

        return view('dentist.schedule', [
            'dentist' => $dentist,
            'upcoming' => $upcoming,
            'past' => $past,
        ]);
    }
}
