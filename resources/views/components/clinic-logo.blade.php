@props(['ring' => 'ring-1 ring-clinic-600/15'])

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 via-emerald-500 to-teal-600 $ring"]) }}>
    <svg viewBox="0 0 24 24" fill="currentColor" class="h-[62%] w-[62%] text-white" aria-hidden="true">
        <path d="M12 3C7.6 3 5 6.1 5 10c0 2.2.8 3.4 1.3 4.8.4 1.1.6 2.7.8 4.2.2 1.7.6 2.7 1.4 2.7.9 0 1.2-1.2 1.5-3.1.3-1.8.7-3.2 2-3.2s1.7 1.4 2 3.2c.3 1.9.6 3.1 1.5 3.1.8 0 1.2-1 1.4-2.7.2-1.5.4-3.1.8-4.2.5-1.4 1.3-2.6 1.3-4.8 0-3.9-2.6-7-7-7Z" />
    </svg>
</span>
