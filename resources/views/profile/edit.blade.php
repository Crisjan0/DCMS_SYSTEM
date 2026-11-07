<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-clinic-950 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    @if (session('status') === 'personal-info-updated')
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                ✅ {{ __('Personal information updated successfully.') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="max-w-xl">
                    @include('profile.partials.personal-information')
                </div>
            </div>

            <div class="p-4 sm:p-8 rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
