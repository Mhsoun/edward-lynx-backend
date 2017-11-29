<?php

namespace App\Listeners;

use App\Events\SurveyKeyExchanged;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\SurveyAnswerRequest;
use App\Notifications\SurveyInviteRequest;
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
     * @param  App\Events\SurveyKeyExchanged  $event
     * @return void
     */
    public function handle(SurveyKeyExchanged $event)
    {
        $whitelist = [SurveyAnswerRequest::class, SurveyInviteRequest::class];
        $notifications = $event->user->unreadNotifications->filter(function ($notification) use ($whitelist) {
            return in_array($notification->type, $whitelist);
        });

        if ($event->action === 'answer') {
            $notifications
                ->filter(function ($notification) {
                    return $notification->type === SurveyAnswerRequest::class;
                })->each(function ($notification) use ($event) {
                    if (isset($notification->data['surveyKey']) && $notification->data['surveyKey'] == $event->key) {
                        $notification->markAsRead();
                    }
                });
        } elseif ($event->action === 'invite') {
            $notifications
                ->filter(function ($notification) {
                    return $notification->type === SurveyInviteRequest::class;
                })->each(function ($notification) use ($event) {
                    if (isset($notification->data['surveyKey']) && $notification->data['surveyKey'] == $event->key) {
                        $notification->markAsRead();
                    }
                });
        }
    }
}
