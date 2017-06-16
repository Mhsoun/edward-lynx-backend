<?php

namespace App\Listeners;

use App\Events\SurveyKeyExchanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkSurveyNotificationRead
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
     * @param  SurveyKeyExchanged  $event
     * @return void
     */
    public function handle(SurveyKeyExchanged $event)
    {
        $notification = $event->user->unreadNotifications()->first(function ($notification) use ($event) {
            return $notification->key == $event->key;
        });

        if ($notification) {
            $notification->markAsRead();
        }
    }
}
