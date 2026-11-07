<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Book Appointment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-slate-500 mb-6">
                        Pick a service, date and time slot. The staff will then assign an available dentist — your
                        request shows as <span class="font-medium text-amber-700">Pending</span> in My Appointments
                        until then.
                    </p>

                    @if ($needsProfile)
                        <div class="mb-6 p-4 rounded-lg bg-sky-50 border border-sky-200 text-sky-800 text-sm">
                            👤 You don't have a patient profile yet — complete the details below and we'll create one
                            together with your booking.
                        </div>
                    @endif

                    <form action="{{ route('appointments.store') }}" method="POST" class="space-y-6">
                        @csrf

                        @if ($needsProfile)
                            <div class="rounded-xl border border-slate-200 p-5 space-y-5 bg-slate-50/60">
                                <h3 class="font-semibold text-slate-700 text-sm uppercase tracking-wide">Patient profile</h3>

                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="lastname" value="Lastname" />
                                        <x-text-input id="lastname" name="lastname" type="text" :value="old('lastname', $lastnameGuess)" required />
                                        <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="firstname" value="Firstname" />
                                        <x-text-input id="firstname" name="firstname" type="text" :value="old('firstname', $firstnameGuess)" required />
                                        <x-input-error :messages="$errors->get('firstname')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="middlename" value="Middlename (optional)" />
                                        <x-text-input id="middlename" name="middlename" type="text" :value="old('middlename')" />
                                        <x-input-error :messages="$errors->get('middlename')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="date_of_birth" value="Date of Birth" />
                                        <x-text-input id="date_of_birth" name="date_of_birth" type="date" :value="old('date_of_birth')" required />
                                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="mobile_number" value="Mobile Number" />
                                        <x-text-input id="mobile_number" name="mobile_number" type="text" :value="old('mobile_number')" required placeholder="09XXXXXXXXX" />
                                        <x-input-error :messages="$errors->get('mobile_number')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="gender" value="Gender" />
                                        <select id="gender" name="gender" required
                                                class="block w-full rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                            <option value="" @selected(! old('gender')) disabled>— Select —</option>
                                            @foreach (['male', 'female', 'other'] as $gender)
                                                <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ ucfirst($gender) }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="civil_status" value="Civil Status" />
                                        <select id="civil_status" name="civil_status" required
                                                class="block w-full rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">
                                            <option value="" @selected(! old('civil_status')) disabled>— Select —</option>
                                            @foreach (['single', 'married', 'widowed', 'separated', 'divorced'] as $status)
                                                <option value="{{ $status }}" @selected(old('civil_status') === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('civil_status')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="occupation" value="Occupation (optional)" />
                                        <x-text-input id="occupation" name="occupation" type="text" :value="old('occupation')" />
                                        <x-input-error :messages="$errors->get('occupation')" class="mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="address" value="Address" />
                                    <textarea id="address" name="address" rows="2" required
                                              class="block w-full rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25">{{ old('address') }}</textarea>
                                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                                </div>
                            </div>
                        @endif

                        <div class="space-y-5">
                            <div>
                                <x-input-label for="service" value="Service" />
                                <select id="service" name="service" required
                                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/60 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white">
                                    <option value="" @selected(! old('service')) disabled>— Select a service —</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service }}" @selected(old('service') === $service)>{{ $service }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('service')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="scheduled_date" value="Preferred Date" />
                                <x-text-input id="scheduled_date" name="scheduled_date" type="date"
                                              :value="old('scheduled_date')" :min="now()->toDateString()" required />
                                <p class="mt-1 text-xs text-slate-500">Open Mon–Sat, 9:00 AM – 5:00 PM · Closed Sundays.</p>
                                <x-input-error :messages="$errors->get('scheduled_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label value="Time Slot" />
                                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-1">
                                    @foreach ($timeSlots as $slot)
                                        <label class="relative">
                                            <input type="radio" name="scheduled_time" value="{{ $slot }}"
                                                   class="peer sr-only" @checked(old('scheduled_time') === $slot)>
                                            <span class="flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-sm text-slate-700 cursor-pointer
                                                   peer-checked:border-clinic-500 peer-checked:bg-clinic-50 peer-checked:text-clinic-700 peer-checked:font-medium
                                                   peer-disabled:opacity-40 peer-disabled:cursor-not-allowed peer-disabled:line-through">
                                                {{ $slot }}<span data-slot-state class="text-[10px] font-semibold"></span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p id="slot-availability-note" class="mt-2 text-xs text-slate-400">Pick a date to check slot availability.</p>
                                <x-input-error :messages="$errors->get('scheduled_time')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="notes" value="Notes (optional)" />
                                <textarea id="notes" name="notes" rows="3"
                                          class="block w-full rounded-xl border border-slate-200 bg-slate-50/60 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                            <a href="{{ route('appointments.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">View My Appointments</a>

                            <x-primary-button>
                                {{ __('Submit Request') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const dateEl = document.getElementById('scheduled_date');
            const noteEl = document.getElementById('slot-availability-note');
            const availabilityUrl = "{{ route('appointments.availability') }}";

            function setNote(text) {
                if (text === null) {
                    noteEl.classList.add('hidden');
                } else {
                    noteEl.classList.remove('hidden');
                    noteEl.textContent = text;
                }
            }

            function paint(data) {
                document.querySelectorAll('input[name="scheduled_time"]').forEach(function (input) {
                    const label = input.closest('label');
                    const state = data.closed ? null : data.slots[input.value];
                    const isBlocked = !state || state.full || state.past;

                    input.disabled = isBlocked;
                    if (isBlocked && input.checked) {
                        input.checked = false;
                    }
                    label.querySelector('[data-slot-state]').textContent = state && state.full ? 'Full' : '';
                });
            }

            async function refresh() {
                if (!dateEl.value) {
                    setNote('Pick a date to check slot availability.');
                    return;
                }

                setNote('Checking availability…');

                try {
                    const res = await fetch(availabilityUrl + '?date=' + dateEl.value);
                    if (!res.ok) {
                        setNote('Could not check availability right now.');
                        return;
                    }

                    const data = await res.json();
                    paint(data);
                    setNote(data.closed ? '🚫 The clinic is closed on Sundays — please pick another date.' : 'Slots marked "Full" have reached capacity — pick a free one.');
                } catch (err) {
                    setNote('Could not check availability right now.');
                }
            }

            dateEl.addEventListener('change', refresh);

            if (dateEl.value) {
                refresh();
            }
        })();
    </script>
</x-app-layout>
