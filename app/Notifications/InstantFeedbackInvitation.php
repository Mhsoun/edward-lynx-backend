<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\InstantFeedback;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class InstantFeedbackInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Instant Feedback ID.
     * 
     * @var int
     */
    public $instantFeedbackId;

    /**
     * Instant Feedback owner name.
     * 
     * @var string
     */
    public $sender;

    /**
     * Create a new notification instance.
     *
     * @param   int     $instantFeedbackId
     * @param   string  $sender
     * @return  void
     */
    public function __construct($instantFeedbackId, $sender)
    {
        $this->instantFeedbackId = $instantFeedbackId;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if (is_callable($notifiable->deviceTokens)) {
            return ['mail', FirebaseChannel::class];
        } else {
            return ['mail'];
        }
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param   mixed   $notifiable
     * @return  Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = "edwardlynx://instant-feedback/{$this->instantFeedbackId}";
        return (new MailMessage)
                    ->subject(trans('instantFeedback.requestedTitle'))
                    ->line(trans('instantFeedback.requested', [
                        'recipient' => $notifiable->name,
                        'sender'    => $this->sender
                    ]))
                    ->action(trans('instantFeedback.requestedAction'), $url);
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
            ->title(trans('instantFeedback.requestedTitle'))
            ->body(trans('instantFeedback.requested', [
                'recipient' => $notifiable->name,
                'sender'    => $this->sender
            ]))
            ->data([
                'type'  => 'instant-request',
                'id'    => $this->instantFeedbackId
            ])->to($notifiable->deviceTokens());
    }
}
