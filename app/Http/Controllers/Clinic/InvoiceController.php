<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Clinic-wide invoice ledger with collection stats (staff + admin).
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status');

        $invoices = Invoice::query()
            ->with('patient.user')
            ->when($status !== '' && in_array($status, Invoice::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patient) use ($search) {
                        $patient->where('lastname', 'like', "%{$search}%")
                            ->orWhere('firstname', 'like', "%{$search}%");
                    });
            }))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $receivable = (float) Invoice::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')
            ->value('due');

        $collectedToday = (float) Payment::query()->whereDate('paid_at', today())->sum('amount');
        $collectedMonth = (float) Payment::query()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()])
            ->sum('amount');

        return view('clinic.invoices.index', [
            'invoices' => $invoices,
            'search' => $search,
            'status' => $status,
            'receivable' => $receivable,
            'collectedToday' => $collectedToday,
            'collectedMonth' => $collectedMonth,
        ]);
    }

    /**
     * Build an invoice: bill one or more charted treatment records
     * and/or custom line items for a chosen patient.
     */
    public function create(Request $request): View
    {
        $patientId = $request->integer('patient');
        $patient = $patientId
            ? Patient::where('role', 'patient')->with('user')->find($patientId)
            : null;

        return view('clinic.invoices.create', [
            'patient' => $patient,
            'patients' => Patient::where('role', 'patient')
                ->with('user')
                ->orderBy('lastname')->orderBy('firstname')
                ->get(),
            'treatments' => $patient
                ? $patient->treatmentRecords()
                    ->whereDoesntHave('invoiceItems')
                    ->orderByDesc('treatment_date')
                    ->limit(30)
                    ->get()
                : collect(),
            'priceList' => Appointment::SERVICE_PRICES,
        ]);
    }

    /**
     * Persist the invoice, its line items, and recompute totals.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('role', 'patient')],
            'treatment_ids' => ['nullable', 'array'],
            'treatment_ids.*' => ['integer'],
            'treatment_items' => ['nullable', 'array'],
            'treatment_items.*' => ['required', 'numeric', 'min:0'],
            'custom_descriptions' => ['nullable', 'array'],
            'custom_quantities' => ['nullable', 'array'],
            'custom_prices' => ['nullable', 'array'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $patient = Patient::findOrFail($data['patient_id']);

        $selectedIds = array_map(fn ($id) => (int) $id, $data['treatment_ids'] ?? []);

        // The chosen treatments must belong to this patient and be unbilled.
        $treatments = TreatmentRecord::query()
            ->where('patient_id', $patient->id)
            ->whereIn('id', $selectedIds)
            ->get()
            ->keyBy('id');

        $alreadyBilled = $treatments->filter(
            fn (TreatmentRecord $treatment) => $treatment->invoiceItems()->exists()
        )->pluck('procedure')->all();

        if ($alreadyBilled !== []) {
            return back()->withErrors([
                'treatment_items' => 'Already billed: '.implode(', ', $alreadyBilled),
            ])->withInput();
        }

        // Assemble custom line items (filter out blank rows).
        $customItems = [];
        $descriptions = $data['custom_descriptions'] ?? [];
        foreach (array_keys($descriptions) as $i) {
            $description = trim((string) ($descriptions[$i] ?? ''));
            $quantity = max(1, (int) ($data['custom_quantities'][$i] ?? 1));
            $unitPrice = round((float) ($data['custom_prices'][$i] ?? 0), 2);

            if ($description !== '' && $unitPrice > 0) {
                $customItems[] = [
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }
        }

        if ($treatments->isEmpty() && $customItems === []) {
            return back()->withErrors([
                'treatment_items' => 'Add at least one treatment or a custom line item to bill.',
            ])->withInput();
        }

        $invoice = DB::transaction(function () use ($patient, $request, $data, $treatments, $customItems) {
            $invoice = $patient->invoices()->create([
                'issued_by' => $request->user()->id,
                'discount' => round((float) ($data['discount'] ?? 0), 2),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($treatments as $treatment) {
                /** @var array<string, numeric-string> $data */
                $price = round((float) $data['treatment_items'][$treatment->id], 2);

                $invoice->items()->create([
                    'treatment_record_id' => $treatment->id,
                    'description' => $treatment->procedure.($treatment->tooth_number ? ' — Tooth '.$treatment->tooth_number : ''),
                    'quantity' => 1,
                    'unit_price' => $price,
                ]);
            }

            foreach ($customItems as $item) {
                $invoice->items()->create($item);
            }

            $invoice->recalculate();

            ActivityLog::record(
                'invoice_created',
                'Created invoice '.$invoice->invoice_no.' ('.number_format($invoice->total, 2).') for '.$patient->fullname(),
                $invoice,
            );

            return $invoice->load('patient.user');
        });

        return redirect()
            ->route('clinic.invoices.show', $invoice)
            ->with('success', 'Invoice '.$invoice->invoice_no.' created for '.$patient->fullname().'.');
    }

    /**
     * Invoice detail. Staff/admin/dentists can open any invoice;
     * patients can only open their own.
     */
    public function show(Request $request, Invoice $invoice): View
    {
        $user = $request->user();

        $mayView = $user->hasRole('staff', 'admin', 'dentist')
            || ($invoice->patient && $invoice->patient->user_id === $user->id);

        abort_unless($mayView, 403);

        $invoice->load([
            'patient.user',
            'issuedBy',
            'items.treatmentRecord.dentist.user',
            'payments.receivedBy',
            'payments.invoice',
        ]);

        return view('clinic.invoices.show', ['invoice' => $invoice]);
    }

    /**
     * Void an invoice. Payments stay on record (handled manually).
     */
    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'void') {
            return back()->with('error', 'Invoice is already void.');
        }

        $invoice->forceFill(['status' => 'void'])->save();

        ActivityLog::record(
            'invoice_voided',
            'Voided invoice '.$invoice->invoice_no.' ('.$invoice->patient?->fullname().')',
            $invoice,
        );

        return redirect()
            ->route('clinic.invoices.index')
            ->with('success', 'Invoice '.$invoice->invoice_no.' was voided. Recorded payments are retained.');
    }
}
