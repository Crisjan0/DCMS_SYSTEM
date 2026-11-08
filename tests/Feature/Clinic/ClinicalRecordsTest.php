<?php

namespace Tests\Feature\Clinic;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\ToothRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalRecordsTest extends TestCase
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

    private function createDentistUser(string $email = 'dr.cart@dcms.test'): User
    {
        $user = User::create([
            'name' => 'Dr. Ball Carts',
            'email' => $email,
            'password' => 'password',
        ]);

        Dentist::create([
            'user_id' => $user->id,
            'lastname' => 'Carts',
            'firstname' => 'Ball',
            'middlename' => null,
            'date_of_birth' => '1980-02-02',
            'mobile_number' => '09175556666',
            'gender' => 'male',
            'license_number' => 'DMD-CART-01',
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);

        return $user->fresh();
    }

    private function staffUser(): User
    {
        return $this->createPatientUser('Front Desk Staff', 'staff');
    }

    private function pendingAppointment(User $owner): Appointment
    {
        $dentistUser = $this->createDentistUser();
        $date = today()->subDay();

        // Land on a non-Sunday past day so it is closable.
        while ((int) $date->dayOfWeek === 0) {
            $date->subDay();
        }

        return Appointment::create([
            'patient_id' => $owner->patient->id,
            'dentist_id' => $dentistUser->dentist->id,
            'scheduled_date' => $date->toDateString(),
            'scheduled_time' => '10:00:00',
            'service' => 'Consultation',
            'status' => 'pending',
        ]);
    }

    public function test_staff_can_browse_patient_records(): void
    {
        $staff = $this->staffUser();
        $patientUser = $this->createPatientUser('Olive Rama');

        $this->actingAs($staff);

        $this->get(route('clinic.patients.index'))
            ->assertOk()
            ->assertSee('Olive Rama');

        $this->get(route('clinic.patients.show', $patientUser->patient))
            ->assertOk()
            ->assertSee('Olive Rama');
    }

    public function test_plain_patients_cannot_access_clinic_records(): void
    {
        $setinta = $this->createPatientUser('Plain Visitor');

        $this->actingAs($setinta);

        $this->get(route('clinic.patients.index'))->assertForbidden();

        $otherUser = $this->createPatientUser('Other Patient');

        $this->get(route('clinic.patients.show', $otherUser->patient))->assertForbidden();
    }

    public function test_tooth_records_can_be_upserted_and_cleared_as_healthy(): void
    {
        $staff = $this->staffUser();
        $patientUser = $this->createPatientUser('Pablo Sites');
        $patient = $patientUser->patient;

        $this->actingAs($staff);

        $this->post(route('clinic.patients.tooth-records.update', $patient), [
            'tooth_number' => '36',
            'status' => 'caries',
            'note' => 'Distal decay',
        ])->assertRedirect();

        $record = ToothRecord::first();
        $this->assertNotNull($record);
        $this->assertSame('caries', $record->status);
        $this->assertSame('36', $record->tooth_number);

        $this->post(route('clinic.patients.tooth-records.update', $patient), [
            'tooth_number' => '36',
            'status' => 'filled',
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame('filled', $record->status);
        $this->assertSame(1, ToothRecord::count());

        $this->post(route('clinic.patients.tooth-records.update', $patient), [
            'tooth_number' => '36',
            'status' => 'healthy',
        ])->assertRedirect();

        $this->assertSame(0, ToothRecord::count());
    }

    public function test_medical_history_can_be_saved_by_staff_with_flags(): void
    {
        $staff = $this->staffUser();
        $patientUser = $this->createPatientUser('Queenie Alcoy');
        $patient = $patientUser->patient;

        $this->actingAs($staff);

        $this->post(route('clinic.patients.medical-history.store', $patient), [
            'allergies' => 'Penicillin',
            'hypertension' => '1',
            'asthma' => '1',
        ])->assertRedirect();

        $history = MedicalHistory::first();
        $this->assertNotNull($history);
        $this->assertTrue($history->hypertension);
        $this->assertTrue($history->asthma);
        $this->assertFalse($history->diabetes);
        $this->assertSame(['hypertension', 'asthma'], $history->activeConditions());
        $this->assertSame($staff->id, $history->updated_by);
    }

    public function test_charting_a_treatment_completes_the_source_appointment_and_logs_it(): void
    {
        $staff = $this->staffUser();
        $patientUser = $this->createPatientUser('Rico Bojo');
        $appointment = $this->pendingAppointment($patientUser);
        $patient = $patientUser->patient;

        $this->actingAs($staff);

        $this->post(route('clinic.patients.treatments.store', $patient), [
            'appointment_id' => $appointment->id,
            'dentist_id' => $appointment->dentist_id,
            'treatment_date' => today()->toDateString(),
            'procedure' => 'Composite Filling',
            'tooth_number' => '16',
            'diagnosis' => 'Occlusal caries',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame('completed', $appointment->status);

        $this->assertDatabaseHas('activity_logs', ['action' => 'treatment_charted']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'appointment_completed']);
    }

    public function test_future_appointments_cannot_be_marked_completed(): void
    {
        $staff = $this->staffUser();
        $patientUser = $this->createPatientUser('Sasha Talamban');
        $dentistUser = $this->createDentistUser('dr.future@dcms.test');

        $date = today()->addDays(4);
        while ((int) $date->dayOfWeek === 0) {
            $date->addDay();
        }

        $appointment = Appointment::create([
            'patient_id' => $patientUser->patient->id,
            'dentist_id' => $dentistUser->dentist->id,
            'scheduled_date' => $date->toDateString(),
            'scheduled_time' => '10:00:00',
            'service' => 'Consultation',
            'status' => 'confirmed',
        ]);

        $this->actingAs($staff);

        $this->patch(route('clinic.appointments.status.update', $appointment), ['status' => 'completed'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_patients_can_edit_their_own_health_declaration(): void
    {
        $patientUser = $this->createPatientUser('Tina Minglanilla');

        $this->actingAs($patientUser);

        $this->get(route('my-record.index'))
            ->assertOk()
            ->assertSee('Health Declaration');

        $this->post(route('my-record.medical-history.save'), [
            'allergies' => 'Mild latex sensitivity',
            'diabetes' => '1',
        ])->assertRedirect();

        $history = MedicalHistory::first();
        $this->assertNotNull($history);
        $this->assertTrue($history->diabetes);
        $this->assertSame($patientUser->id, $history->updated_by);
    }
}
