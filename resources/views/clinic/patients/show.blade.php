<x-app-layout>
    <x-slot name="header">
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                {{ $patient->fullname() }} — Patient Record
            </h2>
            <div class="flex items-center gap-4">
                <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900 transition">
                    🖨 Print Record
                </button>
                <a href="{{ route('clinic.patients.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back to patients</a>
            </div>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Patient summary card -->
            <div class="rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 px-6 py-5">
                <div class="flex flex-wrap items-start gap-4">
                    <!-- Initials avatar -->
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-clinic-600 text-lg font-bold text-white ring-4 ring-clinic-600/15">
                        {{ strtoupper(mb_substr($patient->firstname, 0, 1).mb_substr($patient->lastname, 0, 1)) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-lg font-bold text-clinic-950">{{ $patient->fullname() }}</p>
                        <p class="text-sm text-slate-500">{{ $patient->user?->email }} · {{ $patient->mobile_number ?? 'no mobile on file' }}</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                                {{ $patient->gender ? ucfirst($patient->gender) : '—' }}
                                @if ($patient->date_of_birth)
                                    · {{ $patient->date_of_birth->age }} yrs
                                @endif
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $patient->patient_type ? ucfirst($patient->patient_type) : 'Regular' }} patient</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $patient->civil_status ? ucfirst($patient->civil_status) : '—' }}</span>
                            @if ($medicalHistory && count($medicalHistory->activeConditions()) > 0)
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 font-semibold text-rose-700 ring-1 ring-rose-200">⚠ {{ implode(' · ', $medicalHistory->activeConditions()) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab nav -->
            <div class="no-print flex flex-wrap gap-2">
                @foreach ([
                    'overview' => 'Overview',
                    'medical-history' => 'Medical History',
                    'tooth-chart' => 'Tooth Chart',
                    'treatments' => 'Treatments',
                    'billing' => 'Billing',
                ] as $key => $label)
                    <a href="{{ route('clinic.patients.show', ['patient' => $patient, 'tab' => $key]) }}"
                       @class([
                           'inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold transition-colors duration-150',
                           'bg-clinic-600 text-white shadow-[0_12px_25px_-12px_rgba(26,148,107,0.8)]' => $tab === $key,
                           'bg-white text-slate-600 ring-1 ring-clinic-950/10 hover:bg-clinic-50' => $tab !== $key,
                       ])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <!-- Tab content -->
            @switch($tab)
                @case('overview')
                    @include('clinic.patients.partials.overview')
                    @break

                @case('medical-history')
                    @include('clinic.patients.partials.medical-history')
                    @break

                @case('tooth-chart')
                    @include('clinic.patients.partials.tooth-chart')
                    @break

                @case('treatments')
                    @include('clinic.patients.partials.treatments')
                    @break

                @case('billing')
                    @include('clinic.patients.partials.billing')
                    @break
            @endswitch
        </div>
    </div>
</x-app-layout>
