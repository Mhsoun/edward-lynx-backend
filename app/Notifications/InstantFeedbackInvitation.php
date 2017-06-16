<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Models\InstantFeedback;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use App\Notifications\Concerns\IncludesBadgeCount;
use Illuminate\Notifications\Messages\MailMessage;

class InstantFeedbackInvitation extends Notification implements ShouldQueue
{
    use Queueable, IncludesBadgeCount;

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
     * Instant Feedback answer key.
     * 
     * @var string
     */
    public $key;

    /**
     * Create a new notification instance.
     *
     * @param   int     $instantFeedbackId
     * @param   string  $sender
     * @param   string  $key
     * @return  void
     */
    public function __construct($instantFeedbackId, $sender, $key)
    {
        $this->instantFeedbackId = $instantFeedbackId;
        $this->sender = $sender;
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
        if (method_exists($notifiable, 'deviceTokens')) {
            return ['database', 'mail', FirebaseChannel::class];
        } else {
            return ['database', 'mail'];
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
        $url = route('answer-instant-feedback', $this->key);
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
            ->data($this->withBadgeCountOf($notifiable, [
                'type'  => 'instant-request',
                'id'    => $this->instantFeedbackId
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
            'key' => $this->key
        ];
    }
}
