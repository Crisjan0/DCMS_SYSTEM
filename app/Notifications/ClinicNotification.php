<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic clinic notification delivered to the user's database
 * notifications table. The payload is consumed by the Livewire
 * notification bell, the /notifications page, and toasts.
 */
class ClinicNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $type  one of the NotificationType constants
     * @param  array<string, mixed>  $extra  extra payload keys (e.g. dedupe_key)
     */
    public function __construct(
        public string $type,
        public string $title,
        public string $body,
        public ?string $link = null,
        public array $extra = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /**
     * Stored in `notifications.data` — accessed by the Livewire UI
     * through the DatabaseNotification model's array cast.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'link' => $this->link,
            'icon' => NotificationType::iconFor($this->type),
        ] + $this->extra;
    }
}
