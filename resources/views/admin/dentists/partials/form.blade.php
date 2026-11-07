<div class="space-y-5">
    <!-- Link to user account -->
    <div>
        <x-input-label for="user_id" value="Linked User Account (optional)" />
        <select
            id="user_id"
            name="user_id"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/60 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white"
        >
            <option value="" @selected(! old('user_id', $dentist?->user_id))>
                — No account (dentist has no login yet) —
            </option>
            @foreach ($availableUsers as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $dentist?->user_id) === (string) $user->id)>
                    {{ $user->name }} · {{ $user->email }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">
            Choose a user account that this dentist can log in with. You can leave this empty now and link it later.
        </p>
        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="lastname" value="Lastname" />
            <x-text-input id="lastname" name="lastname" type="text" :value="old('lastname', $dentist?->lastname)" required autofocus />
            <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="firstname" value="Firstname" />
            <x-text-input id="firstname" name="firstname" type="text" :value="old('firstname', $dentist?->firstname)" required />
            <x-input-error :messages="$errors->get('firstname')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="middlename" value="Middlename (optional)" />
            <x-text-input id="middlename" name="middlename" type="text" :value="old('middlename', $dentist?->middlename)" />
            <x-input-error :messages="$errors->get('middlename')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="date_of_birth" value="Date of Birth (optional)" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" :value="old('date_of_birth', $dentist?->date_of_birth?->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="mobile_number" value="Mobile Number" />
            <x-text-input id="mobile_number" name="mobile_number" type="text" :value="old('mobile_number', $dentist?->mobile_number)" required placeholder="09XXXXXXXXX" />
            <x-input-error :messages="$errors->get('mobile_number')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="gender" value="Gender" />
            <select
                id="gender"
                name="gender"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/60 text-slate-900 shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/25 focus:bg-white"
                required
            >
                <option value="" @selected(! old('gender', $dentist?->gender)) disabled>— Select —</option>
                @foreach (['male', 'female', 'other'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $dentist?->gender) === $gender)>
                        {{ ucfirst($gender) }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="license_number" value="License Number (PRC)" />
            <x-text-input id="license_number" name="license_number" type="text" :value="old('license_number', $dentist?->license_number)" required placeholder="e.g. DMD-00001" />
            <x-input-error :messages="$errors->get('license_number')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="specialty" value="Specialty (optional)" />
            <x-text-input id="specialty" name="specialty" type="text" :value="old('specialty', $dentist?->specialty)" placeholder="e.g. Orthodontics" />
            <x-input-error :messages="$errors->get('specialty')" class="mt-2" />
        </div>
    </div>
</div>
