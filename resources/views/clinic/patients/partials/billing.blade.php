{{-- Billing summary for one patient.
     Expects: $patient, $invoices, $outstanding. --}}<div class="space-y-6">
    <!-- Outstanding banner + create invoice CTA -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-clinic-950 px-6 py-5 text-emerald-50 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.55)]">
        <div>
            <p class="text-sm text-emerald-100/70">Total outstanding balance</p>
            <p class="text-2xl font-bold text-white">₱{{ number_format($outstanding, 2) }}</p>
        </div>
        <a href="{{ route('clinic.invoices.create', ['patient' => $patient]) }}"
           class="no-print inline-flex items-center rounded-full bg-clinic-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-clinic-600 transition">
            🧾 Create Invoice
        </a>
    </div>

    <!-- Invoices table -->
    <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-lg text-clinic-950">📄 Recent Invoices</h3>
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">Invoice</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Paid</th>
                        <th class="px-6 py-3">Balance</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-3.5 font-medium text-slate-800">{{ $invoice->invoice_no }}</td>
                            <td class="px-6 py-3.5 text-slate-500 whitespace-nowrap">{{ $invoice->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5 text-slate-700 font-medium">₱{{ number_format($invoice->total, 2) }}</td>
                            <td class="px-6 py-3.5 text-slate-600">₱{{ number_format($invoice->paid_amount, 2) }}</td>
                            <td class="px-6 py-3.5 font-semibold text-slate-800">₱{{ number_format($invoice->balance(), 2) }}</td>
                            <td class="px-6 py-3.5">
                                @include('clinic.partials.status-label', ['status' => $invoice->status])
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('clinic.invoices.show', $invoice) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-clinic-600 rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No invoices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="p-4 space-y-3 md:hidden">
            @forelse ($invoices as $invoice)
                <a href="{{ route('clinic.invoices.show', $invoice) }}"
                   class="block rounded-2xl ring-1 ring-clinic-950/10 px-4 py-3.5">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-slate-800">{{ $invoice->invoice_no }}</p>
                        @include('clinic.partials.status-label', ['status' => $invoice->status])
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        ₱{{ number_format($invoice->total, 2) }} · balance ₱{{ number_format($invoice->balance(), 2) }}
                    </p>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-slate-400">No invoices yet.</div>
            @endforelse
        </div>
    </div>
</div>
