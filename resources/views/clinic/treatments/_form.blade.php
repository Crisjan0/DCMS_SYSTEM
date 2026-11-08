{{-- Shared treatment record form.
     Expects: $action, $treatment (null on create), $appointment (nullable preselected),
     $patient, $visitOptions, $dentists. --}}<form method="POST" action="{{ $action }}"
      class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
    @if ($treatment)
        @method('PATCH')
    @endif
    @csrf

    <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="font-semibold text-lg text-clinic-950">🩺 Treatment Record</h3>
    </div>

    <div class="px-6 py-5 space-y-5">
        <!-- Linked visit -->
        <div>
            <x-input-label for="appointment_id" value="Linked Visit (optional)" />
            <select
                id="appointment_id"
                name="appointment_id"
                class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                <option value="">— Not linked to a visit —</option>
                @foreach ($visitOptions as $option)
                    @php($preselected = $treatment
                        ? (int) $treatment->appointment_id === (int) $option->id
                        : (optional($appointment)->id === $option->id))
                    <option value="{{ $option->id }}" @selected($preselected || old('appointment_id') == $option->id)>
                        {{ $option->scheduled_date->format('M d, Y') }} · {{ $option->scheduled_time }} · {{ $option->service }}
                        @if ($option->dentist) (Dr. {{ $option->dentist->fullname() }}) @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('appointment_id')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="dentist_id" value="Performing Dentist" />
                <select
                    id="dentist_id"
                    name="dentist_id"
                    class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                    <option value="">— Select dentist —</option>
                    @foreach ($dentists as $dentist)
                        <option value="{{ $dentist->id }}" @selected(old('dentist_id', optional($treatment)->dentist_id ?? optional($appointment)->dentist_id) == $dentist->id)>
                            Dr. {{ $dentist->fullname() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('dentist_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="treatment_date" value="Treatment Date" />
                <x-text-input
                    id="treatment_date"
                    name="treatment_date"
                    type="date"
                    :max="now()->toDateString()"
                    value="{{ old('treatment_date', optional($treatment)->treatment_date?->toDateString() ?? optional($appointment)->scheduled_date?->toDateString() ?? now()->toDateString()) }}"
                    class="mt-1.5" />
                <x-input-error :messages="$errors->get('treatment_date')" class="mt-2" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="procedure" value="Procedure" />
                <x-text-input
                    id="procedure"
                    name="procedure"
                    placeholder="e.g. Composite Filling"
                    value="{{ old('procedure', optional($treatment)->procedure ?? optional($appointment)->service) }}"
                    class="mt-1.5" />
                <x-input-error :messages="$errors->get('procedure')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tooth_number" value="Tooth Number (optional)" />
                <select
                    id="tooth_number"
                    name="tooth_number"
                    class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                    <option value="">— General / none —</option>
                    @foreach (\App\Models\ToothRecord::teeth() as $toothNumber)
                        <option value="{{ $toothNumber }}" @selected(old('tooth_number', optional($treatment)->tooth_number) == $toothNumber)>
                            Tooth {{ $toothNumber }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('tooth_number')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="diagnosis" value="Diagnosis (optional)" />
            <x-text-input
                id="diagnosis"
                name="diagnosis"
                placeholder="e.g. Occlusal caries, pulpitis"
                value="{{ old('diagnosis', optional($treatment)->diagnosis) }}"
                class="mt-1.5" />
            <x-input-error :messages="$errors->get('diagnosis')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="treatment_notes" value="Treatment Notes" />
            <textarea
                id="treatment_notes"
                name="treatment_notes"
                rows="3"
                placeholder="Anesthesia used, procedure details, post-op instructions…"
                class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white">{{ old('treatment_notes', optional($treatment)->treatment_notes) }}</textarea>
            <x-input-error :messages="$errors->get('treatment_notes')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="follow_up_date" value="Follow-up Date (optional)" />
            <x-text-input
                id="follow_up_date"
                name="follow_up_date"
                type="date"
                value="{{ old('follow_up_date', optional($treatment)->follow_up_date?->toDateString()) }}"
                class="mt-1.5" />
            <x-input-error :messages="$errors->get('follow_up_date')" class="mt-2" />
        </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
        <a href="{{ $treatment
            ? route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments'])
            : (optional($appointment)->exists
                ? route('clinic.patients.show', ['patient' => $patient, 'tab' => 'overview'])
                : route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments'])) }}"
           class="inline-flex items-center rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-200 transition">
            Cancel
        </a>
        <x-primary-button>{{ $treatment ? 'Save Changes' : 'Chart Treatment' }}</x-primary-button>
    </div>
</form>
