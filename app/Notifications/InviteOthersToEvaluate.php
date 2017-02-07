<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
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
     * @return  App\Services\Firebase\FirebaseNotification
     */
    public function toFirebase($notifiable)
    {
        return (new FirebaseNotification)
            ->title(trans('surveys.toEvaluateTitle'))
            ->body(trans('surveys.toEvaluateBody'))
            ->data([
                'type'  => 'survey',
                'id'    => $this->survey->id
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
