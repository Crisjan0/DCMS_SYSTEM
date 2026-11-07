<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Dentist') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('success'))
                        <div class="mb-5 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.dentists.store') }}" method="POST" class="space-y-6">
                        @csrf

                        @include('admin.dentists.partials.form')

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                            <a href="{{ route('admin.dentists.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>

                            <x-primary-button>
                                {{ __('Save Dentist') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
