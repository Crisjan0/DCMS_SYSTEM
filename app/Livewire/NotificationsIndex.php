<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithNotificationPopup;
use App\Notifications\NotificationType;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full notifications page (/notifications): All/Unread tabs, topic
 * filters, pagination, per-item mark-read and delete, mark-all, and
 * a shared detail popup when a notification is clicked.
 */
class NotificationsIndex extends Component
{
    use InteractsWithNotificationPopup;
    use WithPagination;

    /** @var string 'all'|'unread' */
    public string $tab = 'all';

    /** @var string ''|appointments|billing|records|system */
    public string $group = '';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedGroup(): void
    {
        $this->resetPage();
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();

        $this->dispatch('notify', type: 'success', message: 'All notifications marked as read.');
    }

    /**
     * Flip the read state of one notification.
     */
    public function toggleRead(string $id): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = auth()->user()->notifications()->whereKey($id)->first();

        if ($notification === null) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();

            $this->dispatch('notify', type: 'info', message: 'Notification marked as read.');
        } else {
            $notification->markAsUnread();

            $this->dispatch('notify', type: 'info', message: 'Notification marked as unread.');
        }
    }

    /**
     * Remove one notification permanently.
     */
    public function delete(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->first()?->delete();

        $this->dispatch('notify', type: 'info', message: 'Notification deleted.');
    }

    public function render()
    {
        $prefixes = NotificationType::groupPrefixes($this->group);

        $user = auth()->user();

        return view('livewire.notifications.index', [
            'notificationsPage' => $user
                ->notifications()
                ->when($this->tab === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->when($prefixes !== [], fn ($query) => $query->where(function ($query) use ($prefixes): void {
                    foreach ($prefixes as $prefix) {
                        $query->orWhere('data->type', 'like', $prefix.'%');
                    }
                }))
                ->paginate(12),
            'unreadCount' => $user->unreadNotifications()->count(),
            'groupLabels' => NotificationType::GROUP_LABELS,
            'focused' => $this->focusedNotification(),
        ]);
    }
}
