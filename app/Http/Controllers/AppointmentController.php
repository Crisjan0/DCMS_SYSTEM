<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Show the booking form. Includes a patient-profile section
     * when the logged-in user doesn't have one yet.
     */
    public function create(Request $request): View
    {
        [$firstname, $lastname] = $this->splitName($request->user()->name);

        return view('appointments.book', [
            'services' => Appointment::SERVICES,
            'timeSlots' => Appointment::timeSlots(),
            'needsProfile' => $request->user()->patient === null,
            'firstnameGuess' => $firstname,
            'lastnameGuess' => $lastname,
        ]);
    }

    /**
     * Live slot availability feed for the booking form.
     */
    public function availability(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['date' => ['required', 'date', 'after_or_equal:today']]);

        $date = Carbon::parse($request->input('date'))->startOfDay();
        $capacity = Dentist::count();

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return response()->json(['closed' => true, 'capacity' => $capacity, 'slots' => []]);
        }

        $booked = DB::table('appointments')
            ->whereDate('scheduled_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->groupBy('scheduled_time')
            ->pluck(DB::raw('COUNT(*)'), 'scheduled_time');

        $nowTime = now()->format('H:i');

        $slots = [];
        foreach (Appointment::timeSlots() as $slot) {
            $bookedCount = (int) ($booked[$slot.':00'] ?? 0);

            $slots[$slot] = [
                'booked' => $bookedCount,
                'capacity' => $capacity,
                'full' => $bookedCount >= $capacity,
                'past' => $date->isSameDay(now()) && $slot <= $nowTime,
            ];
        }

        return response()->json(['closed' => false, 'capacity' => $capacity, 'slots' => $slots]);
    }

    /**
     * Store a new appointment request (status: pending, dentist assigned later by staff).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service' => ['required', Rule::in(Appointment::SERVICES)],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', Rule::in(Appointment::timeSlots())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $errors = $this->validateSlot($data);

        $patient = $request->user()->patient;

        // First-time registrants complete a quick profile in the same step.
        $profileData = null;
        if ($patient === null) {
            $profileData = $request->validate([
                'lastname' => ['required', 'string', 'max:255'],
                'firstname' => ['required', 'string', 'max:255'],
                'middlename' => ['nullable', 'string', 'max:255'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'mobile_number' => ['required', 'string', 'max:255'],
                'gender' => ['required', Rule::in(['male', 'female', 'other'])],
                'civil_status' => ['required', Rule::in(['single', 'married', 'widowed', 'separated', 'divorced'])],
                'address' => ['required', 'string', 'max:500'],
                'occupation' => ['nullable', 'string', 'max:255'],
            ]);
        } elseif ($errors === []) {
            // Don't let a patient hold two active bookings in the same slot.
            $hasOwnConflict = Appointment::where('patient_id', $patient->id)
                ->conflictingAt(Carbon::parse($data['scheduled_date'])->toDateString(), $data['scheduled_time'])
                ->exists();

            if ($hasOwnConflict) {
                $errors['scheduled_time'] = 'You already have an appointment at this time slot.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use (&$patient, $request, $data, $profileData) {
            if ($patient === null) {
                $patient = Patient::create($profileData + [
                    'user_id' => $request->user()->id,
                    'patient_type' => 'new',
                    'profile_picture' => null,
                    'role' => 'patient',
                ]);
            }

            $patient->appointments()->create([
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'].':00',
                'service' => $data['service'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);
        });

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Appointment request submitted! The staff will assign an available dentist.');
    }

    /**
     * The logged-in patient's own appointments.
     */
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;

        $appointments = $patient
            ? $patient->appointments()->with('dentist.user')
                ->orderByDesc('scheduled_date')
                ->orderByDesc('scheduled_time')
                ->paginate(10)
            : Appointment::query()->whereRaw('1 = 0')->paginate(10);

        return view('appointments.index', [
            'appointments' => $appointments,
        ]);
    }

    /**
     * Cancel one of the patient's own future appointments.
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()->patient?->id === $appointment->patient_id, 403);

        if (! $appointment->isCancellable()) {
            return back()->with('error', 'This appointment can no longer be cancelled.');
        }

        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Appointment cancelled.');
    }

    private function validateSlot(array $data): array
    {
        $date = Carbon::parse($data['scheduled_date'])->startOfDay();
        $errors = [];

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            $errors['scheduled_date'] = 'The clinic is closed on Sundays, please pick another date.';
        } elseif ($date->isSameDay(now()) && $data['scheduled_time'] <= now()->format('H:i')) {
            $errors['scheduled_time'] = 'This time slot has already passed — pick a later one.';
        } else {
            $dentistCount = Dentist::count();

            if ($dentistCount === 0) {
                $errors['scheduled_time'] = 'No dentists are registered yet. Please try again later.';
            } else {
                $bookedCount = Appointment::conflictingAt($date->toDateString(), $data['scheduled_time'])->count();

                if ($bookedCount >= $dentistCount) {
                    $errors['scheduled_time'] = 'This time slot is fully booked — please pick another date or time.';
                }
            }
        }

        return $errors;
    }

    /**
     * @return array{0: string, 1: string} [firstname, lastname]
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
