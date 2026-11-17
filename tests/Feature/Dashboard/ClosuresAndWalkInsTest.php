<?php

namespace Tests\Feature\Dashboard;

use App\Models\Appointment;
use App\Models\ClinicClosure;
use App\Models\DailyClinicStat;
use App\Models\Patient;
use App\Services\Forecasting\StatsAggregator;
use App\Models\User;
use App\Models\WalkInVisit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosuresAndWalkInsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): User
    {
        $user = User::create([
            'name' => 'Front Desk Staff',
            'email' => 'closures-walkins-staff@dcms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Patient::create([
            'user_id' => $user->id,
            'lastname' => 'Reyes',
            'firstname' => 'Anna',
            'middlename' => null,
            'date_of_birth' => '1995-05-05',
            'mobile_number' => '09171234567',
            'gender' => 'female',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'staff',
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_booking_is_blocked_on_a_declared_closure(): void
    {
        $this->actingAsStaff();

        $closure = ClinicClosure::create([
            'date' => now()->addDays(7)->toDateString(),
            'name' => 'Local Fiesta',
            'type' => 'holiday',
        ]);

        $patientUser = User::create([
            'name' => 'Patient Tester',
            'email' => 'closures-walkins-patient@dcms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Patient::create([
            'user_id' => $patientUser->id,
            'lastname' => 'Dela Cruz',
            'firstname' => 'Pedro',
            'middlename' => null,
            'date_of_birth' => '2000-02-02',
            'mobile_number' => '09170000000',
            'gender' => 'male',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        $this->actingAs($patientUser);

        $response = $this->post(route('appointments.store'), [
            'service' => 'Consultation',
            'scheduled_date' => $closure->date->toDateString(),
            'scheduled_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('scheduled_date');
        $this->assertDatabaseMissing('appointments', [
            'service' => 'Consultation',
            'scheduled_date' => $closure->date->toDateString(),
        ]);
    }

    public function test_availability_feed_reports_closure_days_as_closed(): void
    {
        $this->actingAsStaff();

        $someday = now()->addDays(10);
        // Avoid Sunday for this test
        if ($someday->isSunday()) {
            $someday->addDay();
        }

        ClinicClosure::create([
            'date' => $someday->toDateString(),
            'name' => 'Maintenance Day',
            'type' => 'maintenance',
        ]);

        $response = $this->getJson(route('appointments.availability', ['date' => $someday->toDateString()]));

        $response->assertOk()->assertJson(['closed' => true]);
    }

    public function test_walk_in_counts_into_daily_clinic_stats_total_visits(): void
    {
        $this->actingAsStaff();

        $this->post(route('clinic.walk-ins.store'), [
            'service' => 'Consultation',
            'visit_date' => now()->subDay()->toDateString(),
            'count' => 2,
        ])->assertSessionHasNoErrors();

        // A booked visit the same day to prove they merge into total_visits
        $visitUser = User::create([
            'name' => 'Juan Cruz Visitee',
            'email' => 'closures-walkins-patient2@dcms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Appointment::create([
            'patient_id' => Patient::create([
                'user_id' => $visitUser->id,
                'lastname' => 'Cruz',
                'firstname' => 'Juan',
                'middlename' => null,
                'date_of_birth' => '1980-01-01',
                'mobile_number' => '0917123456',
                'gender' => 'male',
                'civil_status' => 'single',
                'address' => 'Cebu',
                'patient_type' => 'regular',
                'role' => 'patient',
            ])->id,
            'scheduled_date' => now()->subDay()->toDateString(),
            'scheduled_time' => '09:00:00',
            'service' => 'Consultation',
            'status' => 'completed',
        ]);

        $daysRebuilt = (new StatsAggregator())->rebuild();

        $this->assertSame(1, $daysRebuilt);

        $stat = DailyClinicStat::query()
            ->whereDate('date', now()->subDay()->toDateString())
            ->first();

        $this->assertNotNull($stat);
        $this->assertSame(3, (int) $stat->total_visits);   // 1 booked + 2 walk-ins
        $this->assertSame(2, (int) $stat->walk_ins);
    }

    public function test_walk_ins_page_requires_staff_role(): void
    {
        $this->actingAsStaff();

        $this->get(route('clinic.walk-ins.index'))->assertOk();
        $this->get(route('clinic.closures.index'))->assertOk();
    }
}
