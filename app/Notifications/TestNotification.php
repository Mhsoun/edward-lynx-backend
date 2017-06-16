<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;

    public $body;

    /**
     * Create a new notification instance.
     *
     * @param   string  $title
     * @param   string  $body
     * @return  void
     */
    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
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
            ->title($this->title)
            ->body($this->body)
            ->data([
                'id'    => 123,
                'type'  => 'test'
            ])->to($notifiable->deviceTokens());
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
            'title' => $this->title,
            'body'  => $this->body
        ];
    }
}
