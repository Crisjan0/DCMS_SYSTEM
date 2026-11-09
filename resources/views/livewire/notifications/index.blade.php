<div class="py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <!-- Heading -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-5">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-clinic-950">
                    {{ __('Notifications') }}
                </h2>
                <p class="text-[13px] text-slate-500">
                    {{ __('Appointment updates, billing, and health-record activity.') }}
                </p>
            </div>

            <button type="button"
                wire:click="markAllRead"
                @disabled($unreadCount === 0)
                @class([
                    'inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wider transition',
                    'bg-clinic-600 text-white hover:bg-clinic-700' => $unreadCount > 0,
                    'bg-slate-100 text-slate-400' => $unreadCount === 0,
                ])>
                {{ __('Mark all read') }}
            </button>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-wrap items-center gap-3 pb-4">
            <div class="flex gap-2">
                <button type="button" wire:click="$set('tab', 'all')"
                    @class([
                        'rounded-full px-4 py-2 text-[13px] font-medium transition-colors',
                        'bg-clinic-600 text-white focus:outline-none focus:ring-2 focus:ring-clinic-500/40' => $tab === 'all',
                        'bg-white text-slate-500 ring-1 ring-clinic-950/10 hover:text-clinic-700' => $tab !== 'all',
                    ])>
                    {{ __('All') }}
                </button>
                <button type="button" wire:click="$set('tab', 'unread')"
                    @class([
                        'rounded-full px-4 py-2 text-[13px] font-medium transition-colors',
                        'bg-clinic-600 text-white focus:outline-none focus:ring-2 focus:ring-clinic-500/40' => $tab === 'unread',
                        'bg-white text-slate-500 ring-1 ring-clinic-950/10 hover:text-clinic-700' => $tab !== 'unread',
                    ])>
                    {{ __('Unread') }}
                </button>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="$set('group', '')"
                    @class([
                        'rounded-full px-4 py-2 text-[13px] font-medium transition-colors',
                        'bg-clinic-950 text-white' => $group === '',
                        'bg-white text-slate-500 ring-1 ring-clinic-950/10 hover:text-clinic-700' => $group !== '',
                    ])>
                    {{ __('All topics') }}
                </button>

                @foreach ($groupLabels as $groupKey => $label)
                    <button type="button" wire:click="$set('group', '{{ $groupKey }}')"
                        @class([
                            'rounded-full px-4 py-2 text-[13px] font-medium transition-colors',
                            'bg-clinic-950 text-white' => $group === $groupKey,
                            'bg-white text-slate-500 ring-1 ring-clinic-950/10 hover:text-clinic-700' => $group !== $groupKey,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- List card -->
        <div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
            @forelse ($notificationsPage as $notification)
                <div @class([
                    'flex items-start gap-4 px-5 py-4',
                    'bg-clinic-500/5' => $notification->read_at === null,
                    'border-t border-slate-100' => ! $loop->first,
                ])>
                    <!-- Clickable area: opens the detail popup -->
                    <button type="button"
                        wire:click="focusNotification('{{ $notification->id }}')"
                        title="{{ __('Open notification') }}"
                        class="group flex min-w-0 flex-1 items-start gap-4 rounded-2xl text-start focus:outline-none focus:ring-2 focus:ring-clinic-500/30">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-clinic-100 text-clinic-700 transition-colors group-hover:bg-clinic-600 group-hover:text-white">
                            <x-nav-icon :icon="$notification->data['icon'] ?? 'bell'" class="h-5 w-5" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="min-w-0 flex-1 truncate text-[14px] font-semibold text-clinic-950">
                                    {{ $notification->data['title'] ?? '' }}
                                </span>
                                @if ($notification->read_at === null)
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-rose-500" aria-hidden="true"></span>
                                @endif
                            </span>

                            <span class="mt-1 line-clamp-2 block text-[13px] leading-relaxed text-slate-600">
                                {{ $notification->data['body'] ?? '' }}
                            </span>

                            <span class="mt-1.5 block text-[11px] text-slate-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </span>
                    </button>

                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button"
                            wire:click="toggleRead('{{ $notification->id }}')"
                            title="{{ $notification->read_at === null ? __('Mark as read') : __('Mark as unread') }}"
                            class="inline-flex items-center justify-center rounded-xl p-2 text-slate-400 transition-colors hover:bg-clinic-50 hover:text-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500/30">
                            @if ($notification->read_at === null)
                                <!-- Check -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <!-- Dot -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.375 3v1.5C9.375 4.83 9.205 5 8.875 5h-.75C7.795 5 7.625 4.83 7.625 4.5V3M9.375 20.25v1.5c0 .33-.17.5-.5.5h-.75c-.33 0-.5-.17-.5-.5v-1.5m7.5 0v1.5c0 .33.17.5.5.5h.75c.33 0 .5-.17.5-.5v-1.5m0-17.25v1.5c0 .33.17.5.5.5h.75c.33 0 .5-.17.5-.5V3m-14.25 9h-1.5c-.33 0-.5-.17-.5-.5v-.75c0-.33.17-.5.5-.5h1.5m17.25 0h1.5c.33 0 .5-.17.5-.5v-.75c0-.33-.17-.5-.5-.5h-1.5m-15 6.75h-1.5c-.33 0-.5.17-.5.5v.75c0 .33.17.5.5.5h1.5m17.25 0h1.5c.33 0 .5.17.5.5v.75c0 .33-.17.5-.5.5h-1.5M12 8.25l1.5 3 3 .75-3 .75-1.5 3-1.5-3-3-.75 3-.75 1.5-3z" />
                                </svg>
                            @endif
                        </button>

                        <button type="button"
                            wire:click="delete('{{ $notification->id }}')"
                            title="{{ __('Delete notification') }}"
                            class="inline-flex items-center justify-center rounded-xl p-2 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="text-[15px] font-medium text-slate-500">
                        {{ $tab === 'unread' ? __('All caught up — nothing unread here.') : __('No notifications yet.') }}
                    </p>
                </div>
            @endforelse

            @if ($notificationsPage->hasPages())
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                    <p class="text-xs text-slate-500">
                        {{ __('Showing :first–:last of :total', [
                            'first' => $notificationsPage->firstItem(),
                            'last' => $notificationsPage->lastItem(),
                            'total' => $notificationsPage->total(),
                        ]) }}
                    </p>

                    <div class="flex gap-2">
                        <button type="button"
                            wire:click="previousPage"
                            @disabled($notificationsPage->onFirstPage())
                            @class([
                                'rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition',
                                'bg-white text-clinic-700 ring-1 ring-clinic-950/10 hover:bg-clinic-50' => ! $notificationsPage->onFirstPage(),
                                'bg-white text-slate-300 ring-1 ring-slate-100' => $notificationsPage->onFirstPage(),
                            ])>
                            {{ __('Previous') }}
                        </button>

                        <button type="button"
                            wire:click="nextPage"
                            @disabled(! $notificationsPage->hasMorePages())
                            @class([
                                'rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition',
                                'bg-white text-clinic-700 ring-1 ring-clinic-950/10 hover:bg-clinic-50' => $notificationsPage->hasMorePages(),
                                'bg-white text-slate-300 ring-1 ring-slate-100' => ! $notificationsPage->hasMorePages(),
                            ])>
                            {{ __('Next') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Notification detail popup (shared partial) -->
    @include('livewire.notifications.detail', ['focused' => $focused])
</div>
