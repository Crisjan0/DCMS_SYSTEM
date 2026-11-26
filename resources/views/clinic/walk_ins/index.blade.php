<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                🚶 Walk-In Visits
            </h2>
            <a href="{{ route('forecast.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back to forecast</a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <p class="px-1 mb-4 text-sm text-slate-500">
                Log unbooked patients you served on the spot. Walk-ins are added to the daily volume stats,
                so the forecast learns from the clinic's <strong>true</strong> attendance — not just bookings.
                Today's walk-ins so far: <strong>{{ $todayCount }}</strong>
            </p>

            <div class="grid gap-6 lg:grid-cols-2 items-start">
                <!-- Record walk-in form -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">➕ Record a walk-in visit</h3>
                    </div>

                    <form method="POST" action="{{ route('clinic.walk-ins.store') }}" class="px-6 py-5 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="service" value="Service" />
                            <select id="service" name="service" required
                                class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                <option value="">— Select service —</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service }}" @selected(old('service') === $service)>{{ $service }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('service')" class="mt-2" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="patient_id" value="Patient (if known)" />
                                <select id="patient_id" name="patient_id"
                                    class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                    <option value="">— Anonymous walk-in —</option>
                                    @foreach ($patients as $person)
                                        <option value="{{ $person->id }}" @selected(old('patient_id') == $person->id)>
                                            {{ $person->fullname() }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-slate-400">Link a known patient so their treatment can be charted and billed later.</p>
                                <x-input-error :messages="$errors->get('patient_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="dentist_id" value="Handled by dentist (optional)" />
                                <select id="dentist_id" name="dentist_id"
                                    class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                    <option value="">— Not recorded —</option>
                                    @foreach ($dentists as $dentist)
                                        <option value="{{ $dentist->id }}" @selected(old('dentist_id') == $dentist->id)>
                                            {{ $dentist->user?->name ?? 'Dentist #'.$dentist->id }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('dentist_id')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="visit_date" value="Visit date" />
                                <x-text-input id="visit_date" name="visit_date" type="date" required
                                    :value="old('visit_date', today()->toDateString())" :max="today()->toDateString()" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('visit_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="count" value="Number of patients" />
                                <x-text-input id="count" name="count" type="number" min="1" max="100" required
                                    value="{{ old('count', 1) }}" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('count')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notes" value="Notes (optional)" />
                            <x-text-input id="notes" name="notes" placeholder="e.g. referred by a friend, follow-up scanning"
                                value="{{ old('notes') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>Record walk-in</x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Recent walk-ins -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">📋 Recent walk-in entries</h3>
                    </div>

                    <div class="px-6 py-5">
                        @if ($visits->isEmpty())
                            <p class="text-sm text-slate-400">No walk-ins recorded yet. Every entry here improves the accuracy of the volume forecast.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($visits as $visit)
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
                                        <span class="text-slate-700">
                                            🦷 {{ $visit->service }}
                                            @if ($visit->patient)
                                                — <span class="font-semibold">{{ $visit->patient->fullname() }}</span>
                                            @else
                                                — <span class="text-slate-400">Anonymous</span>
                                            @endif
                                            @if ($visit->dentist)
                                                <span class="text-xs text-slate-400">({{ $visit->dentist->user?->name ?? '—' }})</span>
                                            @endif
                                        </span>
                                        <span class="flex items-center gap-3">
                                            <span class="text-xs text-slate-400 whitespace-nowrap">
                                                {{ $visit->count }}× · {{ $visit->visit_date->format('M d, Y') }}
                                            </span>
                                            <form method="POST" action="{{ route('clinic.walk-ins.destroy', $visit) }}"
                                                  onsubmit="return confirm('Remove this walk-in entry?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Delete</button>
                                            </form>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
