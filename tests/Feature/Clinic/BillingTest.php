<?php

namespace Tests\Feature\Clinic;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
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

    private function createStaffUser(): User
    {
        return $this->createPatientUser('Reception Staff', 'staff');
    }

    private function createTreatment(User $owner, string $procedure = 'Composite Filling', ?Appointment $appointment = null): TreatmentRecord
    {
        $dentistUser = User::create([
            'name' => 'Dr. Ana Reyes',
            'email' => 'dr.reyes.billing@dcms.test',
            'password' => 'password',
        ]);

        $dentist = Dentist::firstWhere('user_id', $dentistUser->id) ?? Dentist::create([
            'user_id' => $dentistUser->id,
            'lastname' => 'Reyes',
            'firstname' => 'Ana',
            'middlename' => null,
            'date_of_birth' => '1985-01-01',
            'mobile_number' => '09179999999',
            'gender' => 'female',
            'license_number' => 'DMD-BILL-01',
            'specialty' => 'General Dentistry',
            'profile_picture' => null,
        ]);

        return TreatmentRecord::create([
            'patient_id' => $owner->patient->id,
            'dentist_id' => $dentist->id,
            'appointment_id' => $appointment?->id,
            'treatment_date' => today()->toDateString(),
            'tooth_number' => '16',
            'procedure' => $procedure,
            'diagnosis' => 'Occlusal caries',
            'recorded_by' => $owner->id,
        ]);
    }

    public function test_staff_can_create_an_invoice_from_billed_treatments(): void
    {
        $staff = $this->createStaffUser();
        $patientUser = $this->createPatientUser('Gwen Zarate');
        $treatment = $this->createTreatment($patientUser);

        $this->actingAs($staff);

        $response = $this->post(route('clinic.invoices.store'), [
            'patient_id' => $patientUser->patient->id,
            'treatment_ids' => [$treatment->id],
            'treatment_items' => [(string) $treatment->id => '1800'],
            'discount' => '100',
        ]);

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);

        $response->assertRedirect(route('clinic.invoices.show', $invoice));

        $this->assertSame('1800.00', (string) $invoice->subtotal);
        $this->assertSame('1700.00', (string) $invoice->total);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertStringStartsWith('INV-'.now()->year.'-', $invoice->invoice_no);
        $this->assertSame($treatment->id, $invoice->items->first()->treatment_record_id);
    }

    public function test_payment_flow_reaches_paid_status(): void
    {
        $staff = $this->createStaffUser();
        $patientUser = $this->createPatientUser('Helen Basus');
        $treatment = $this->createTreatment($patientUser);

        $invoice = $patientUser->patient->invoices()->create(['issued_by' => $staff->id]);
        $invoice->items()->create([
            'treatment_record_id' => $treatment->id,
            'description' => $treatment->procedure,
            'quantity' => 1,
            'unit_price' => 1000,
        ]);
        $invoice->recalculate();

        $this->actingAs($staff);

        $this->post(route('clinic.invoices.payments.store', $invoice), [
            'amount' => '400',
            'method' => 'gcash',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame('400.00', (string) $invoice->paid_amount);

        $this->post(route('clinic.invoices.payments.store', $invoice), [
            'amount' => '600',
            'method' => 'cash',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('1000.00', (string) $invoice->paid_amount);
    }

    public function test_payments_cannot_exceed_the_balance(): void
    {
        $staff = $this->createStaffUser();
        $patientUser = $this->createPatientUser('Ivan Cruz');
        $treatment = $this->createTreatment($patientUser);

        $invoice = $patientUser->patient->invoices()->create(['issued_by' => $staff->id]);
        $invoice->items()->create([
            'description' => $treatment->procedure,
            'quantity' => 1,
            'unit_price' => 500,
        ]);
        $invoice->recalculate();

        $this->actingAs($staff);

        $this->post(route('clinic.invoices.payments.store', $invoice), [
            'amount' => '800',
            'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::count());
    }

    public function test_already_billed_treatments_are_guarded_against_double_billing(): void
    {
        $staff = $this->createStaffUser();
        $patientUser = $this->createPatientUser('Jade Abenoja');

        $invoice = $patientUser->patient->invoices()->create(['issued_by' => $staff->id]);
        $treatment = $this->createTreatment($patientUser);
        $invoice->items()->create([
            'treatment_record_id' => $treatment->id,
            'description' => $treatment->procedure,
            'quantity' => 1,
            'unit_price' => 800,
        ]);

        $this->actingAs($staff);

        $response = $this->post(route('clinic.invoices.store'), [
            'patient_id' => $patientUser->patient->id,
            'treatment_ids' => [$treatment->id],
            'treatment_items' => [(string) $treatment->id => '800'],
        ]);

        $response->assertSessionHasErrors('treatment_items');
        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_patients_can_view_their_own_invoice_but_not_others(): void
    {
        $owner = $this->createPatientUser('Kaye Vidal');
        $other = $this->createPatientUser('Leo Monares');
        $staff = $this->createStaffUser();

        $invoice = $owner->patient->invoices()->create(['issued_by' => $staff->id]);
        $invoice->items()->create([
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => 500,
        ]);
        $invoice->recalculate();

        $this->actingAs($owner)
            ->get(route('clinic.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_no);

        $this->actingAs($other)
            ->get(route('clinic.invoices.show', $invoice))
            ->assertForbidden();
    }

    public function test_voiding_an_invoice_keeps_payments_and_zeroes_balance(): void
    {
        $staff = $this->createStaffUser();
        $patientUser = $this->createPatientUser('Mei Lim');

        $invoice = $patientUser->patient->invoices()->create(['issued_by' => $staff->id]);
        $invoice->items()->create([
            'description' => 'Teeth Whitening',
            'quantity' => 1,
            'unit_price' => 2000,
        ]);

        $invoice->payments()->create(['amount' => '2000', 'method' => 'hmo', 'received_by' => $staff->id, 'paid_at' => now()]);
        $invoice->recalculate();
        $this->assertSame('paid', $invoice->status);

        $this->actingAs($staff);

        $this->patch(route('clinic.invoices.void', $invoice))
            ->assertRedirect(route('clinic.invoices.index'));

        $invoice->refresh();
        $this->assertSame('void', $invoice->status);
        $this->assertSame(0.0, $invoice->balance());
        $this->assertSame(1, Payment::count());
    }

    public function test_custom_line_items_build_the_subtotal(): void
    {
        $staff = $this->createStaffUser();
        $patientUser = $this->createPatientUser('Nika Tanudtanud');

        $this->actingAs($staff);

        $this->post(route('clinic.invoices.store'), [
            'patient_id' => $patientUser->patient->id,
            'custom_descriptions' => ['0' => 'Fabricated Retainer', '1' => ''],
            'custom_quantities' => ['0' => '2', '1' => '1'],
            'custom_prices' => ['0' => '1500.50', '1' => '999'],
        ])->assertRedirect();

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame('3001.00', (string) $invoice->subtotal);
        $this->assertSame(1, $invoice->items->count());
    }
}
