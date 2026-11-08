{{-- Treatment timeline for a patient.
     Expects: $patient, $treatments, optional $canManage (defaults true). --}}
@php($canManage = $canManage ?? true)

<div class="space-y-6">
    <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
        <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-lg text-clinic-950">🩺 Treatment Records</h3>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500">{{ $treatments->count() }} recorded</span>
                @if ($canManage)
                    <a href="{{ route('clinic.patients.treatments.create', $patient) }}"
                       class="no-print inline-flex items-center px-3 py-1.5 bg-clinic-600 rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                        + Chart Treatment
                    </a>
                @endif
            </div>
        </div>

        <ul class="divide-y divide-slate-100">
            @forelse ($treatments as $record)
                <li class="px-6 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-800">{{ $record->procedure }}</span>
                                @if ($record->tooth_number)
                                    <span class="inline-flex rounded bg-slate-800 px-2 py-0.5 text-[11px] font-bold text-white">Tooth {{ $record->tooth_number }}</span>
                                @endif
                                @if ($record->invoiceItems->isNotEmpty())
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">💰 Billed</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                📅 {{ $record->treatment_date->format('M d, Y') }} · Dr. {{ $record->dentist?->fullname() ?? '—' }}
                                @if ($record->follow_up_date)
                                    · 🔁 Follow-up {{ $record->follow_up_date->format('M d, Y') }}
                                @endif
                            </p>
                            @if ($record->diagnosis)
                                <p class="mt-1.5 text-sm text-slate-700"><span class="font-medium">Diagnosis:</span> {{ $record->diagnosis }}</p>
                            @endif
                            @if ($record->treatment_notes)
                                <p class="mt-1 text-sm text-slate-500">{{ $record->treatment_notes }}</p>
                            @endif
                        </div>

                        @if ($canManage)
                            <div class="no-print flex items-center gap-2 shrink-0">
                                <a href="{{ route('clinic.treatments.edit', $record) }}"
                                   class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">Edit</a>
                                <form action="{{ route('clinic.treatments.destroy', $record) }}" method="POST"
                                      onsubmit="return confirm('Delete this treatment record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 ring-1 ring-rose-200 hover:bg-rose-100 transition">Delete</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-slate-400">No treatment records yet.</li>
            @endforelse
        </ul>
    </div>
</div>
