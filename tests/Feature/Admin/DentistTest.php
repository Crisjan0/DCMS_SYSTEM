<?php

namespace Tests\Feature\Admin;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentistTest extends TestCase
{
    use RefreshDatabase;

    /** Admin access no longer lives in patients.role — it's users.is_admin. */
    public function test_admin_without_patient_profile_still_passes_middleware(): void
    {
        $admin = User::create([
            'name' => 'Flag Admin',
            'email' => 'flag@dcms.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dentists.index'))
            ->assertOk();
    }

    public function test_patient_profile_role_grants_no_admin_access(): void
    {
        $user = User::create([
            'name' => 'Patient Person',
            'email' => 'patient.p@dcms.test',
            'password' => 'password',
        ]);

        Patient::create([
            'user_id' => $user->id,
            'lastname' => 'Person',
            'firstname' => 'Patient',
            'date_of_birth' => '2000-01-01',
            'mobile_number' => '09123456789',
            'gender' => 'male',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        $this->actingAs($user)
            ->get(route('admin.dentists.index'))
            ->assertForbidden();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Clinic Admin',
            'email' => 'admin@dcms.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    private function makeUser(string $name = 'Linked Person'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@dcms.test',
            'password' => 'password',
        ]);
    }

    private function dentistPayload(?User $user = null, string $license = 'DMD-90001'): array
    {
        return [
            'user_id' => $user?->id,
            'lastname' => 'Reyes',
            'firstname' => 'Ana',
            'middlename' => 'Carpio',
            'date_of_birth' => '1990-05-10',
            'mobile_number' => '09171234567',
            'gender' => 'female',
            'license_number' => $license,
            'specialty' => 'Orthodontics',
        ];
    }

    public function test_admin_can_view_dentist_list(): void
    {
        $this->actingAsAdmin();

        Dentist::create($this->dentistPayload());

        $this->get(route('admin.dentists.index'))
            ->assertOk()
            ->assertSee('Dr. Ana Carpio Reyes');
    }

    public function test_non_admin_cannot_access_dentist_admin(): void
    {
        // Guest is redirected to login
        $this->get(route('admin.dentists.index'))
            ->assertRedirect(route('login'));

        // Authenticated non-admin is forbidden
        $user = User::create([
            'name' => 'Patient Person',
            'email' => 'patient.p@dcms.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get(route('admin.dentists.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_dentist_without_user_account(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.dentists.store'), $this->dentistPayload())
            ->assertRedirect(route('admin.dentists.index'));

        $dentist = Dentist::where('license_number', 'DMD-90001')->first();
        $this->assertNotNull($dentist);
        $this->assertNull($dentist->user_id);
    }

    public function test_admin_can_create_dentist_with_linked_account(): void
    {
        $this->actingAsAdmin();
        $user = $this->makeUser('Dr. Lito Tan');

        $this->post(route('admin.dentists.store'), $this->dentistPayload($user))
            ->assertRedirect(route('admin.dentists.index'));

        $dentist = Dentist::where('license_number', 'DMD-90001')->first();
        $this->assertSame($user->id, $dentist->user_id);
        $this->assertSame($user->id, $dentist->user->id);
    }

    public function test_admin_can_link_account_to_existing_unlinked_dentist(): void
    {
        $this->actingAsAdmin();

        $dentist = Dentist::create($this->dentistPayload());
        $user = $this->makeUser('Nurse Rhea Lopez');

        $payload = $this->dentistPayload(null);
        $payload['user_id'] = $user->id;

        $this->patch(route('admin.dentists.update', $dentist), $payload)
            ->assertRedirect(route('admin.dentists.index'));

        $dentist->refresh();
        $this->assertSame($user->id, $dentist->user_id);
    }

    public function test_cannot_link_account_already_linked_to_another_dentist(): void
    {
        $this->actingAsAdmin();

        $user = $this->makeUser('Dr. Shared Account');

        $first = Dentist::create($this->dentistPayload($user, 'DMD-90001'));
        $second = Dentist::create($this->dentistPayload(null, 'DMD-90002'));

        $payload = $this->dentistPayload($user, 'DMD-90002');

        $this->patch(route('admin.dentists.update', $second), $payload)
            ->assertSessionHasErrors('user_id');

        $second->refresh();
        $this->assertNotSame($user->id, $second->user_id);
        $first->refresh();
        $this->assertSame($user->id, $first->user_id);
    }

    public function test_admin_can_unlink_account_and_user_is_kept(): void
    {
        $this->actingAsAdmin();
        $user = $this->makeUser('Dr. To Unlink');

        $dentist = Dentist::create($this->dentistPayload($user));

        $payload = $this->dentistPayload(null); // no user_id sent

        $this->patch(route('admin.dentists.update', $dentist), $payload)
            ->assertRedirect(route('admin.dentists.index'));

        $dentist->refresh();
        $this->assertNull($dentist->user_id);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_dentist_keeps_linked_user(): void
    {
        $this->actingAsAdmin();
        $user = $this->makeUser('Dr. Delete Me');

        $dentist = Dentist::create($this->dentistPayload($user));

        $this->delete(route('admin.dentists.destroy', $dentist))
            ->assertRedirect(route('admin.dentists.index'));

        $this->assertDatabaseMissing('dentists', ['id' => $dentist->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
