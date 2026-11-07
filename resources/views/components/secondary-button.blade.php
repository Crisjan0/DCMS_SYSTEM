<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-full bg-white/80 px-5 py-2.5 text-sm font-semibold text-clinic-800 shadow-sm ring-1 ring-clinic-600/25 transition ease-in-out duration-150 hover:bg-white hover:ring-clinic-600/45 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60']) }}>
    {{ $slot }}
</button>
