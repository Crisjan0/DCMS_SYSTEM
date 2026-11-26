<?php

namespace Tests\Feature\Dashboard;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffUser(): User
    {
        $staff = User::create([
            'name' => 'Reception Staff',
            'email' => 'forecast-staff@dcms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Patient::create([
            'user_id' => $staff->id,
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

        return $staff;
    }

    public function test_staff_can_open_the_forecast_page(): void
    {
        $staff = $this->makeStaffUser();

        $this->actingAs($staff)
            ->get('/forecast')
            ->assertOk()
            ->assertSee('Volume Forecast')
            ->assertSee('ARIMA')
            ->assertSee('holt_winters');
    }

    public function test_patients_cannot_open_the_forecast_page(): void
    {
        $patient = User::create([
            'name' => 'Ana Reyes',
            'email' => 'forecast-patient@dcms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($patient)
            ->get('/forecast')
            ->assertForbidden();
    }

    public function test_regeneration_defaults_to_the_arima_model(): void
    {
        $staff = $this->makeStaffUser();

        // The collector zero-fills the 3-year daily series, so even an
        // empty clinic produces a valid (flat) forecast run.
        $this->actingAs($staff)
            ->post('/forecast/regenerate')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('forecast_runs', 1);
        $this->assertDatabaseHas('forecast_runs', ['algorithm' => 'arima']);
    }

    public function test_regeneration_accepts_an_alternate_model(): void
    {
        $staff = $this->makeStaffUser();

        $this->actingAs($staff)
            ->post('/forecast/regenerate', ['algorithm' => 'holt_winters'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('forecast_runs', ['algorithm' => 'holt_winters']);
    }

    public function test_unknown_algorithm_falls_back_to_arima(): void
    {
        $staff = $this->makeStaffUser();

        $this->actingAs($staff)
            ->post('/forecast/regenerate', ['algorithm' => 'bogus-model'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('forecast_runs', ['algorithm' => 'arima']);
    }
}
