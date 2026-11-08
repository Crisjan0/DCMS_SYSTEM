<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
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

        return redirect()
            ->route('clinic.invoices.show', $invoice)
            ->with('success', 'Payment recorded. New balance: ₱'.number_format($invoice->balance(), 2).'.');
    }
}
