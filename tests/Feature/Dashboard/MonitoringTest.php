<?php

namespace Tests\Feature\Dashboard;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function createPatientUser(string $name = 'John Doe', string $role = 'patient', ?bool $isAdmin = false): User
    {
        $parts = preg_split('/\s+/', $name, 2);
        $firstname = $parts[0] ?? $name;
        $lastname = $parts[1] ?? 'Doe';

        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).rand(1, 999).'@dcms.test',
            'password' => 'password',
            'is_admin' => $isAdmin,
        ]);

        Patient::create([
            'user_id' => $isAdmin ? null : $user->id,
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

        return $user->fresh();
    }

    private function createDentistBooking(?int $dentistId, User $patientUser, string $date, string $time, string $status = 'confirmed'): Appointment
    {
        return Appointment::create([
            'patient_id' => $patientUser->patient->id,
            'dentist_id' => $dentistId,
            'scheduled_date' => $date,
            'scheduled_time' => $time.':00',
            'service' => 'Consultation',
            'status' => $status,
        ]);
    }

    private function staffUser(): User
    {
        return $this->createPatientUser('Console Staff', 'staff');
    }

    private function dentistUser(): User
    {
        $user = $this->createPatientUser('Dash Dentist', 'patient', isAdmin: false);

        Dentist::create([
            'user_id' => $user->id,
            'lastname' => 'Dentist',
            'firstname' => 'Dash',
            'middlename' => null,
            'date_of_birth' => '1980-01-01',
            'mobile_number' => '09179999999',
            'gender' => 'female',
            'license_number' => 'DMD-DASH-01',
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);

        return $user->fresh();
    }

    private function futureWeekday(): string
    {
        $date = today();

        while ($date->dayOfWeek === Carbon::SUNDAY) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    public function test_staff_dashboard_shows_metrics_today(): void
    {
        $staff = $this->staffUser();
        $dentistUser = $this->dentistUser();
        $dentist = Dentist::firstWhere('user_id', $dentistUser->id);

        $this->actingAs($staff);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Today's Patient Flow", false)
            ->assertSee('Staff Activity')
            ->assertSee('Resource Allocation');

        // Flow endpoint returns the patients of today.
        $patientUser = $this->createPatientUser('Gloria Bright');
        $this->createDentistBooking($dentist->id, $patientUser, today()->toDateString(), '10:00', 'confirmed');

        $this->getJson(route('dashboard.flow'))
            ->assertOk()
            ->assertJsonFragment(['patient' => 'Gloria Bright', 'status' => 'confirmed']);
    }

    public function test_activity_log_is_written_for_assign_and_cancel(): void
    {
        $staff = $this->staffUser();
        $dentistUser = $this->dentistUser();
        $dentist = Dentist::firstWhere('user_id', $dentistUser->id);

        $patientUser = $this->createPatientUser('Harvey Brood');
        $appointment = $this->createDentistBooking(null, $patientUser, $this->futureWeekday(), '10:00');
        $appointment->update(['dentist_id' => null, 'status' => 'pending']);

        $this->actingAs($staff);

        $this->patch(route('staff.appointments.assign', $appointment), ['dentist_id' => $dentist->id])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'appointment_assigned',
            'user_id' => $staff->id,
            'subject_type' => 'Appointment',
            'subject_id' => $appointment->id,
        ]);

        $this->patch(route('staff.appointments.cancel', $appointment->fresh()))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'appointment_cancelled',
            'user_id' => $staff->id,
        ]);
    }

    public function test_flow_endpoint_is_forbidden_for_plain_patients(): void
    {
        $this->actingAs($this->createPatientUser('Isa Bul'));

        $this->getJson(route('dashboard.flow'))->assertForbidden();
    }

    public function test_patient_dashboard_shows_next_visit_and_balance(): void
    {
        $dentistUser = $this->dentistUser();
        $dentist = Dentist::firstWhere('user_id', $dentistUser->id);

        $patientUser = $this->createPatientUser('Julia Wez');
        $this->createDentistBooking($dentist->id, $patientUser, $this->futureWeekday(), '10:00', 'confirmed');

        $invoice = $patientUser->patient->invoices()->create();
        $invoice->items()->create([
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => 500,
        ]);
        $invoice->recalculate();

        $this->actingAs($patientUser);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Consultation')
            ->assertSee('Your next visit')
            ->assertSee('₱'.number_format(500, 2));
    }

    public function test_dentist_utilization_rows_render_on_staff_dashboard(): void
    {
        $staff = $this->staffUser();
        $dentistUser = $this->dentistUser();
        $dentist = Dentist::firstWhere('user_id', $dentistUser->id);

        $patientUser = $this->createPatientUser('Kai Marlow');
        $this->createDentistBooking($dentist->id, $patientUser, today()->toDateString(), '10:00', 'completed');
        $this->createDentistBooking($dentist->id, $patientUser, today()->toDateString(), '11:00', 'confirmed');

        $this->actingAs($staff);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dr. Dash Dentist')
            ->assertSee('2/16 slots today');
    }
}
