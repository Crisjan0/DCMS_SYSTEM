@php
    $countText = $unreadCount > 99 ? '99+' : (string) $unreadCount;
@endphp

<div class="mb-3" wire:poll.15s>
    <button type="button"
        wire:click="$set('open', true)"
        class="inline-flex w-full items-center gap-3 rounded-full px-4 py-2.5 text-[15px] font-medium text-emerald-50/70 transition-colors duration-150 hover:bg-white/10 hover:text-white focus:outline-none">
        <x-nav-icon icon="bell" class="text-emerald-300/80" />
        <span class="flex-1 text-start">{{ __('Notifications') }}</span>

        @if ($unreadCount > 0)
            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white shadow-sm">
                {{ $countText }}
            </span>
        @endif
    </button>

    @if ($open)
        <!-- Centered popup (full-screen overlay, popup style — not a dropdown) -->
        <div class="fixed inset-0 z-[80] flex items-center justify-center p-4 no-print">
            <!-- Backdrop: clicking it closes the popup -->
            <button type="button" wire:click="$set('open', false)" class="absolute inset-0 cursor-default bg-clinic-950/60 backdrop-blur-sm" aria-label="{{ __('Close notifications') }}"></button>

            <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-[0_35px_80px_-30px_rgba(10,47,37,0.6)] ring-1 ring-clinic-950/5">
                <!-- Popup header -->
                <div class="flex items-center justify-between gap-3 border-b border-clinic-950/5 bg-clinic-50/80 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-clinic-100 text-clinic-700">
                            <x-nav-icon icon="bell" class="h-5 w-5" />
                        </span>
                        <span>
                            <span class="block text-[15px] font-semibold text-clinic-950">{{ __('Notifications') }}</span>
                            <span class="block text-[12px] text-slate-400">
                                {{ $unreadCount }} {{ __('unread') }}
                            </span>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                            wire:click="markAllRead"
                            @disabled($unreadCount === 0)
                            @class([
                                'text-[12px] font-semibold transition-colors hover:text-clinic-800',
                                'text-clinic-600' => $unreadCount > 0,
                                'opacity-40' => $unreadCount === 0,
                            ])>
                            {{ __('Mark all read') }}
                        </button>

                        <button type="button" wire:click="$set('open', false)" class="inline-flex items-center justify-center rounded-xl p-1.5 text-slate-400 transition-colors hover:bg-white hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-clinic-500/30" aria-label="{{ __('Close notifications') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Popup list -->
                <div class="flex-1 overflow-y-auto">
                    @forelse ($notifications as $notification)
                        <button type="button"
                            wire:click="openNotification('{{ $notification->id }}')"
                            @class([
                                'flex w-full gap-3 border-b border-clinic-950/5 px-6 py-4 text-start transition-colors hover:bg-clinic-50 focus:outline-none',
                                'bg-clinic-500/5' => $notification->read_at === null,
                            ])>
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-clinic-100 text-clinic-700">
                                <x-nav-icon :icon="$notification->data['icon'] ?? 'bell'" class="h-4 w-4" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="min-w-0 flex-1 truncate text-[13px] font-semibold text-clinic-950">
                                        {{ $notification->data['title'] ?? '' }}
                                    </span>
                                    @if ($notification->read_at === null)
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-rose-500" aria-hidden="true"></span>
                                    @endif
                                </span>

                                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-snug text-slate-500">
                                    {{ $notification->data['body'] ?? '' }}
                                </span>

                                <span class="mt-1 block text-[11px] text-slate-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </span>
                        </button>
                    @empty
                        <p class="px-6 py-16 text-center text-[13px] text-slate-400">{{ __('No notifications yet.') }}</p>
                    @endforelse
                </div>

                <!-- Popup footer -->
                <a href="{{ route('notifications.index') }}" class="block bg-clinic-950 px-6 py-3.5 text-center text-[13px] font-semibold text-emerald-100 transition-colors hover:text-white">
                    {{ __('View all notifications') }}
                </a>
            </div>
        </div>
    @endif
</div>
