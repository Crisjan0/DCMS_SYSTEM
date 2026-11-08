<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                🧾 New Invoice
            </h2>
            <a href="{{ route('clinic.invoices.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back to invoices</a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg text-clinic-950">Billing</h3>
                </div>

                <form method="POST" action="{{ route('clinic.invoices.store') }}" x-data="{
                    treatments: @js($treatments->mapWithKeys(fn ($treatment) => [
                        (string) $treatment->id => [
                            'label' => $treatment->procedure.($treatment->tooth_number ? ' — Tooth '.$treatment->tooth_number : '').' ('.$treatment->treatment_date->format('M d, Y').')',
                            'price' => \App\Models\Appointment::SERVICE_PRICES[$treatment->procedure] ?? 0,
                            'checked' => false,
                        ],
                    ])->all()),
                    custom: [],
                    discount: 0,
                    treatmentTotal() {
                        return Object.values(this.treatments)
                            .filter(t => t.checked)
                            .reduce((sum, t) => sum + (Number(t.price) || 0), 0);
                    },
                    customTotal() {
                        return this.custom.reduce((sum, item) => sum + (Number(item.qty) || 0) * (Number(item.price) || 0), 0);
                    },
                    subtotal() {
                        return this.treatmentTotal() + this.customTotal();
                    },
                    total() {
                        return Math.max(0, this.subtotal() - (Number(this.discount) || 0));
                    },
                    money(n) {
                        return Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },
                    addCustom() {
                        this.custom.push({ description: '', qty: 1, price: 0 });
                    },
                    removeCustom(i) {
                        this.custom.splice(i, 1);
                    },
                }" class="px-6 py-5 space-y-5">
                    @csrf

                    <!-- Patient picker -->
                    <div>
                        <x-input-label value="Patient" />
                        @if ($patient)
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                            <div class="mt-1.5 rounded-2xl bg-clinic-50 ring-1 ring-clinic-200 px-4 py-3 text-sm text-clinic-900 font-semibold">
                                👤 {{ $patient->fullname() }}
                            </div>
                        @else
                            <select
                                name="patient_id"
                                required
                                class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                <option value="">— Select patient —</option>
                                @foreach ($patients as $person)
                                    <option value="{{ $person->id }}" @selected(old('patient_id') == $person->id)>
                                        {{ $person->fullname() }} ({{ $person->user?->email ?? 'no account' }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <x-input-error :messages="$errors->get('patient_id')" class="mt-2" />
                    </div>

                    <!-- Unbilled treatment selection -->
                    <fieldset x-show="Object.keys(treatments).length > 0">
                        <legend class="text-sm font-semibold text-slate-700 mb-2">Unbilled treatment records</legend>
                        <div class="space-y-2">
                            <template x-for="(treatment, id) in treatments" :key="id">
                                <label class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <input type="checkbox" name="treatment_ids[]" :value="id" x-model="treatment.checked"
                                           class="h-4 w-4 rounded border-slate-300 text-clinic-600 focus:ring-clinic-500">
                                    <span class="flex-1 text-slate-700" x-text="treatment.label"></span>
                                    <span class="flex items-center gap-1.5 text-slate-500">
                                        ₱
                                        <input type="number" :name="'treatment_items[' + id + ']'" :value="treatment.price" x-model.number="treatment.price"
                                               step="0.01" min="0"
                                               class="w-24 rounded border-slate-300 text-sm text-right focus:border-clinic-500 focus:ring-2 focus:ring-clinic-500/25">
                                    </span>
                                </label>
                            </template>
                        </div>
                        @if ($patient && $treatments->isEmpty())
                            <p class="mt-2 text-xs text-slate-400">No unbilled treatment records for this patient — add a custom line item below.</p>
                        @endif
                        <x-input-error :messages="$errors->get('treatment_items')" class="mt-2" />
                    </fieldset>

                    <!-- Custom line items -->
                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-700 mb-2">Custom line items</legend>
                        <div class="space-y-2">
                            <template x-for="(item, index) in custom" :key="'custom-'+index">
                                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 px-3 py-2.5">
                                    <input type="text" x-model="item.description" :name="'custom_descriptions['+index+']'"
                                           placeholder="Description (e.g. Consultation)"
                                           class="flex-1 min-w-40 rounded border border-slate-300 text-sm focus:border-clinic-500 focus:ring-2 focus:ring-clinic-500/25">
                                    <input type="number" x-model.number="item.qty" :name="'custom_quantities['+index+']'" min="1"
                                           class="w-16 rounded border border-slate-300 text-sm text-right focus:border-clinic-500 focus:ring-2 focus:ring-clinic-500/25">
                                    <span class="text-sm text-slate-500">₱</span>
                                    <input type="number" x-model.number="item.price" :name="'custom_prices['+index+']'" min="0" step="0.01"
                                           class="w-24 rounded border border-slate-300 text-sm text-right focus:border-clinic-500 focus:ring-2 focus:ring-clinic-500/25">
                                    <button type="button" @click="removeCustom(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                        ✕
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addCustom" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">+ Add line item</button>
                        </div>
                    </fieldset>

                    <!-- Discount, notes, totals -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="discount" value="Discount (₱)" />
                            <x-text-input id="discount" name="discount" type="number" step="0.01" min="0"
                                value="{{ old('discount') }}" x-model.number="discount" class="mt-1.5" />
                            <x-input-error :messages="$errors->get('discount')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes (optional)" />
                            <x-text-input id="notes" name="notes" value="{{ old('notes') }}" class="mt-1.5" />
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Live totals -->
                    <div class="rounded-2xl bg-clinic-950 px-5 py-4 text-emerald-50">
                        <div class="flex justify-between text-sm">
                            <span class="text-emerald-100/70">Subtotal</span>
                            <span x-text="'₱' + money(subtotal())">₱0.00</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-emerald-100/70">Discount</span>
                            <span x-text="'− ₱' + money(discount)">− ₱0.00</span>
                        </div>
                        <div class="flex justify-between items-baseline mt-2 pt-2 border-t border-white/10">
                            <span class="font-semibold">Total</span>
                            <span class="text-xl font-bold" x-text="'₱' + money(total())">₱0.00</span>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Create Invoice</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
