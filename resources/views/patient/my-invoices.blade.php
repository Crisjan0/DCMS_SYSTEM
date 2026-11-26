<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('My Invoices') }}
        </h2>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Outstanding -->
            <div class="rounded-3xl bg-clinic-950 px-6 py-5 text-emerald-50 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.55)]">
                <p class="text-sm text-emerald-100/70">My outstanding balance</p>
                <p class="text-2xl font-bold text-white">₱{{ number_format($outstanding, 2) }}</p>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-clinic-950">📄 Billing history</h3>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($invoices ?? [] as $invoice)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div>
                                <p class="font-semibold text-slate-800">{{ $invoice->invoice_no }}</p>
                                <p class="text-sm text-slate-500">{{ $invoice->created_at->format('M d, Y') }} · ₱{{ number_format($invoice->total, 2) }} total</p>
                            </div>
                            <div class="flex items-center gap-4">
                                @include('clinic.partials.status-label', ['status' => $invoice->status])
                                <span class="text-sm font-semibold text-slate-800">Balance: ₱{{ number_format($invoice->balance(), 2) }}</span>
                                <a href="{{ route('clinic.invoices.show', $invoice) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-clinic-600 rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                                    View
                                </a>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-10 text-center text-slate-400">
                            {{ $invoices ? 'No invoices yet.' : 'Your account is not linked to a patient profile yet.' }}
                        </li>
                    @endforelse
                </ul>

                @if ($invoices?->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
