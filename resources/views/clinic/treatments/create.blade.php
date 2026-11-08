<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                Chart Treatment — {{ $patient->fullname() }}
            </h2>
            <a href="{{ route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments']) }}"
               class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back to record</a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($appointment)
                <div class="mb-6 rounded-3xl bg-clinic-50 ring-1 ring-clinic-200 px-6 py-4">
                    <p class="text-sm text-clinic-800">
                        📅 Charting from visit: <strong>{{ $appointment->service }}</strong>
                        on {{ $appointment->scheduled_date->format('M d, Y') }} at {{ $appointment->scheduled_time }}
                        — saving will mark this visit <strong>completed</strong>.
                    </p>
                </div>
            @endif

            @include('clinic.treatments._form', [
                'action' => route('clinic.patients.treatments.store', $patient),
                'treatment' => null,
                'appointment' => $appointment,
                'patient' => $patient,
                'visitOptions' => $visitOptions,
                'dentists' => $dentists,
            ])
        </div>
    </div>
</x-app-layout>
