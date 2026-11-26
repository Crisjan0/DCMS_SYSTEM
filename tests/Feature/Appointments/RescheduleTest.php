<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RescheduleTest extends TestCase
{
    use RefreshDatabase;

    private function createPatientUser(string $name = 'John Doe'): User
    {
        $parts = preg_split('/\s+/', $name, 2);
        $firstname = $parts[0] ?? $name;
        $lastname = $parts[1] ?? 'Doe';

        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).rand(1, 999).'@dcms.test',
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
            'role' => 'patient',
        ]);

        return $user->fresh();
    }

    private function createDentist(string $email = 'dr.res@dcms.test', string $lastname = 'Resched'): Dentist
    {
        $user = User::create([
            'name' => 'Dr. '.$lastname,
            'email' => $email,
            'password' => 'password',
        ]);

        return Dentist::create([
            'user_id' => $user->id,
            'lastname' => $lastname,
            'firstname' => 'Test',
            'middlename' => null,
            'date_of_birth' => '1980-01-01',
            'mobile_number' => '09179999999',
            'gender' => 'male',
            'license_number' => 'DMD-'.substr(md5($email), 0, 6),
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);
    }

    private function futureWeekday(): Carbon
    {
        $date = today()->addDays(3);

        while ($date->dayOfWeek === Carbon::SUNDAY) {
            $date->addDay();
        }

        return $date;
    }

    private function anotherFutureWeekday(): Carbon
    {
        $date = $this->futureWeekday()->addDays(2);

        while ($date->dayOfWeek === Carbon::SUNDAY) {
            $date->addDay();
        }

        return $date;
    }

    private function book(User $user, Carbon $date, string $time, string $status = 'confirmed'): Appointment
    {
        return Appointment::create([
            'patient_id' => $user->patient->id,
            'dentist_id' => null,
            'scheduled_date' => $date->toDateString(),
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => $status,
        ]);
    }

    public function test_patient_can_reschedule_their_own_upcoming_appointment(): void
    {
        $this->createDentist(); // clinic must have capacity

        $user = $this->createPatientUser('Lina Gwapo');
        $appointment = $this->book($user, $this->futureWeekday(), '10:00');

        $this->actingAs($user);

        $newDate = $this->anotherFutureWeekday();

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $newDate->toDateString(),
            'scheduled_time' => '14:00',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame($newDate->toDateString(), $appointment->scheduled_date->toDateString());
        $this->assertSame('14:00', $appointment->scheduled_time);
        $this->assertSame('confirmed', $appointment->status);

        $this->assertDatabaseHas('activity_logs', ['action' => 'appointment_rescheduled']);
    }

    public function test_patient_cannot_reschedule_someone_elses_appointment(): void
    {
        $intruder = $this->createPatientUser('Nosy那人 Sameh');
        $owner = $this->createPatientUser('Owner Galang');
        $appointment = $this->book($owner, $this->futureWeekday(), '10:00');

        $this->actingAs($intruder);

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $this->anotherFutureWeekday()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertForbidden();
    }

    public function test_past_appointments_reschedule_form_is_denied(): void
    {
        $user = $this->createPatientUser('Late Comor');
        $appointment = $this->book($user, today()->subDays(3), '10:00');
        $appointment->update(['status' => 'confirmed']);

        $this->actingAs($user);

        $this->get(route('appointments.reschedule.form', $appointment))
            ->assertRedirect();

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $this->futureWeekday()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertRedirect();
    }

    public function test_rescheduling_a_sunday_is_rejected(): void
    {
        $user = $this->createPatientUser('Rest Day');
        $appointment = $this->book($user, $this->futureWeekday(), '10:00');

        $this->actingAs($user);

        $sunday = today()->addDays(10);
        while ($sunday->dayOfWeek !== Carbon::SUNDAY) {
            $sunday->addDay();
        }

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $sunday->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertSessionHasErrors('scheduled_date');
    }

    public function test_fully_booked_slot_is_rejected(): void
    {
        $dentist = $this->createDentist(); // capacity = 1
        $user = $this->createPatientUser('Crowd Hater');
        $other = $this->createPatientUser('Slot Taker');

        $appointment = $this->book($user, $this->futureWeekday(), '09:00');
        $this->book($other, $this->futureWeekday(), '10:00'); // occupies the 10:00 slot

        $this->actingAs($user);

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $this->futureWeekday()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertSessionHasErrors('scheduled_time');

        $appointment->refresh();
        $this->assertSame('09:00', $appointment->scheduled_time);
    }

    public function test_patient_double_booking_same_slot_is_rejected(): void
    {
        $this->createDentist(); // clinic has capacity; conflict is the patient's own booking

        $user = $this->createPatientUser('Double Booker');
        $appointment = $this->book($user, $this->futureWeekday(), '09:00');
        $this->book($user, $this->futureWeekday(), '13:00');

        $this->actingAs($user);

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $this->futureWeekday()->toDateString(),
            'scheduled_time' => '13:00',
        ])->assertSessionHasErrors('scheduled_time');
    }

    public function test_staff_can_reschedule_an_appointment(): void
    {
        $dentist = $this->createDentist();
        $staffUser = $this->createPatientUser('Staff Sched');
        $staffUser->patient->update(['role' => 'staff']);
        $staffUser = $staffUser->fresh();

        $owner = $this->createPatientUser('Visit Holder');
        $appointment = $this->book($owner, $this->futureWeekday(), '10:00');
        $appointment->update(['dentist_id' => $dentist->id, 'status' => 'confirmed']);

        $this->actingAs($staffUser);

        $newDate = $this->anotherFutureWeekday();

        $this->patch(route('staff.appointments.reschedule', $appointment), [
            'scheduled_date' => $newDate->toDateString(),
            'scheduled_time' => '09:00',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame($newDate->toDateString(), $appointment->scheduled_date->toDateString());
        $this->assertSame($dentist->id, $appointment->dentist_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'appointment_rescheduled', 'user_id' => $staffUser->id]);
    }

    public function test_staff_reschedule_is_blocked_when_dentist_is_busy_at_new_slot(): void
    {
        $dentist = $this->createDentist();
        $staffUser = $this->createPatientUser('Staff Busycheck');
        $staffUser->patient->update(['role' => 'staff']);
        $staffUser = $staffUser->fresh();

        $owner = $this->createPatientUser('Target Patient');
        $appointment = $this->book($owner, $this->futureWeekday(), '10:00');
        $appointment->update(['dentist_id' => $dentist->id]);

        // Same dentist has another visit already at 15:00 that same day.
        $otherOwner = $this->createPatientUser('Other Visit');
        $this->book($otherOwner, $this->futureWeekday(), '15:00')->update(['dentist_id' => $dentist->id]);

        $this->actingAs($staffUser);

        $this->patch(route('staff.appointments.reschedule', $appointment), [
            'scheduled_date' => $this->futureWeekday()->toDateString(),
            'scheduled_time' => '15:00',
        ])->assertSessionHasErrors('scheduled_time');

        $appointment->refresh();
        $this->assertSame('10:00', $appointment->scheduled_time);
    }

    public function test_patients_cannot_reschedule_completed_appointments(): void
    {
        $user = $this->createPatientUser('Done Deal');
        $appointment = $this->book($user, today()->subDays(2), '10:00');
        $appointment->update(['status' => 'completed']);

        $this->actingAs($user);

        $this->patch(route('appointments.reschedule', $appointment), [
            'scheduled_date' => $this->futureWeekday()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame('completed', $appointment->status);
    }
}
