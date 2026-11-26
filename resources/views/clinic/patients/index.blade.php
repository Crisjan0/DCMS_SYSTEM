<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                {{ __('Patients') }} — Clinic Records
            </h2>
            <span class="text-sm text-slate-500">{{ $patients->total() }} patients</span>
        </div>
    </x-slot>

    @include('layouts.partials.flash')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Search -->
            <div class="rounded-3xl bg-white/80 shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 p-4">
                <form method="GET" action="{{ route('clinic.patients.index') }}" class="flex gap-3">
                    <x-text-input
                        name="q"
                        placeholder="Search by name, mobile, or email…"
                        value="{{ $search }}"
                        class="flex-1" />
                    <button type="submit" class="inline-flex items-center rounded-full bg-clinic-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-clinic-700 transition">
                        Search
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('clinic.patients.index') }}" class="inline-flex items-center rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-200 transition">Clear</a>
                    @endif
                </form>
            </div>

            <!-- ── Desktop table (≥ md) ── -->
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Patient</th>
                                <th class="px-6 py-3">Contact</th>
                                <th class="px-6 py-3">Visits</th>
                                <th class="px-6 py-3">Upcoming</th>
                                <th class="px-6 py-3">Charted Teeth</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($patients as $person)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-800">{{ $person->fullname() }}</span>
                                        <div class="text-xs text-slate-400">{{ $person->user?->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 whitespace-nowrap">{{ $person->mobile_number ?? '—' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $person->visits_count }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $person->upcoming_count }}</td>
                                    <td class="px-6 py-4">
                                        @if ($person->tooth_records_count > 0)
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                                🦷 {{ $person->tooth_records_count }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('clinic.patients.show', $person) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-clinic-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 transition">
                                            Open Record
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        {{ $search !== '' ? 'No patients matched your search.' : 'No patients registered yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ── Mobile cards (< md) ── -->
                <div class="p-4 space-y-3 md:hidden">
                    @forelse ($patients as $person)
                        <a href="{{ route('clinic.patients.show', $person) }}"
                           class="block rounded-2xl bg-white ring-1 ring-clinic-950/10 shadow-[0_16px_36px_-24px_rgba(10,47,37,0.5)] px-4 py-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 leading-snug">{{ $person->fullname() }}</p>
                                    <p class="mt-1 text-xs text-slate-400 truncate">{{ $person->user?->email }}</p>
                                </div>
                                <span class="text-xs text-slate-500 whitespace-nowrap">{{ $person->visits_count }} visits</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">📅 {{ $person->upcoming_count }} upcoming</span>
                                @if ($person->tooth_records_count > 0)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700 ring-1 ring-amber-200">🦷 {{ $person->tooth_records_count }} teeth</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                            No patients found.
                        </div>
                    @endforelse
                </div>

                @if ($patients->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $patients->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
