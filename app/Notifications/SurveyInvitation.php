<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Survey;
use Illuminate\Bus\Queueable;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use App\Notifications\Concerns\IncludesBadgeCount;
use Illuminate\Notifications\Messages\MailMessage;

class SurveyInvitation extends Notification implements ShouldQueue
{
    use Queueable, IncludesBadgeCount;

    /**
     * The survey ID.
     * 
     * @var App\Models\Survey
     */
    public $surveyId;

    /**
     * Survey answer key.
     * 
     * @var string
     */
    public $key;

    /**
     * Create a new notification instance.
     *
     * @param   int     $surveyId
     * @param   string  $key
     * @return  void
     */
    public function __construct($surveyId, $key)
    {
        $this->surveyId = $surveyId;
        $this->key = $key;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param   mixed  $notifiable
     * @return  array
     */
    public function via($notifiable)
    {
        return ['database', FirebaseChannel::class];
    }

    /**
     * Get the firebase representation of the notification.
     * 
     * @param   mixed   $notifiable
     * @return  App\Services\Firebase\FirebaseNotification
     */
    public function toFirebase($notifiable)
    {
        return (new FirebaseNotification)
            ->title(trans('surveys.toEvaluateTitle'))
            ->body(trans('surveys.toEvaluateBody'))
            ->data($this->withBadgeCountOf($notifiable, [
                'type'  => 'survey',
                'id'    => $this->surveyId,
                'key'   => $this->key
            ]))
            ->to($notifiable->deviceTokens());
    }

    /**
     * Returns the database representation of the notification.
     * 
     * @param   mixed   $notifiable
     * @return  array
     */
    public function toDatabase($notifiable)
    {
        if (!$notifiable instanceof User) {
            return null;
        }
        
        return [
            'surveyKey' => $this->key
        ];
    }
}
