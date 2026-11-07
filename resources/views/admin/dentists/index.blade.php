<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
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
            </div>
        </div>
    </div>
</x-app-layout>
