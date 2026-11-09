@if ($focused)
    <div class="fixed inset-0 z-[80] flex items-center justify-center p-4 no-print">
        <!-- Backdrop: clicking it dismisses the popup -->
        <button type="button" wire:click="closeFocus" class="absolute inset-0 cursor-default bg-clinic-950/60 backdrop-blur-sm" aria-label="{{ __('Close') }}"></button>

        <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-[0_35px_80px_-30px_rgba(10,47,37,0.6)] ring-1 ring-clinic-950/5">
            <!-- Popup header -->
            <div class="flex items-start gap-4 border-b border-clinic-950/5 bg-clinic-50/70 px-6 py-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-clinic-100 text-clinic-700">
                    <x-nav-icon :icon="$focused->data['icon'] ?? 'bell'" class="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[15px] font-semibold leading-snug text-clinic-950">
                        {{ $focused->data['title'] ?? '' }}
                    </p>
                    <p class="mt-0.5 text-[12px] text-slate-400">
                        {{ $focused->created_at->diffForHumans() }} · {{ $focused->created_at->format('M d, Y g:i A') }}
                    </p>
                </div>

                <button type="button" wire:click="closeFocus" class="inline-flex items-center justify-center rounded-xl p-1.5 text-slate-400 transition-colors hover:bg-white hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-clinic-500/30" aria-label="{{ __('Close') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Full message body -->
            <div class="px-6 py-5">
                <p class="text-[14px] leading-relaxed text-slate-600">
                    {{ $focused->data['body'] ?? '' }}
                </p>
            </div>

            <!-- Popup footer -->
            <div class="flex items-center justify-between gap-3 border-t border-clinic-950/5 bg-slate-50/70 px-6 py-4">
                <span @class([
                    'inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider',
                    'text-rose-500' => $focused->read_at === null,
                    'text-slate-400' => $focused->read_at !== null,
                ])>
                    <span @class([
                        'h-2 w-2 rounded-full',
                        'bg-rose-500' => $focused->read_at === null,
                        'bg-slate-300' => $focused->read_at !== null,
                    ]) aria-hidden="true"></span>
                    {{ $focused->read_at === null ? __('Unread') : __('Read') }}
                </span>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="closeFocus" class="inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 ring-1 ring-clinic-950/10 transition-colors hover:text-clinic-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-clinic-500/30">
                        {{ __('Close') }}
                    </button>

                    @if (filled($focused->data['link'] ?? null))
                        <button type="button" wire:click="goToFocused" class="inline-flex items-center gap-1.5 rounded-full bg-clinic-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-[0_12px_25px_-12px_rgba(26,148,107,0.8)] transition-colors hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500/40">
                            {{ __('Open') }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
