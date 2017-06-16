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
        $notification = $event->user->unreadNotifications()->first(function ($notification) use ($event) {
            return $notification->key == $event->key;
        });

        if ($notification) {
            $notification->markAsRead();
        }
    }
}
