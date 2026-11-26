<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                {{ __('Reschedule Appointment') }}
            </h2>
            <a href="{{ route('appointments.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← My appointments</a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Current slot -->
            <div class="rounded-3xl bg-clinic-50 ring-1 ring-clinic-200 px-6 py-4">
                <p class="text-sm text-clinic-800">
                    <strong>{{ $appointment->service }}</strong> ·
                    📅 {{ $appointment->scheduled_date->format('l, M d, Y') }} ·
                    🕐 {{ \Illuminate\Support\Carbon::parse($appointment->scheduled_time)->format('g:i A') }}
                    @if ($appointment->dentist)
                        · Dr. {{ $appointment->dentist->fullname() }}
                    @endif
                    <span class="inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-clinic-700 ring-1 ring-clinic-200 ml-1">{{ ucfirst($appointment->status) }}</span>
                </p>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <form method="POST" action="{{ route('appointments.reschedule', $appointment) }}" class="px-6 py-5 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="scheduled_date" value="New Date" />
                        <x-text-input
                            id="scheduled_date"
                            name="scheduled_date"
                            type="date"
                            :min="today()->toDateString()"
                            required
                            class="mt-1.5" />
                        <p class="mt-1.5 text-xs text-slate-400">The clinic is closed on Sundays.</p>
                        <x-input-error :messages="$errors->get('scheduled_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="scheduled_time" value="New Time (30-minute slots)" />
                        <select
                            id="scheduled_time"
                            name="scheduled_time"
                            required
                            class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                            <option value="">— Pick a time —</option>
                            @foreach ($timeSlots as $slot)
                                <option value="{{ $slot }}" @selected(old('scheduled_time') === $slot)>
                                    {{ \Illuminate\Support\Carbon::parse($slot)->format('g:i A') }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('scheduled_time')" class="mt-2" />
                    </div>

                    <p class="text-xs text-slate-400">Your booking status stays the same; the clinic may contact you if the slot becomes unavailable.</p>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('appointments.index') }}"
                           class="inline-flex items-center rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-200 transition">
                            Cancel
                        </a>
                        <x-primary-button>Move Appointment</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
