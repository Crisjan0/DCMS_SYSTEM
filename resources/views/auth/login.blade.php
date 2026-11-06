<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4 rounded-xl bg-emerald-50 px-4 py-2.5 text-center font-medium text-emerald-700 ring-1 ring-emerald-600/15" :status="session('status')" />

    <div class="mb-6 text-center">
        <h1 class="text-[22px] font-bold tracking-tight text-clinic-950">Welcome back</h1>
        <p class="mt-1 text-sm text-slate-500">Log in to your account to continue</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="rounded-md text-xs font-medium text-clinic-700 hover:text-clinic-900 focus:outline-none focus:ring-2 focus:ring-clinic-500" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="mt-1.5 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="mt-5 inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-clinic-600 shadow-sm focus:ring-clinic-500" name="remember">
            <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="mt-6 w-full py-3">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-clinic-700 hover:text-clinic-900">
                Sign up
            </a>
        </p>
    @endif
</x-guest-layout>
