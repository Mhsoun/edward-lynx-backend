<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;

class SurveyInvitation extends Notification
{
    use Queueable;

    /**
     * The survey.
     * 
     * @var App\Models\Survey
     */
    public $survey;

    /**
     * Answer key.
     * 
     * @var string
     */
    public $key;

    /**
     * Create a new notification instance.
     *
     * @param   App\models\Survey   $survey
     * @param   string              $key
     * @return  void
     */
    public function __construct(Survey $survey, $key)
    {
        $this->survey = $survey;
        $this->key = $key;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FirebaseChannel::class];
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
            ->title(trans('surveys.inviteMailDefaultSubject'))
            ->body(trans('Hello :recipient! :sender wants you to answer the ":surveyName" survey.', [
                'recipient'     => $notifiable->name,
                'sender'        => $survey->owner->name,
                'surveyName'    => $survey->name
            ]))
            ->data([
                'type'  => 'survey-invitation',
                'key'   => $this->key
            ])->to($notifiable->deviceTokens());
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
