<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedBasics(): array
    {
        $dentistUser = User::create([
            'name' => 'Dr. Ana Reyes',
            'email' => 'dr.reyes@dcms.test',
            'password' => 'password',
        ]);

        $dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'lastname' => 'Reyes',
            'firstname' => 'Ana',
            'middlename' => null,
            'date_of_birth' => '1985-01-01',
            'mobile_number' => '09179999999',
            'gender' => 'female',
            'license_number' => 'DMD-10001',
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);

        $patientUser = User::create([
            'name' => 'Ana Reyes',
            'email' => 'ana@dcms.test',
            'password' => 'password',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'lastname' => 'Reyes',
            'firstname' => 'Ana',
            'middlename' => null,
            'date_of_birth' => '1990-01-01',
            'mobile_number' => '09171234567',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        $date = today()->addDays(3);
        while ($date->dayOfWeek === Carbon::SUNDAY) {
            $date->addDay();
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => null,
            'scheduled_date' => $date->toDateString(),
            'scheduled_time' => '10:00:00',
            'service' => 'Consultation',
            'notes' => 'test',
            'status' => 'pending',
        ]);

        $staffUser = User::create([
            'name' => 'Reception Staff',
            'email' => 'staff@dcms.test',
            'password' => 'password',
        ]);

        Patient::create([
            'user_id' => $staffUser->id,
            'lastname' => 'Santos',
            'firstname' => 'Maria',
            'middlename' => null,
            'date_of_birth' => '1990-01-01',
            'mobile_number' => '09171234568',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'staff',
        ]);

        $adminUser = User::create([
            'name' => 'Clinic Admin',
            'email' => 'admin2@dcms.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        return compact('dentist', 'appointment', 'staffUser', 'adminUser', 'patientUser');
    }

    public function test_app_layout_renders_with_new_theme_for_staff(): void
    {
        $s = $this->seedBasics();
        $this->actingAs($s['staffUser']);

        $this->get(route('staff.appointments.index'))
            ->assertOk()
            ->assertSee('Sombilon-Sabuga')
            ->assertSee('Dental Clinic')
            ->assertSee('bg-clinic-950') // themed sidebar + footer
            ->assertSee('md:hidden'); // mobile card lists (table hidden on phones)
    }

    public function test_all_main_pages_render_for_patient(): void
    {
        $s = $this->seedBasics();
        $this->actingAs($s['patientUser']);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('appointments.create'))->assertOk();
        $this->get(route('appointments.index'))->assertOk()->assertSee('md:hidden');
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_all_main_pages_render_for_admin(): void
    {
        $s = $this->seedBasics();
        $this->actingAs($s['adminUser']);

        $this->get(route('staff.appointments.index'))->assertOk();
        $this->get(route('admin.dentists.index'))->assertOk();
        $this->get(route('admin.dentists.create'))->assertOk();
        $this->get(route('admin.dentists.edit', $s['dentist']))->assertOk();
        $this->get(route('staff.appointments.assign.form', $s['appointment']))->assertOk();
    }
}
