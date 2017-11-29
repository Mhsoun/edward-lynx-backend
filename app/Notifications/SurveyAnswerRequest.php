<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class SurveyAnswerRequest extends Notification
{
    use Queueable, Concerns\IncludesBadgeCount;

    /**
     * The Survey.
     *
     * @var App\Models\Survey
     */
    public $survey;

    /**
     * Invitation/Answer key
     *
     * @var string
     */
    public $key;

    /**
     * Create a new notification instance.
     *
     * @return void
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
        $body = sprintf('Hello %s, please answer the survey %s.', $notifiable->name, $this->survey->name);

        return (new FirebaseNotification)
            ->title('Invitation to answer a survey.')
            ->body($body)
            ->data($this->withBadgeCountOf($notifiable, [
                'type'  => 'survey-answer',
                'id'    => $this->survey->id,
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
        return [
            'surveyKey' => $this->key
        ];
    }
}
