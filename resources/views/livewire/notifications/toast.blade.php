<div class="pointer-events-none fixed inset-x-3 bottom-4 z-[70] flex flex-col items-stretch gap-2 no-print sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-96 sm:items-end">
    {{-- Poll only while toasts exist — prunes expired entries and stops itself when the stack empties. --}}
    @if (count($toasts) > 0)
        <div wire:poll.1s></div>
    @endif

    @foreach ($toasts as $toast)
        <div @class([
            'pointer-events-auto flex w-full items-start gap-3 rounded-2xl border px-4 py-3 shadow-[0_18px_40px_-20px_rgba(10,47,37,0.5)]',
            'border-emerald-200 bg-white' => $toast['type'] !== 'error',
            'border-rose-200 bg-white' => $toast['type'] === 'error',
        ])>
            <span @class([
                'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[12px] font-bold text-white',
                'bg-emerald-500' => $toast['type'] === 'success',
                'bg-rose-500' => $toast['type'] === 'error',
                'bg-clinic-600' => $toast['type'] === 'info',
            ])>
                @if ($toast['type'] === 'success')
                    ✓
                @elseif ($toast['type'] === 'error')
                    !
                @else
                    i
                @endif
            </span>

            <p class="min-w-0 flex-1 text-[13px] leading-snug text-slate-700">
                {{ $toast['message'] }}
            </p>

            <button type="button"
                wire:click="dismiss('{{ $toast['id'] }}')"
                class="text-slate-300 transition-colors hover:text-slate-500"
                aria-label="{{ __('Dismiss') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endforeach
</div>
