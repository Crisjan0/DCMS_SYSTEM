<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
                {{ __('Dentists') }}
            </h2>

            <a href="{{ route('admin.dentists.create') }}" class="inline-flex items-center px-4 py-2 bg-clinic-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wider hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500/40 transition">
                + Add Dentist
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <!-- ── Desktop table (≥ md) ── -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Dentist</th>
                                <th class="px-6 py-3">Specialty</th>
                                <th class="px-6 py-3">License No.</th>
                                <th class="px-6 py-3">Mobile</th>
                                <th class="px-6 py-3">Linked Account</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($dentists as $dentist)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        Dr. {{ $dentist->fullname() }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $dentist->specialty ?? '—' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $dentist->license_number }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $dentist->mobile_number }}</td>
                                    <td class="px-6 py-4">
                                        @if ($dentist->user)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $dentist->user->email }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                No account
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.dentists.edit', $dentist) }}" class="text-clinic-600 hover:text-clinic-800 font-medium">Edit</a>

                                        <form action="{{ route('admin.dentists.destroy', $dentist) }}" method="POST" class="inline ms-3"
                                              onsubmit="return confirm('Remove Dr. {{ $dentist->lastname }}? Linked user account will be kept but unlinked.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        No dentists yet. Click <span class="font-medium">+ Add Dentist</span> to create one.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ── Mobile cards (< md) ── -->
                <div class="p-4 space-y-3 md:hidden">
                    @forelse ($dentists as $dentist)
                        <div x-data="{ open: false }" class="overflow-hidden rounded-2xl bg-white ring-1 ring-clinic-950/10 shadow-[0_16px_36px_-24px_rgba(10,47,37,0.5)]">
                            <!-- Summary row (tap to open) -->
                            <button type="button" @click="open = ! open" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 leading-snug">Dr. {{ $dentist->fullname() }}</p>
                                    <p class="mt-1 text-sm text-slate-500 truncate">
                                        {{ $dentist->specialty ?? 'General Dentistry' }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    @if ($dentist->user)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border border-emerald-200 bg-emerald-50 text-emerald-700">
                                            🔗 Linked
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border border-amber-200 bg-amber-50 text-amber-700">
                                            No account
                                        </span>
                                    @endif
                                    <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </button>

                            <!-- Details (expanded) -->
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0">
                                <div class="border-t border-slate-100 px-4 py-3 space-y-2.5 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">License No.</span>
                                        <span class="text-slate-600">{{ $dentist->license_number }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Mobile</span>
                                        <span class="text-slate-600">{{ $dentist->mobile_number }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Specialty</span>
                                        <span class="text-slate-600">{{ $dentist->specialty ?? '—' }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-400">Login account</span>
                                        @if ($dentist->user)
                                            <span class="text-slate-600 truncate">{{ $dentist->user->email }}</span>
                                        @else
                                            <span class="text-amber-700">Not linked</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="px-4 pb-4 flex items-center gap-3">
                                    <a href="{{ route('admin.dentists.edit', $dentist) }}"
                                       class="flex-1 rounded-full bg-clinic-50 px-4 py-2.5 text-center text-sm font-semibold text-clinic-700 ring-1 ring-clinic-200 hover:bg-clinic-100 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.dentists.destroy', $dentist) }}" method="POST" class="flex-1"
                                          onsubmit="return confirm('Remove Dr. {{ $dentist->lastname }}? Linked user account will be kept but unlinked.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded-full bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-400">
                            No dentists yet. Tap <span class="font-medium">+ Add Dentist</span> to create one.
                        </div>
                    @endforelse
                </div>

                @if ($dentists->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $dentists->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
