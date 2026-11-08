<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                🧾 Invoices
            </h2>
            <a href="{{ route('clinic.invoices.create') }}"
               class="inline-flex items-center px-4 py-2 bg-clinic-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                + New Invoice
            </a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Collection stats -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-3xl bg-clinic-950 px-5 py-4 text-emerald-50 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.55)]">
                    <p class="text-sm text-emerald-100/70">Outstanding receivables</p>
                    <p class="mt-1 text-2xl font-bold text-white">₱{{ number_format($receivable, 2) }}</p>
                </div>
                <div class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <p class="text-sm text-slate-500">Collected today</p>
                    <p class="mt-1 text-2xl font-bold text-clinic-700">₱{{ number_format($collectedToday, 2) }}</p>
                </div>
                <div class="rounded-3xl bg-white px-5 py-4 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <p class="text-sm text-slate-500">Collected this month</p>
                    <p class="mt-1 text-2xl font-bold text-clinic-700">₱{{ number_format($collectedMonth, 2) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-3xl bg-white/80 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 p-4">
                <form method="GET" action="{{ route('clinic.invoices.index') }}" class="flex flex-wrap gap-3">
                    <x-text-input name="q" placeholder="Search invoice # or patient…" value="{{ $search }}" class="flex-1 min-w-52" />
                    <select
                        name="status"
                        class="rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Invoice::STATUSES as $option)
                            <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center rounded-full bg-clinic-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-clinic-700 transition">Filter</button>
                    @if ($search !== '' || $status !== '')
                        <a href="{{ route('clinic.invoices.index') }}" class="inline-flex items-center rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-200 transition">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Invoice</th>
                                <th class="px-6 py-3">Patient</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3">Balance</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($invoices as $invoice)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-3.5 font-medium text-slate-800">{{ $invoice->invoice_no }}</td>
                                    <td class="px-6 py-3.5 text-slate-600">{{ $invoice->patient?->fullname() }}</td>
                                    <td class="px-6 py-3.5 text-slate-500 whitespace-nowrap">{{ $invoice->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-3.5 text-slate-700 font-medium">₱{{ number_format($invoice->total, 2) }}</td>
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
                                <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No invoices found.</td></tr>
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
                            <p class="mt-1 text-sm text-slate-500">{{ $invoice->patient?->fullname() }} · ₱{{ number_format($invoice->total, 2) }} · balance ₱{{ number_format($invoice->balance(), 2) }}</p>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-slate-400">No invoices found.</div>
                    @endforelse
                </div>

                @if ($invoices->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
