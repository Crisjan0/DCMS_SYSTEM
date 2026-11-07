<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('Assign Dentist') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Appointment summary -->
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="p-6 space-y-1">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Appointment request</div>

                        <form action="{{ route('staff.appointments.cancel', $appointment) }}" method="POST"
                              onsubmit="return confirm('Cancel this appointment request entirely?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">✖ Cancel request…</button>
                        </form>
                    </div>
                    <p class="text-slate-800">
                        <span class="font-semibold">👤 {{ $appointment->patient?->fullname() ?? 'Unknown' }}</span>
                        <span class="text-slate-400">· {{ $appointment->patient?->mobile_number }}</span>
                    </p>
                    <p class="text-slate-600 text-sm">
                        🦷 {{ $appointment->service }} ·
                        📅 {{ $appointment->scheduled_date->format('l, M d, Y') }} ·
                        🕐 {{ $appointment->scheduled_time }}
                    </p>
                    @if ($appointment->notes)
                        <p class="text-slate-500 text-sm italic">📝 "{{ $appointment->notes }}"</p>
                    @endif
                </div>
            </div>

            <form action="{{ route('staff.appointments.assign', $appointment) }}" method="POST"
                  class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                @csrf
                @method('PATCH')
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-clinic-950 mb-1">Pick an available dentist</h3>
                    <p class="text-sm text-slate-500 mb-5">
                        Only dentists free at this date &amp; time slot are selectable. Assignment confirms the appointment.
                    </p>

                    <x-input-error :messages="$errors->get('dentist_id')" class="mb-4" />

                    <div class="space-y-2">
                        @forelse ($dentists as $dentist)
                            @php($isBusy = $busyIds->contains($dentist->id))
                            <label class="relative block">
                                <input type="radio" name="dentist_id" value="{{ $dentist->id }}"
                                       class="peer sr-only" required @disabled($isBusy) @checked(old('dentist_id') == $dentist->id)>
                                <span class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 cursor-pointer transition
                                       peer-checked:border-clinic-500 peer-checked:bg-clinic-50
                                       peer-disabled:opacity-40 peer-disabled:cursor-not-allowed">
                                    <span>
                                        <span class="font-medium text-slate-800">Dr. {{ $dentist->fullname() }}</span>
                                        <span class="block text-xs text-slate-500">
                                            {{ $dentist->specialty ?? 'General Dentistry' }} · {{ $dentist->license_number }}
                                            @if ($isBusy)
                                                <span class="text-rose-600 font-medium">· 🔴 booked at this slot</span>
                                            @else
                                                <span class="text-emerald-600 font-medium">· 🟢 available</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="text-xs font-semibold uppercase tracking-wide {{ $isBusy ? 'text-rose-500' : 'text-clinic-600' }}">
                                        {{ $isBusy ? 'Busy' : 'Free' }}
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="text-slate-400 text-sm">No dentists registered yet.</p>
                        @endforelse
                    </div>

                    @if ($dentists->isNotEmpty() && $busyIds->count() >= $dentists->count())
                        <div class="mt-5 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                            ⚠️ All dentists are booked at this slot. Cancel the request and ask the patient to pick a new schedule.
                        </div>
                    @endif

                    @unless ($dentists->isEmpty())
                        <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                            <a href="{{ route('staff.appointments.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Back to queue</a>
                            <x-primary-button>{{ __('Confirm Assignment') }}</x-primary-button>
                        </div>
                    @endunless
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
