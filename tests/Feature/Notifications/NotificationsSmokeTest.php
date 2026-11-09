<?php

namespace Tests\Feature\Notifications;

use App\Livewire\NotificationsIndex;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\ClinicNotification;
use App\Notifications\NotificationType;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_center_requires_authentication(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_notification_center_renders_for_authenticated_users(): void
    {
        $user = User::create([
            'name' => 'Ana Reyes',
            'email' => 'ana@dcms.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSeeLivewire('notifications-index');
    }

    public function test_toast_picks_up_flashed_session_messages(): void
    {
        $user = User::create([
            'name' => 'Ana Reyes',
            'email' => 'ana2@dcms.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->withSession(['success' => 'Health declaration saved. Thank you!'])
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Health declaration saved. Thank you!');
    }

    public function test_notification_detail_popup_opens_and_marks_read(): void
    {
        [$patientUser, $patient] = $this->makePatient();

        $patientUser->notify(new ClinicNotification(
            NotificationType::APPOINTMENT_REMINDER,
            'Appointment reminder',
            'Your visit is scheduled tomorrow.',
            '/appointments',
        ));

        $notification = $patientUser->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame(1, $patientUser->unreadNotifications()->count());

        $this->actingAs($patientUser);

        $component = Livewire::test(NotificationsIndex::class);

        // Clicking a notification opens the popup and marks it read.
        $component->call('focusNotification', $notification->id)
            ->assertSet('focusId', $notification->id);

        $this->assertSame(0, $patientUser->unreadNotifications()->count());

        // Closing the popup resets focus...
        $component->call('closeFocus')->assertSet('focusId', null);

        // ...and "Open" on a focused notification navigates to its link.
        $component->call('focusNotification', $notification->id)
            ->call('goToFocused')
            ->assertRedirect('/appointments');
    }

    public function test_service_targets_the_right_roles(): void
    {
        $admin = User::create([
            'name' => 'Clinic Admin',
            'email' => 'admin@dcms.test',
            'password' => 'password',
            'is_admin' => true,
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

        [$patientUser, $patient] = $this->makePatient();

        $service = app(NotificationService::class);

        $service->toClinic(NotificationType::APPOINTMENT_BOOKED, 'New appointment request', 'Booked a visit.');

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $staffUser->notifications()->count());
        $this->assertSame(0, $patientUser->notifications()->count());

        $service->toPatient($patient, NotificationType::APPOINTMENT_CONFIRMED, 'Appointment confirmed', 'See you soon.');

        $this->assertSame(1, $patientUser->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_reminder_command_is_deduped(): void
    {
        [$patientUser, $patient] = $this->makePatient();

        Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => null,
            'scheduled_date' => today()->addDay()->toDateString(),
            'scheduled_time' => '10:00:00',
            'service' => 'Consultation',
            'notes' => 'test',
            'status' => 'confirmed',
        ]);

        $this->artisan('appointments:remind')->assertSuccessful();
        $this->artisan('appointments:remind')->assertSuccessful();

        // Re-running the command must not duplicate the reminder.
        $this->assertSame(1, $patientUser->notifications()->count());

        $notification = $patientUser->notifications()->first();

        $this->assertSame(NotificationType::APPOINTMENT_REMINDER, $notification->data['type']);
        $this->assertStringContainsString('Consultation', $notification->data['body']);
    }

    /**
     * @return array{0: User, 1: Patient}
     */
    private function makePatient(): array
    {
        $patientUser = User::create([
            'name' => 'Ben Cruz',
            'email' => 'ben@dcms.test',
            'password' => 'password',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'lastname' => 'Cruz',
            'firstname' => 'Ben',
            'middlename' => null,
            'date_of_birth' => '1992-02-02',
            'mobile_number' => '09171234569',
            'gender' => 'male',
            'civil_status' => 'single',
            'address' => 'Cebu City',
            'patient_type' => 'regular',
            'role' => 'patient',
        ]);

        return [$patientUser, $patient];
    }
}
