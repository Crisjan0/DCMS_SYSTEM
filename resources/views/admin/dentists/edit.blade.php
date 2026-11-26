<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('Edit Dentist') }} — Dr. {{ $dentist->fullname() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="p-6">
                    @if (session('success'))
                        <div class="mb-5 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.dentists.update', $dentist) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @include('admin.dentists.partials.form')

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                            <a href="{{ route('admin.dentists.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>

                            <x-primary-button>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
