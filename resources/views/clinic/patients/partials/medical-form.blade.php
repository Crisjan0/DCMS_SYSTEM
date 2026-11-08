{{-- Reusable health declaration form.
     Expects: $action (form URL), $medicalHistory (model or null). --}}
<div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
        <h3 class="font-semibold text-lg text-clinic-950">📋 Health Declaration</h3>
        @if ($medicalHistory?->declared_at)
            <span class="text-xs text-slate-500">Last updated {{ $medicalHistory->declared_at->format('M d, Y g:i A') }} by {{ $medicalHistory->updatedBy?->name ?? '—' }}</span>
        @else
            <span class="text-xs text-amber-600 font-medium">⚠ Not yet declared</span>
        @endif
    </div>

    <form method="POST" action="{{ $action }}" class="px-6 py-5 space-y-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="allergies" value="Allergies" />
                <x-text-input id="allergies" name="allergies"
                    placeholder="e.g. Penicillin, Latex"
                    value="{{ old('allergies', $medicalHistory?->allergies) }}" class="mt-1.5" />
                <x-input-error :messages="$errors->get('allergies')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="medications" value="Current Medications" />
                <x-text-input id="medications" name="medications"
                    placeholder="e.g. Metformin 500mg"
                    value="{{ old('medications', $medicalHistory?->medications) }}" class="mt-1.5" />
                <x-input-error :messages="$errors->get('medications')" class="mt-2" />
            </div>
        </div>

        <fieldset>
            <legend class="text-sm font-semibold text-slate-700 mb-2.5">Check any that apply:</legend>
            <div class="grid gap-2.5 sm:grid-cols-2">
                @foreach (\App\Models\MedicalHistory::CONDITIONS as $flag => $label)
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-600 cursor-pointer hover:border-clinic-400 transition {{ old($flag, $medicalHistory?->$flag ?? false) ? 'border-clinic-400 bg-clinic-50' : '' }}">
                        <input
                            @checked(old($flag, $medicalHistory?->$flag ?? false))
                            type="checkbox"
                            name="{{ $flag }}"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-clinic-600 focus:ring-clinic-500" />
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="other_conditions" value="Other Medical Conditions" />
                <textarea id="other_conditions" name="other_conditions" rows="2"
                    class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white"
                    placeholder="Anything else we should know…">{{ old('other_conditions', $medicalHistory?->other_conditions) }}</textarea>
                <x-input-error :messages="$errors->get('other_conditions')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="medical_notes" value="Internal Notes" />
                <textarea id="medical_notes" name="medical_notes" rows="2"
                    class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white"
                    placeholder="Notes for the clinic team…">{{ old('medical_notes', $medicalHistory?->medical_notes) }}</textarea>
                <x-input-error :messages="$errors->get('medical_notes')" class="mt-2" />
            </div>
        </div>

        <div class="flex justify-end">
            <x-primary-button>Save Declaration</x-primary-button>
        </div>
    </form>
</div>
