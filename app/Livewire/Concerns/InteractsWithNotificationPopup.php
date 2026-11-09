<?php

namespace App\Livewire\Concerns;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Shared notification detail-popup (modal) behaviour for the Livewire
 * notification components — used by both the sidebar bell dropdown and
 * the /notifications center page:
 *
 * - focusNotification() opens the popup and marks the notification read
 * - closeFocus() dismisses the popup
 * - goToFocused() closes the popup and jumps to the notification's link
 */
trait InteractsWithNotificationPopup
{
    /** @var ?string id of the notification currently shown in the popup */
    public ?string $focusId = null;

    /**
     * Open the detail popup for one notification (marks it read).
     */
    public function focusNotification(string $id): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = auth()->user()->notifications()->whereKey($id)->first();

        if ($notification === null) {
            $this->focusId = null;

            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $this->focusId = $id;
    }

    /**
     * Close the detail popup.
     */
    public function closeFocus(): void
    {
        $this->focusId = null;
    }

    /**
     * Close the popup and navigate to the notification's link target.
     */
    public function goToFocused(): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = auth()->user()->notifications()->whereKey((string) $this->focusId)->first();

        $link = $notification?->data['link'] ?? null;

        $this->focusId = null;

        if (filled($link)) {
            $this->redirect($link);
        }
    }

    /**
     * The notification currently shown in the popup (null when closed).
     */
    public function focusedNotification(): ?DatabaseNotification
    {
        if (blank($this->focusId)) {
            return null;
        }

        /** @var DatabaseNotification|null $notification */
        $notification = auth()->user()->notifications()->whereKey($this->focusId)->first();

        if ($notification === null) {
            $this->focusId = null;
        }

        return $notification;
    }
}
