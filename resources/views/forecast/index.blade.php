@php($showForecast = true)

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                    🔮 {{ __('Volume Forecast') }}
                </h2>
                <p class="text-[13px] text-slate-500">
                    {{ __('Predicted clinic visits from historical data — ARIMA time-series model') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('forecast.regenerate') }}" class="flex items-center gap-2">
                    @csrf
                    <label for="algorithm" class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        {{ __('Model') }}
                    </label>
                    <select id="algorithm" name="algorithm"
                        class="rounded-lg border-clinic-950/10 bg-white px-2.5 py-1.5 text-sm text-clinic-950 shadow-sm focus:border-clinic-500 focus:ring-1 focus:ring-clinic-500/40">
                        @foreach ($algorithms as $algorithm)
                            <option value="{{ $algorithm }}" @if ($algorithm === 'arima') selected @endif>
                                {{ $labels[$algorithm] ?? $algorithm }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-full bg-clinic-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500/40">
                        <span aria-hidden="true">⟳</span> {{ __('Regenerate') }}
                    </button>
                </form>

                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 ring-1 ring-clinic-950/10 transition hover:text-clinic-700">
                    {{ __('Dashboard') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-6">
        @include('dashboard.partials.forecast')
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4 pb-6">
        <div class="rounded-2xl bg-white px-5 py-4 text-[13px] text-slate-500 ring-1 ring-clinic-950/5">
            {{ __('Pick a model and press Regenerate to compare accuracy (MAPE). The forecast also re-trains automatically every Monday at 6:00 AM.') }}
            @if ($latestRun)
                <span class="block mt-1 text-slate-400">
                    {{ __('Latest run') }}: #{{ $latestRun->id }} · {{ $latestRun->algorithm }} · {{ $latestRun->ran_at?->format('M d, Y g:i A') }}
                </span>
            @endif
        </div>
    </div>
</x-app-layout>
