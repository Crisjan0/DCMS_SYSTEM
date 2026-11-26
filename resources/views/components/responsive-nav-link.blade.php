@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block rounded-xl bg-clinic-100/80 px-4 py-3 text-start text-base font-semibold text-clinic-800 focus:outline-none focus:ring-2 focus:ring-clinic-500 transition duration-150 ease-in-out'
            : 'block rounded-xl px-4 py-3 text-start text-base font-medium text-slate-700 hover:bg-clinic-100/60 hover:text-clinic-800 focus:outline-none focus:text-clinic-800 focus:bg-clinic-100/60 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
