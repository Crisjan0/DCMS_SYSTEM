@props(['class' => 'h-9 w-9'])

@php
    // Show the logged-in user's own photo when one exists
    // (patients keep theirs in `patients`, dentists in `dentists`).
    $picture = Auth::user()?->patient?->profile_picture
        ?? Auth::user()?->dentist?->profile_picture;
@endphp

@if ($picture)
    <img src="{{ asset('storage/'.$picture) }}" alt="Your profile picture" loading="lazy"
         {{ $attributes->merge(['class' => $class.' rounded-full object-cover ring-1 ring-white/20']) }}>
@else
    <x-clinic-logo ring="ring-1 ring-white/20" :class="$class" {{ $attributes->except('class') }} />
@endif
