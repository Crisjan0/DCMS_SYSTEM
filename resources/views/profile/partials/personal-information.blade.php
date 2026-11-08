<section>
    <header>
        <h2 class="text-lg font-medium text-clinic-950">
            {{ __('Personal Information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('View and update your patient record from the clinic database.') }}
        </p>
    </header>

    <div class="mt-6">
        <x-primary-button type="button" x-on:click="$dispatch('open-personal-information')">
            📋 {{ __('View Personal Information') }}
        </x-primary-button>
    </div>

    <!-- ── Personal information modal ── -->
    @php($patient = $user->patient)
    @php($hasPatientErrors = $errors->getBag('patientUpdate')->any())

    <div
        x-data="{ open: false, editing: false, previewUrl: null }"
        x-on:open-personal-information.window="open = true"
        x-on:keydown.escape.window="open = false"
        x-init="@if($hasPatientErrors) open = true; editing = true @endif"
        x-cloak
    >
        <!-- Backdrop -->
        <div x-show="open" class="fixed inset-0 z-40 bg-clinic-950/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- Panel -->
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none" role="dialog" aria-modal="true" aria-labelledby="personal-info-title">
            <div class="pointer-events-auto w-full max-w-md max-h-[85dvh] overflow-y-auto rounded-3xl bg-white shadow-[0_40px_70px_-30px_rgba(10,47,37,0.55)] ring-1 ring-clinic-950/10"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-y-4">

                <!-- Header -->
                <div class="flex items-start justify-between gap-3 px-6 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        @if ($patient?->profile_picture)
                            <img src="{{ asset('storage/'.$patient->profile_picture) }}" alt="Profile picture"
                                 class="h-11 w-11 shrink-0 rounded-full object-cover ring-1 ring-clinic-950/10">
                        @else
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-clinic-600 text-sm font-bold text-white uppercase">
                                {{ substr($patient?->firstname ?? $user->name, 0, 2) }}
                            </div>
                        @endif

                        <div class="min-w-0">
                            <h3 id="personal-info-title" class="font-semibold text-base text-clinic-950 leading-tight">
                                {{ __('Patient Record') }}
                            </h3>
                            <p class="text-xs text-slate-400 truncate">
                                {{ $user->email }}
                            </p>
                        </div>
                    </div>
                    <button type="button" x-on:click="open = false; editing = false" aria-label="Close"
                            class="shrink-0 rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- ═══ VIEW MODE ═══ -->
                <div x-show="! editing" class="px-6 py-4 space-y-2.5 text-sm">
                    @if ($patient)
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Full name</span>
                            <span class="font-medium text-slate-800 text-right">{{ $patient->fullname() }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Date of birth</span>
                            <span class="text-slate-700 text-right">
                                {{ $patient->date_of_birth ? $patient->date_of_birth->format('M d, Y').' ('.((int) $patient->date_of_birth->diffInYears(now())).' yrs old)' : '—' }}
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Mobile number</span>
                            <span class="text-slate-700">{{ $patient->mobile_number ?? '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Gender</span>
                            <span class="text-slate-700">{{ $patient->gender ? ucfirst($patient->gender) : '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Civil status</span>
                            <span class="text-slate-700">{{ $patient->civil_status ? ucfirst($patient->civil_status) : '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Address</span>
                            <span class="text-slate-700 text-right">{{ $patient->address ?? '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Occupation</span>
                            <span class="text-slate-700 text-right">{{ $patient->occupation ?? '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Patient type</span>
                            <span class="text-slate-700">{{ $patient->patient_type ? ucfirst($patient->patient_type) : '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-slate-400 shrink-0">Account type</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $patient->role === 'staff' ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                                {{ $patient->role === 'staff' ? '🩺 Staff' : '🦷 Patient' }}
                            </span>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-slate-400">
                            👤 This account has no patient record in the clinic database.
                        </div>
                    @endif
                </div>

                <!-- ═══ EDIT MODE ═══ -->
                @if ($patient)
                    <form x-show="editing"
                          method="POST"
                          action="{{ route('profile.patient.update') }}"
                          enctype="multipart/form-data"
                          class="px-6 py-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Profile picture -->
                        <div>
                            <x-input-label for="profile_picture" :value="__('Profile picture')" />
                            <div class="mt-1 flex items-center gap-4">
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" alt="New profile picture preview"
                                         class="h-14 w-14 rounded-full object-cover ring-2 ring-clinic-200">
                                </template>
                                <div class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-clinic-50 text-clinic-700 ring-1 ring-clinic-200 overflow-hidden">
                                    @if ($patient->profile_picture)
                                        <img src="{{ asset('storage/'.$patient->profile_picture) }}" alt="Current profile picture"
                                             class="h-full w-full object-cover">
                                    @else
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <input id="profile_picture" type="file" name="profile_picture" accept="image/png,image/jpeg,image/webp"
                                           @change="previewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                           class="block w-full text-sm text-slate-500 border border-clinic-950/10 rounded-2xl bg-white/70 shadow-sm transition cursor-pointer file:mr-3 file:rounded-full file:border-0 file:bg-clinic-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-clinic-700">
                                    <p class="mt-1 text-xs text-slate-400">JPG, PNG or WEBP — max 2MB</p>
                                    @error('profile_picture', 'patientUpdate')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                    @if (isset($patient->profile_picture) && $patient->profile_picture)
                                        <p class="mt-1 text-xs text-slate-400">Uploading a new picture replaces the current one.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="firstname" :value="__('First name')" />
                                <x-text-input id="firstname" name="firstname" type="text" class="mt-1 block w-full"
                                              :value="old('firstname', $patient->firstname)" required />
                                @error('firstname', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="middlename" :value="__('Middle name')" />
                                <x-text-input id="middlename" name="middlename" type="text" class="mt-1 block w-full"
                                              :value="old('middlename', $patient->middlename)" />
                                @error('middlename', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <x-input-label for="lastname" :value="__('Last name')" />
                            <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full"
                                          :value="old('lastname', $patient->lastname)" required />
                            @error('lastname', 'patientUpdate')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full"
                                              :value="old('date_of_birth', $patient->date_of_birth?->toDateString())" required />
                                @error('date_of_birth', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="mobile_number" :value="__('Mobile number')" />
                                <x-text-input id="mobile_number" name="mobile_number" type="text" class="mt-1 block w-full"
                                              :value="old('mobile_number', $patient->mobile_number)" required />
                                @error('mobile_number', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender" class="mt-1 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $key => $label)
                                        <option value="{{ $key }}" @selected(old('gender', $patient->gender) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('gender', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="civil_status" :value="__('Civil status')" />
                                <select id="civil_status" name="civil_status" class="mt-1 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                    @foreach (['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'separated' => 'Separated', 'divorced' => 'Divorced'] as $key => $label)
                                        <option value="{{ $key }}" @selected(old('civil_status', $patient->civil_status) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('civil_status', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <x-input-label for="address" :value="__('Address')" />
                            <textarea id="address" name="address" rows="2"
                                      class="mt-1 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">{{ old('address', $patient->address) }}</textarea>
                            @error('address', 'patientUpdate')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="occupation" :value="__('Occupation')" />
                                <x-text-input id="occupation" name="occupation" type="text" class="mt-1 block w-full"
                                              :value="old('occupation', $patient->occupation)" />
                                @error('occupation', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="patient_type" :value="__('Patient type')" />
                                <select id="patient_type" name="patient_type" class="mt-1 block w-full rounded-2xl border border-clinic-950/10 bg-white/70 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                    @foreach (['regular' => 'Regular', 'new' => 'New', 'returning' => 'Returning'] as $key => $label)
                                        <option value="{{ $key }}" @selected(old('patient_type', $patient->patient_type) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('patient_type', 'patientUpdate')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="rounded-2xl bg-clinic-50 px-4 py-3 text-xs text-clinic-800 ring-1 ring-clinic-200">
                            ℹ️ Account type (Patient/Staff) can only be changed by clinic staff.
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <x-primary-button>💾 {{ __('Save Changes') }}</x-primary-button>
                            <button type="button" x-on:click="editing = false"
                                    class="rounded-full px-4 py-2.5 text-sm font-semibold text-clinic-700 ring-1 ring-clinic-200 hover:bg-clinic-50 transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                @endif

                <!-- ═══ VIEW MODE FOOTER ═══ -->
                @if ($patient)
                    <div x-show="! editing" class="px-6 pb-6 flex items-center gap-3">
                        <button type="button" x-on:click="editing = true"
                                class="w-full rounded-full bg-clinic-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-clinic-700 transition">
                            ✏️ Update Information
                        </button>
                        <button type="button" x-on:click="open = false; editing = false"
                                class="rounded-full px-4 py-2.5 text-sm font-semibold text-clinic-700 ring-1 ring-clinic-200 hover:bg-clinic-50 transition">
                            Close
                        </button>
                    </div>
                @else
                    <div class="px-6 pb-6">
                        <button type="button" x-on:click="open = false"
                                class="w-full rounded-full bg-clinic-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-clinic-700 transition">
                            Close
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
