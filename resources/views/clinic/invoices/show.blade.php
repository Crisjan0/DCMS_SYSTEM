<x-app-layout>
    <x-slot name="header">
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                {{ $invoice->invoice_no }}
            </h2>
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900 transition">
                    🖨 Print Receipt
                </button>
                @if (auth()->user()->hasRole('staff', 'admin'))
                    <a href="{{ route('clinic.invoices.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Invoices</a>
                @else
                    <a href="{{ route(auth()->user()->patient ? 'my-invoices.index' : 'dashboard') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back</a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- ── Receipt document (prints) ── -->
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="bg-clinic-950 px-6 py-5 text-emerald-50">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-lg font-bold text-white">Sombilon-Sabuga Dental Clinic</p>
                            <p class="text-xs text-emerald-100/70">Official Billing Statement</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono font-bold text-white">{{ $invoice->invoice_no }}</p>
                            <p class="text-xs text-emerald-100/70">{{ $invoice->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <!-- Bill to -->
                    <div class="flex flex-wrap justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Billed to</p>
                            <p class="font-semibold text-slate-800">{{ $invoice->patient?->fullname() }}</p>
                            <p class="text-sm text-slate-500">{{ $invoice->patient?->mobile_number ?? '' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status</p>
                            <div class="mt-1.5">
                                @include('clinic.partials.status-label', ['status' => $invoice->status])
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <table class="mt-4 w-full text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                                <th class="py-2 text-left font-semibold">Description</th>
                                <th class="py-2 text-center font-semibold w-16">Qty</th>
                                <th class="py-2 text-right font-semibold w-28">Price</th>
                                <th class="py-2 text-right font-semibold w-28">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($invoice->items as $item)
                                <tr>
                                    <td class="py-2.5 text-slate-700">
                                        {{ $item->description }}
                                        @if ($item->treatmentRecord?->dentist)
                                            <span class="text-slate-400 text-xs">— Dr. {{ $item->treatmentRecord->dentist->fullname() }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-center text-slate-600">{{ $item->quantity }}</td>
                                    <td class="py-2.5 text-right text-slate-600">₱{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="py-2.5 text-right text-slate-800 font-medium">₱{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-slate-400">No line items.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="mt-5 ml-auto max-w-xs space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span>₱{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        @if ((float) $invoice->discount > 0)
                            <div class="flex justify-between text-slate-600">
                                <span>Discount</span>
                                <span>− ₱{{ number_format($invoice->discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold text-clinic-950 border-t border-slate-200 pt-2 text-base">
                            <span>Total</span>
                            <span>₱{{ number_format($invoice->total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Payments</span>
                            <span>− ₱{{ number_format($invoice->paid_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold {{ $invoice->balance() > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            <span>Balance</span>
                            <span>₱{{ number_format($invoice->balance(), 2) }}</span>
                        </div>
                    </div>

                    @if ($invoice->notes)
                        <p class="mt-4 text-sm text-slate-500"><span class="font-medium text-slate-600">Notes:</span> {{ $invoice->notes }}</p>
                    @endif

                    @if ($invoice->issuedBy)
                        <p class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-400">
                            Issued by {{ $invoice->issuedBy->name }} · Thank you for visiting Sombilon-Sabuga Dental Clinic!
                        </p>
                    @endif
                </div>
            </div>

            <!-- ── Payments ledger (no-print) ── -->
            <div class="no-print overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-clinic-950">💰 Payments</h3>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($invoice->payments->sortByDesc('paid_at') as $payment)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-6 py-3.5 text-sm">
                            <span class="font-semibold text-slate-800">₱{{ number_format($payment->amount, 2) }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $payment->methodLabel() }}{{ $payment->reference_no ? ' · '.$payment->reference_no : '' }}</span>
                            <span class="text-slate-500">{{ $payment->paid_at->format('M d, Y g:i A') }}</span>
                            <span class="text-slate-400 text-xs">by {{ $payment->receivedBy?->name ?? '—' }}</span>
                        </li>
                    @empty
                        <li class="px-6 py-6 text-center text-slate-400">No payments recorded yet.</li>
                    @endforelse
                </ul>

                @if (auth()->user()->hasRole('staff', 'admin') && $invoice->status !== 'void' && $invoice->balance() > 0)
                    <div class="border-t border-slate-200 px-6 py-5">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Record a payment</h4>
                        <form method="POST" action="{{ route('clinic.invoices.payments.store', $invoice) }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div>
                                <x-input-label for="amount" value="Amount (₱) — max ₱{{ number_format($invoice->balance(), 2) }}" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance() }}"
                                    value="{{ old('amount') }}" required class="mt-1.5 w-36" />
                            </div>
                            <div>
                                <x-input-label for="method" value="Method" />
                                <select
                                    id="method"
                                    name="method"
                                    class="mt-1.5 rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                    @foreach (\App\Models\Payment::METHODS as $method)
                                        <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="reference_no" value="Reference (optional)" />
                                <x-text-input id="reference_no" name="reference_no" value="{{ old('reference_no') }}" class="mt-1.5" />
                            </div>
                            <div>
                                <x-input-label for="payment_notes" value="Notes (optional)" />
                                <x-text-input id="payment_notes" name="notes" value="{{ old('notes') }}" class="mt-1.5" />
                            </div>
                            <x-primary-button>Record Payment</x-primary-button>
                        </form>
                    </div>
                @endif
            </div>

            @if (auth()->user()->hasRole('staff', 'admin') && $invoice->status !== 'void')
                <form method="POST" action="{{ route('clinic.invoices.void', $invoice) }}" class="no-print"
                      onsubmit="return confirm('Void this invoice? Recorded payments stay on record.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center rounded-full bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-600 ring-1 ring-rose-200 hover:bg-rose-100 transition">
                        Void Invoice
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
