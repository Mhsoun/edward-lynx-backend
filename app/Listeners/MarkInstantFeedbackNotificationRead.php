<?php

namespace App\Listeners;

use App\Events\InstantFeedbackKeyExchanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkInstantFeedbackNotificationRead
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  InstantFeedbackKeyExchanged  $event
     * @return void
     */
    public function handle(InstantFeedbackKeyExchanged $event)
    {
        $notifications = $event->user->unreadNotifications;
        foreach ($notifications as $notification) {
            if (isset($notification->data['key']) && $notification->data['key'] == $event->key) {
                $notification->markAsRead();
            }
        }
    }
}
