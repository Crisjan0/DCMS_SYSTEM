<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\NotificationType;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Record a payment against an invoice and refresh its balance/status.
     */
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'void') {
            return back()->with('error', 'Cannot record payment against a void invoice.');
        }

        $balance = $invoice->balance();

        if ($balance <= 0) {
            return back()->with('error', 'This invoice is already fully paid.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$balance],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $invoice->payments()->create($data + [
            'received_by' => $request->user()->id,
            'paid_at' => now(),
        ]);

        $invoice->recalculate();

        ActivityLog::record(
            'payment_recorded',
            'Recorded a '.number_format((float) $data['amount'], 2).' '.$data['method'].' payment for '.$invoice->invoice_no.' ('.$invoice->patient?->fullname().')',
            $invoice,
        );

        $this->notifications->toPatient(
            $invoice->patient,
            NotificationType::PAYMENT_RECORDED,
            'Payment received',
            'Your ₱'.number_format((float) $data['amount'], 2).' '.$data['method'].' payment for invoice '.$invoice->invoice_no.' was recorded. Remaining balance: ₱'.number_format($invoice->balance(), 2).'.',
            route('clinic.invoices.show', $invoice, absolute: false),
        );

        $this->notifications->toAdmins(
            NotificationType::PAYMENT_RECORDED,
            'Payment recorded',
            '₱'.number_format((float) $data['amount'], 2).' '.$data['method'].' payment recorded for '.$invoice->patient?->fullname()."'s invoice ".$invoice->invoice_no.'.',
            route('clinic.invoices.show', $invoice, absolute: false),
        );

        return redirect()
            ->route('clinic.invoices.show', $invoice)
            ->with('success', 'Payment recorded. New balance: ₱'.number_format($invoice->balance(), 2).'.');
    }
}
