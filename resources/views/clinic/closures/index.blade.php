<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                📅 Clinic Closures
            </h2>
            <a href="{{ route('forecast.index') }}" class="text-sm font-semibold text-clinic-700 hover:text-clinic-800">← Back to forecast</a>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <p class="px-1 mb-4 text-sm text-slate-500">
                Declare holidays and clinic closure days. Booking is blocked on these dates and the volume
                forecast predicts <strong>zero visits</strong> for them, keeping predictions realistic.
            </p>

            <div class="grid gap-6 lg:grid-cols-2 items-start">
                <!-- Add closure form -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">➕ Declare a closure</h3>
                    </div>

                    <form method="POST" action="{{ route('clinic.closures.store') }}" class="px-6 py-5 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="date" value="Date" />
                            <x-text-input id="date" name="date" type="date" required
                                :value="old('date')" :min="today()->toDateString()" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="name" value="Name / Reason" />
                            <x-text-input id="name" name="name" required placeholder="e.g. Christmas Day, Clinic Renovation"
                                value="{{ old('name') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="type" value="Type" />
                            <select id="type" name="type"
                                class="mt-1.5 block w-full rounded-2xl border border-clinic-950/10 bg-white text-slate-900 shadow-sm focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                <option value="holiday">Holiday (no clinic)</option>
                                <option value="closure">Branch closure</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>Save closure</x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Closures list -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="font-semibold text-lg text-clinic-950">🗓️ Upcoming &amp; past closures</h3>
                    </div>

                    <div class="px-6 py-5">
                        @if ($upcoming->isEmpty() && $past->isEmpty())
                            <p class="text-sm text-slate-400">No closures declared yet. The forecast assumes the clinic operates every day except Sundays.</p>
                        @endif

                        @if ($upcoming->isNotEmpty())
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Upcoming</p>
                            <ul class="space-y-2 mb-5">
                                @foreach ($upcoming as $closure)
                                    <li class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm">
                                        <span class="text-amber-900">
                                            <span class="font-semibold">{{ $closure->date->format('D, M d, Y') }}</span>
                                            — {{ $closure->name }}
                                            <span class="text-xs text-amber-700">({{ ucfirst($closure->type) }})</span>
                                        </span>
                                        <form method="POST" action="{{ route('clinic.closures.destroy', $closure) }}"
                                              onsubmit="return confirm('Remove this closure and allow booking again?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Delete</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($past->isNotEmpty())
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Past</p>
                            <ul class="space-y-2">
                                @foreach ($past as $closure)
                                    <li class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-400">
                                        <span>
                                            {{ $closure->date->format('M d, Y') }} — {{ $closure->name }}
                                            <span class="text-xs">({{ ucfirst($closure->type) }})</span>
                                        </span>
                                        <form method="POST" action="{{ route('clinic.closures.destroy', $closure) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-400 hover:text-rose-600 text-xs font-semibold">Remove</button>
                                        </form>
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
