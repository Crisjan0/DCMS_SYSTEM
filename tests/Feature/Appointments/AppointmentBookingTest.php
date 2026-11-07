<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    private function createPatientUser(string $name = 'John Doe', string $role = 'patient'): User
    {
        $parts = preg_split('/\s+/', $name, 2);
        $firstname = $parts[0] ?? $name;
        $lastname = $parts[1] ?? 'Doe';

        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@dcms.test',
            'password' => 'password',
        ]);

        Patient::create([
            'user_id' => $user->id,
            'lastname' => $lastname,
            'firstname' => $firstname,
            'middlename' => null,
            'date_of_birth' => '1990-01-01',
            'mobile_number' => '09171234567',
            'gender' => 'male',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => $role,
        ]);

        return $user;
    }

    private function createDentist(string $last, string $first, string $license): Dentist
    {
        $user = User::create([
            'name' => 'Dr. '.$first.' '.$last,
            'email' => $license.'@dcms.test',
            'password' => 'password',
        ]);

        return Dentist::create([
            'user_id' => $user->id,
            'lastname' => $last,
            'firstname' => $first,
            'middlename' => null,
            'date_of_birth' => '1985-01-01',
            'mobile_number' => '09179999999',
            'gender' => 'female',
            'license_number' => $license,
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);
    }

    /**
     * Next weekday (Mon–Sat) after today — clinic is closed Sundays.
     */
    private function futureWeekday(int $daysAhead = 3): Carbon
    {
        $date = today()->addDays($daysAhead);

        while ($date->dayOfWeek === Carbon::SUNDAY) {
            $date->addDay();
        }

        return $date;
    }

    private function bookingPayload(string $date = null, string $time = '10:00'): array
    {
        return [
            'service' => 'Consultation',
            'scheduled_date' => $date ?? $this->futureWeekday()->toDateString(),
            'scheduled_time' => $time,
            'notes' => 'Please be gentle.',
        ];
    }

    private function patientProfileFields(): array
    {
        return [
            'lastname' => 'Dela Cruz',
            'firstname' => 'Maria',
            'middlename' => 'Santos',
            'date_of_birth' => '1995-05-05',
            'mobile_number' => '09170001111',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Mandaue City',
            'occupation' => 'Teacher',
        ];
    }

    public function test_patient_can_submit_booking_which_stays_pending_without_dentist(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001');
        $user = $this->createPatientUser('Ana Reyes');
        $this->actingAs($user);

        $this->post(route('appointments.store'), $this->bookingPayload())
            ->assertRedirect(route('appointments.index'));

        $appointment = Appointment::first();
        $this->assertNotNull($appointment);
        $this->assertSame('pending', $appointment->status);
        $this->assertNull($appointment->dentist_id);
        $this->assertSame($user->patient->id, $appointment->patient_id);
        $this->assertSame('Consultation', $appointment->service);
    }

    public function test_booking_rejected_on_sundays(): void
    {
        $this->actingAs($this->createPatientUser('Ana Reyes'));

        $sunday = today()->addDay();
        while ($sunday->dayOfWeek !== Carbon::SUNDAY) {
            $sunday->addDay();
        }

        $this->post(route('appointments.store'), $this->bookingPayload($sunday->toDateString()))
            ->assertSessionHasErrors('scheduled_date');

        $this->assertSame(0, Appointment::count());
    }

    public function test_booking_rejected_when_slot_capacity_is_exhausted(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001'); // capacity = 1
        $date = $this->futureWeekday()->toDateString();
        $time = '10:00';

        $firstPatient = $this->createPatientUser('First Patient');
        Appointment::create([
            'patient_id' => $firstPatient->patient->id,
            'dentist_id' => Dentist::first()->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->createPatientUser('Second Patient'));

        $this->post(route('appointments.store'), $this->bookingPayload($date, $time))
            ->assertSessionHasErrors('scheduled_time');

        $this->assertSame(1, Appointment::count()); // only the pre-existing one
    }

    public function test_booking_allowed_when_slot_has_free_capacity(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001');
        $this->createDentist('Cruz', 'Carlo', 'DMD-10002');
        $date = $this->futureWeekday()->toDateString();
        $time = '10:00';

        $firstPatient = $this->createPatientUser('First Patient');
        Appointment::create([
            'patient_id' => $firstPatient->patient->id,
            'dentist_id' => Dentist::query()->pluck('id')->first(),
            'scheduled_date' => $date,
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->createPatientUser('Second Patient'));

        $this->post(route('appointments.store'), $this->bookingPayload($date, $time))
            ->assertRedirect(route('appointments.index'));

        $this->assertSame(2, Appointment::count());
    }

    public function test_booking_rejected_for_the_same_patient_twice_in_one_slot(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001');
        $this->actingAs($user = $this->createPatientUser('Ana Reyes'));

        $payload = $this->bookingPayload();

        $this->post(route('appointments.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('appointments.store'), $payload)->assertSessionHasErrors('scheduled_time');

        $this->assertSame(1, Appointment::count());
    }

    public function test_first_time_user_books_and_patient_profile_is_created_automatically(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001');
        $user = User::create([
            'name' => 'Maria Dela Cruz',
            'email' => 'maria@dcms.test',
            'password' => 'password',
        ]);
        $this->actingAs($user);

        $this->assertNull($user->patient);

        $this->post(route('appointments.store'), $this->patientProfileFields() + $this->bookingPayload())
            ->assertRedirect(route('appointments.index'));

        $user->refresh();
        $this->assertNotNull($user->patient);
        $this->assertSame('patient', $user->patient->role);
        $this->assertSame('new', $user->patient->patient_type);

        $appointment = $user->patient->appointments()->first();
        $this->assertNotNull($appointment);
        $this->assertSame('pending', $appointment->status);
    }

    public function test_availability_reports_sundays_as_closed(): void
    {
        $this->actingAs($this->createPatientUser('Ana Reyes'));

        $sunday = today()->addDay();
        while ($sunday->dayOfWeek !== Carbon::SUNDAY) {
            $sunday->addDay();
        }

        $this->getJson(route('appointments.availability', ['date' => $sunday->toDateString()]))
            ->assertOk()
            ->assertJson(['closed' => true]);
    }

    public function test_availability_reports_full_slots(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001'); // capacity = 1
        $date = $this->futureWeekday()->toDateString();
        $time = '10:00';

        $bookedPatient = $this->createPatientUser('Occupier');
        Appointment::create([
            'patient_id' => $bookedPatient->patient->id,
            'dentist_id' => Dentist::first()->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->createPatientUser('Checker'));
        $response = $this->getJson(route('appointments.availability', ['date' => $date]));

        $response->assertOk()
            ->assertJsonPath('slots.'.$time.'.full', true);
    }

    public function test_staff_can_assign_available_dentist_and_status_becomes_confirmed(): void
    {
        $this->createDentist('Reyes', 'Ana', 'DMD-10001');
        $this->createDentist('Cruz', 'Carlo', 'DMD-10002');

        $patientUser = $this->createPatientUser('Ana Patient');
        $appointment = Appointment::create([...$this->bookingPayload(), 'patient_id' => $patientUser->patient->id, 'status' => 'pending']);

        $staff = $this->createPatientUser('Reception Staff', 'staff');
        $this->actingAs($staff);

        $appointment = $appointment->fresh();

        $this->patch(route('staff.appointments.assign', $appointment), ['dentist_id' => Dentist::first()->id])
            ->assertRedirect(route('staff.appointments.index'));

        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNotNull($appointment->dentist_id);
    }

    public function test_staff_cannot_assign_a_dentist_booked_in_the_same_slot(): void
    {
        $dentist = $this->createDentist('Reyes', 'Ana', 'DMD-10001');
        $date = $this->futureWeekday()->toDateString();
        $time = '10:00';

        $occupyingPatient = $this->createPatientUser('Occupier');
        Appointment::create([
            'patient_id' => $occupyingPatient->patient->id,
            'dentist_id' => $dentist->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => 'confirmed',
        ]);

        $requestingPatient = $this->createPatientUser('Requester');
        $appointment = Appointment::create([
            'patient_id' => $requestingPatient->patient->id,
            'dentist_id' => null,
            'scheduled_date' => $date,
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => 'pending',
        ]);

        $this->actingAs($this->createPatientUser('Reception Staff', 'staff'));

        $this->patch(route('staff.appointments.assign', $appointment), ['dentist_id' => $dentist->id])
            ->assertSessionHasErrors('dentist_id');

        $appointment->refresh();
        $this->assertSame('pending', $appointment->status);
        $this->assertNull($appointment->dentist_id);
    }

    public function test_non_staff_users_cannot_access_the_staff_console(): void
    {
        $this->actingAs($this->createPatientUser('Plain Patient'));

        $this->get(route('staff.appointments.index'))->assertForbidden();
    }

    public function test_patient_can_cancel_their_own_future_appointment(): void
    {
        $user = $this->createPatientUser('Ana Reyes');
        $appointment = Appointment::create([...$this->bookingPayload(), 'patient_id' => $user->patient->id, 'status' => 'pending']);

        $this->actingAs($user);

        $this->patch(route('appointments.cancel', $appointment->fresh()))
            ->assertRedirect();

        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_patient_cannot_cancel_someone_elses_appointment(): void
    {
        $otherUser = $this->createPatientUser('Other Owner');
        $appointment = Appointment::create([...$this->bookingPayload(), 'patient_id' => $otherUser->patient->id, 'status' => 'pending']);

        $attacker = $this->createPatientUser('Attacker');
        $this->actingAs($attacker);

        $this->patch(route('appointments.cancel', $appointment->fresh()))
            ->assertForbidden();

        $this->assertSame('pending', $appointment->fresh()->status);
    }

    public function test_completed_appointments_are_not_cancellable(): void
    {
        $user = $this->createPatientUser('Ana Reyes');
        $appointment = Appointment::create([
            ...$this->bookingPayload(now()->subDays(5)->toDateString(), '10:00'),
            'patient_id' => $user->patient->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user);

        $this->patch(route('appointments.cancel', $appointment))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('completed', $appointment->fresh()->status);
    }
}
