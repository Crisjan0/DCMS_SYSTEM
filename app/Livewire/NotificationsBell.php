<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

/**
 * Sidebar notification bell: unread badge + centered popup list.
 *
 * Clicking the bell opens a full-screen modal (popup style, not a
 * dropdown) listing the 8 most recent notifications. Item clicks
 * mark the notification read and jump to its link.
 *
 * Refreshes itself every 15 seconds via wire:poll (Livewire-only
 * realtime — no websockets).
 */
class NotificationsBell extends Component
{
    public bool $open = false;

    /**
     * Mark every notification read.
     */
    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();

        $this->dispatch('notify', type: 'success', message: 'All notifications marked as read.');
    }

    /**
     * Mark one notification read, close the popup, jump to its link.
     */
    public function openNotification(string $id): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = auth()->user()->notifications()->whereKey($id)->first();

        $link = null;

        if ($notification !== null) {
            if ($notification->read_at === null) {
                $notification->markAsRead();
            }

            $link = $notification->data['link'] ?? null;
        }

        $this->open = false;

        if (filled($link)) {
            $this->redirect($link);
        }
    }

    public function render()
    {
        if (! auth()->check()) {
            return view('livewire.notifications.bell', [
                'unreadCount' => 0,
                'notifications' => collect(),
            ]);
        }

        $user = auth()->user();

        return view('livewire.notifications.bell', [
            'unreadCount' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->limit(8)->get(),
        ]);
    }
}
