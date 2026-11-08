<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_shows_personal_information_button(): void
    {
        $user = User::factory()->create();

        // Birthday falls 3 days after today → completed age must show as a
        // whole number (29), not the raw fraction (29.99…).
        $dob = now()->subYears(30)->addDays(3)->toDateString();

        Patient::create([
            'user_id' => $user->id,
            'lastname' => 'Reyes',
            'firstname' => 'Ana',
            'middlename' => 'B.',
            'date_of_birth' => $dob,
            'mobile_number' => '09171234567',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('View Personal Information')
            ->assertSee('Ana B. Reyes')
            ->assertSee('09171234567')
            ->assertSee('Cebu City')
            ->assertSee('29 yrs old');
    }

    public function test_personal_information_section_handles_accounts_without_patient_record(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('View Personal Information')
            ->assertSee('no patient record');
    }

    public function test_patient_can_update_their_personal_information_and_picture(): void
    {
        Storage::fake('public');

        $user = $this->createPatientUser();

        $this->actingAs($user)
            ->patch('/profile/patient', [
                'lastname' => 'Dela Cruz',
                'firstname' => 'Maria',
                'middlename' => 'C.',
                'date_of_birth' => '1992-03-15',
                'mobile_number' => '09185556677',
                'gender' => 'female',
                'civil_status' => 'married',
                'address' => 'Mandaue City',
                'occupation' => 'Teacher',
                'patient_type' => 'regular',
                'profile_picture' => UploadedFile::fake()->image('me.jpg', 100, 100),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $patient = $user->fresh()->patient;
        $this->assertSame('Dela Cruz', $patient->lastname);
        $this->assertSame('Maria', $patient->firstname);
        $this->assertSame('1992-03-15', $patient->date_of_birth->toDateString());
        $this->assertSame('09185556677', $patient->mobile_number);
        $this->assertSame('married', $patient->civil_status);
        $this->assertSame('Mandaue City', $patient->address);
        $this->assertSame('Teacher', $patient->occupation);

        // Role must never be changeable via this endpoint.
        $this->assertSame('patient', $patient->role);

        // Picture stored on public disk.
        $this->assertStringStartsWith('patient-pictures/', $patient->profile_picture);
        Storage::disk('public')->assertExists($patient->profile_picture);
    }

    public function test_updating_the_picture_replaces_the_previous_one(): void
    {
        Storage::fake('public');

        $user = $this->createPatientUser();
        $patient = $user->patient;
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('patient-pictures', 'public');
        $patient->update(['profile_picture' => $oldPath]);

        $this->actingAs($user)
            ->patch('/profile/patient', [
                'lastname' => $patient->lastname,
                'firstname' => $patient->firstname,
                'middlename' => $patient->middlename,
                'date_of_birth' => $patient->date_of_birth->toDateString(),
                'mobile_number' => $patient->mobile_number,
                'gender' => $patient->gender,
                'civil_status' => $patient->civil_status,
                'address' => $patient->address,
                'occupation' => $patient->occupation,
                'patient_type' => $patient->patient_type,
                'profile_picture' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $patient->refresh();
        $this->assertNotSame($oldPath, $patient->profile_picture);
        Storage::disk('public')->assertExists($patient->profile_picture);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_patient_update_validates_required_fields(): void
    {
        $user = $this->createPatientUser();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile/patient', [
                'lastname' => '',
                'mobile_number' => 'not-a-number-at-all-yes!',
                'gender' => 'alien',
            ])
            ->assertSessionHasErrorsIn('patientUpdate', ['lastname', 'mobile_number', 'gender'])
            ->assertRedirect('/profile');

        $this->assertSame('Reyes', $user->patient->lastname);
    }

    public function test_account_without_patient_record_cannot_update_patient_info(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->patch('/profile/patient', [
                'lastname' => 'Hacker',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('error');

        $this->assertSame(null, $user->patient);
    }

    private function createPatientUser(): User
    {
        $user = User::factory()->create();

        Patient::create([
            'user_id' => $user->id,
            'lastname' => 'Reyes',
            'firstname' => 'Ana',
            'middlename' => 'B.',
            'date_of_birth' => '1990-01-01',
            'mobile_number' => '09171234567',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        return $user->fresh();
    }

    public function test_sidebar_shows_uploaded_patient_picture(): void
    {
        Storage::fake('public');

        $user = $this->createPatientUser();
        $path = UploadedFile::fake()->image('me.jpg')->store('patient-pictures', 'public');
        $user->patient->update(['profile_picture' => $path]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('storage/'.$path, false)
            // Regression guard: the avatar <img> must keep its fixed size classes
            // (otherwise the photo renders at natural size and fills the sidebar).
            ->assertSee('class="h-9 w-9 rounded-full', false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
