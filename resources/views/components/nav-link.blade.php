@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-clinic-100 px-4 py-2 text-[15px] font-semibold text-clinic-800 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2 transition-colors duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full px-4 py-2 text-[15px] font-medium text-slate-600 hover:bg-clinic-100/70 hover:text-clinic-800 focus:outline-none focus:text-clinic-800 focus:bg-clinic-100/70 transition-colors duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
