<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Alfa6661\Firebase\FirebaseChannel;
use Alfa6661\Firebase\FirebaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InviteOthersToEvaluate extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The survey.
     * 
     * @var App\Models\Survey
     */
    public $survey;

    /**
     * Create a new notification instance.
     *
     * @return  void
     */
    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param   mixed  $notifiable
     * @return  array
     */
    public function via($notifiable)
    {
        return [FirebaseChannel::class];
    }

    /**
     * Get the firebase representation of the notification.
     * 
     * @param   mixed   $notifiable
     * @return  Alfa6661\Firebase\FirebaseMessage
     */
    public function toFirebase($notifiable)
    {
        return FirebaseMessage::create()
            ->title(trans('surveys.toEvaluateTitle'))
            ->body(trans('surveys.toEvaluateBody'))
            ->data(['surveyId' => $this->survey->id]);
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
