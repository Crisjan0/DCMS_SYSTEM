<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-clinic-600 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_14px_28px_-14px_rgba(26,148,107,0.85)] transition ease-in-out duration-150 hover:bg-clinic-700 focus:bg-clinic-700 active:bg-clinic-800 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60']) }}>
    {{ $slot }}
</button>
