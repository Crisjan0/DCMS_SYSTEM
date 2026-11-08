<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('My Health Record') }}
        </h2>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($patient === null)
                <div class="rounded-3xl bg-amber-50 ring-1 ring-amber-200 px-6 py-5 text-sm text-amber-800">
                    ⚠ Your patient profile is not complete yet.
                    <a href="{{ route('profile.edit') }}" class="font-semibold underline hover:text-amber-900">Complete it here</a>
                    to unlock your health record.
                </div>
            @else
                <!-- Health declaration -->
                @include('clinic.patients.partials.medical-form', [
                    'action' => route('my-record.medical-history.save'),
                    'medicalHistory' => $medicalHistory,
                ])

                <!-- Read-only odontogram -->
                @include('clinic.patients.partials.tooth-chart', [
                    'patient' => $patient,
                    'toothRecords' => $toothRecords,
                    'canEdit' => false,
                ])

                <!-- My treatments (read-only) -->
                @include('clinic.patients.partials.treatments', [
                    'patient' => $patient,
                    'treatments' => $treatments,
                    'canManage' => false,
                ])
            @endif
        </div>
    </div>
</x-app-layout>
