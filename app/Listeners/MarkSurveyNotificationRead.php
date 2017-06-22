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
        $notifications = $event->user->unreadNotifications;
        foreach ($notifications as $notification) {
            if ($notification->data['surveyKey'] == $event->key) {
                $notification->markAsRead();
            }
        }
    }
}
