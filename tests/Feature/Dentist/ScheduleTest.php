<?php

namespace Tests\Feature\Dentist;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function linkedDentistUser(string $email = 'dr.new@dcms.test', string $lastname = 'Lim'): User
    {
        $user = User::create([
            'name' => 'Dr. '.$lastname,
            'email' => $email,
            'password' => 'password',
        ]);

        $dentist = Dentist::create([
            'user_id' => $user->id,
            'lastname' => $lastname,
            'firstname' => 'Test',
            'middlename' => null,
            'date_of_birth' => '1980-01-01',
            'mobile_number' => '09'.substr(md5($email), 0, 9),
            'gender' => 'male',
            'license_number' => 'DMD-'.substr(md5($email), 0, 6),
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);

        return $user->fresh();
    }

    private function patientUser(string $email, string $firstname = 'Patient', string $lastname = 'Sampol'): User
    {
        $user = User::create([
            'name' => $firstname.' '.$lastname,
            'email' => $email,
            'password' => 'password',
        ]);

        Patient::create([
            'user_id' => $user->id,
            'lastname' => $lastname,
            'firstname' => $firstname,
            'middlename' => null,
            'date_of_birth' => '1995-05-05',
            'mobile_number' => '09170000002',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        return $user->fresh();
    }

    private function upcomingDate(): string
    {
        $date = today()->addDays(3);

        while ($date->dayOfWeek === \Carbon\Carbon::SUNDAY) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    public function test_dentist_sees_their_assigned_patients(): void
    {
        $dentistUser = $this->linkedDentistUser();
        $dentist = $dentistUser->dentist;
        $patientUser = $this->patientUser('anna@dcms.test', 'Anna', 'Abella');

        Appointment::create([
            'patient_id' => $patientUser->patient->id,
            'dentist_id' => $dentist->id,
            'scheduled_date' => $this->upcomingDate(),
            'scheduled_time' => '10:00:00',
            'service' => 'Tooth Extraction',
            'status' => 'confirmed',
        ]);

        $this->actingAs($dentistUser)
            ->get(route('dentist.schedule'))
            ->assertOk()
            ->assertSee('Anna Abella')
            ->assertSee('Tooth Extraction')
            ->assertSee('09170000002');
    }

    public function test_dentist_does_not_see_other_dentists_appointments(): void
    {
        $dentistA = $this->linkedDentistUser('dr.a@dcms.test', 'Abanto');
        $dentistB = $this->linkedDentistUser('dr.b@dcms.test', 'Bucao');
        $patientUser = $this->patientUser('joy@dcms.test', 'Joy', 'Yamba');

        Appointment::create([
            'patient_id' => $patientUser->patient->id,
            'dentist_id' => $dentistB->dentist->id,
            'scheduled_date' => $this->upcomingDate(),
            'scheduled_time' => '10:00:00',
            'service' => 'Cleaning',
            'status' => 'confirmed',
        ]);

        $this->actingAs($dentistA)
            ->get(route('dentist.schedule'))
            ->assertOk()
            ->assertDontSee('Joy Yamba')
            ->assertSee('No assigned appointments yet');
    }

    public function test_dentist_schedule_shows_empty_state(): void
    {
        $dentistUser = $this->linkedDentistUser();

        $this->actingAs($dentistUser)
            ->get(route('dentist.schedule'))
            ->assertOk()
            ->assertSee('No assigned appointments yet');
    }

    public function test_users_without_dentist_profile_see_not_linked_message(): void
    {
        $patientUser = $this->patientUser('plain@dcms.test', 'Plainy', 'Puno');

        $this->actingAs($patientUser)
            ->get(route('dentist.schedule'))
            ->assertOk()
            ->assertSee('not linked to a dentist profile');
    }

    public function test_upcoming_list_is_paginated_by_ten(): void
    {
        $dentistUser = $this->linkedDentistUser();
        $patientUser = $this->patientUser('many@dcms.test', 'Many', 'Mada');

        for ($i = 0; $i < 11; $i++) {
            Appointment::create([
                'patient_id' => $patientUser->patient->id,
                'dentist_id' => $dentistUser->dentist->id,
                'scheduled_date' => $this->upcomingDate(),
                'scheduled_time' => '10:00:00',
                'service' => 'Consultation',
                'status' => 'confirmed',
            ]);
        }

        $this->actingAs($dentistUser)
            ->get(route('dentist.schedule'))
            ->assertOk()
            ->assertSee('Next', false);
    }
}
