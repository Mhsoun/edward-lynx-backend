<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\InstantFeedback;
use Alfa6661\Firebase\FirebaseChannel;
use Alfa6661\Firebase\FirebaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InstantFeedbackRequested extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The Instant Feedback instance.
     *
     * @var App\Models\InstantFeedback
     */
    public $instantFeedback;

    /**
     * Create a new notification instance.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  void
     */
    public function __construct(InstantFeedback $instantFeedback)
    {
        $this->instantFeedback = $instantFeedback;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', FirebaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param   mixed   $notifiable
     * @return  Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = "edwardlynx:instant-feedback-{$this->instantFeedback->id}";
        return (new MailMessage)
                    ->subject(trans('instantFeedback.requestedTitle'))
                    ->line($this->message($notifiable))
                    ->action(trans('instantFeedback.requestedAction'), $url);
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
            ->title(trans('instantFeedback.requestedTitle'))
            ->body($this->message($notifiable))
            ->data(['instantFeedbackId' => $this->instantFeedback->id]);
    }
    
    /**
     * Generates the notification message.
     *
     * @param   mixed   $notifiable
     * @return  string
     */
    protected function message($recipient)
    {
        return trans('instantFeedback.requested', [
            'recipient' => $recipient->name,
            'sender'    => $this->instantFeedback->user->name
        ]);
    }
}
