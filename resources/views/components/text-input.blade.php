@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-gray-300 bg-white shadow-sm transition focus:border-clinic-500 focus:outline-none focus:ring-2 focus:ring-clinic-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-clinic-500 dark:focus:ring-clinic-500/30']) }}>
