<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                Edit Treatment — {{ $patient->fullname() }}
            </h2>
            <a href="{{ route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments']) }}"
               class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back to record</a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('clinic.treatments._form', [
                'action' => route('clinic.treatments.update', $treatment),
                'treatment' => $treatment,
                'appointment' => $appointment,
                'patient' => $patient,
                'visitOptions' => $visitOptions,
                'dentists' => $dentists,
            ])
        </div>
    </div>
</x-app-layout>
