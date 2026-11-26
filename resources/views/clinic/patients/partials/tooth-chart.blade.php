{{-- Interactive odontogram (FDI numbering).
     Expects: $patient, $toothRecords (collection keyed by tooth_number),
     and optional $canEdit bool (defaults to true). --}}
@php($canEdit = $canEdit ?? true)
@php($toothMeta = \App\Models\ToothRecord::STATUS_META)
@php($layout = \App\Models\ToothRecord::chartLayout())

<section
    x-data="{
        records: @js($toothRecords->mapWithKeys(fn ($record) => [(string) $record->tooth_number => ['status' => $record->status, 'note' => $record->note]])->all()),
        meta: @js($toothMeta),
        currentTooth: '',
        formStatus: 'caries',
        formNote: '',
        cls(n) {
            const rec = this.records[String(n)];
            if (rec && this.meta[rec.status]) return this.meta[rec.status].color;
            return 'bg-white border-slate-200 text-slate-500 hover:border-clinic-500 hover:text-clinic-700';
        },
        open(n) {
            this.currentTooth = String(n);
            const rec = this.records[String(n)];
            this.formStatus = rec ? rec.status : 'caries';
            this.formNote = rec ? (rec.note || '') : '';
        },
    }"
    class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">

    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
        <h3 class="font-semibold text-lg text-clinic-950">🦷 Odontogram (Tooth Chart)</h3>
        <span class="text-xs text-slate-500">FDI numbering · {{ $toothRecords->count() }} teeth with findings</span>
    </div>

    <div class="px-6 py-5 space-y-5">
        @foreach ($layout as $jaw => $teeth)
            <div>
                <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ ucfirst($jaw) }} Jaw</p>
                <div class="flex gap-1 overflow-x-auto pb-1">
                    @foreach ($teeth as $index => $toothNumber)
                        @if ($index === 8)
                            <span class="mx-1 w-0 self-stretch border-l border-dashed border-slate-300"></span>
                        @endif
                        @if ($canEdit)
                            <button
                                type="button"
                                @click="open({{ $toothNumber }})"
                                :class="cls({{ $toothNumber }}) + (currentTooth == '{{ $toothNumber }}' ? ' ring-2 ring-offset-2 ring-clinic-600' : '')"
                                class="inline-flex h-9 w-9 shrink-0 flex-col items-center justify-center rounded-lg border text-[11px] font-bold transition-colors duration-150"
                                :title="'Tooth ' + '{{ $toothNumber }}'">
                                {{ $toothNumber }}
                            </button>
                        @else
                            <div
                                :class="cls({{ $toothNumber }})"
                                class="inline-flex h-9 w-9 shrink-0 flex-col items-center justify-center rounded-lg border text-[11px] font-bold"
                                title="Tooth {{ $toothNumber }}">
                                {{ $toothNumber }}
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Legend -->
        <div class="flex flex-wrap gap-2 pt-1">
            @foreach ($toothMeta as $statuses)
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                    <span class="inline-block h-3.5 w-3.5 rounded {{ $statuses['color'] }}"></span>
                    {{ $statuses['label'] }}
                </span>
            @endforeach
        </div>
    </div>

    <!-- Findings list -->
    @if ($toothRecords->isNotEmpty())
        <div class="border-t border-slate-200 px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Findings by tooth</p>
            <ul class="grid gap-1.5 sm:grid-cols-2">
                @foreach ($toothRecords->sortBy('tooth_number') as $record)
                    <li class="flex items-start gap-2 text-sm">
                        <span class="inline-flex h-6 shrink-0 items-center rounded {{ $toothMeta[$record->status]['color'] }} px-2 text-[11px] font-bold">#{{ $record->tooth_number }}</span>
                        <span class="text-slate-600">
                            {{ $toothMeta[$record->status]['label'] }}
                            @if ($record->note)
                                <span class="text-slate-400"> — {{ $record->note }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($canEdit)
        <!-- Tooth edit modal -->
        <div x-show="currentTooth" x-cloak class="fixed inset-0 z-50 no-print">
            <div class="absolute inset-0 bg-clinic-950/50 backdrop-blur-sm" @click="currentTooth = ''"></div>

            <div class="absolute inset-x-4 top-1/2 max-w-sm -translate-y-1/2 rounded-3xl bg-white p-6 shadow-2xl sm:mx-auto">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-bold text-clinic-950">Tooth <span x-text="currentTooth"></span></h4>
                    <button type="button" @click="currentTooth = ''" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('clinic.patients.tooth-records.update', ['patient' => $patient]) }}">
                    @csrf
                    <input type="hidden" name="tooth_number" :value="currentTooth">

                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Condition</label>
                    <select name="status" x-model="formStatus"
                            class="block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                        <option value="healthy">Healthy (remove from chart)</option>
                        @foreach ($toothMeta as $key => $statuses)
                            <option value="{{ $key }}">{{ $statuses['label'] }}</option>
                        @endforeach
                    </select>

                    <label class="mt-3.5 block text-sm font-medium text-slate-700 mb-1.5">Note</label>
                    <textarea name="note" x-model="formNote" rows="2"
                              class="block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white"
                              placeholder="Optional note…"></textarea>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="currentTooth = ''"
                                class="rounded-full bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-200 transition">Cancel</button>
                        <x-primary-button>Save Tooth</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
