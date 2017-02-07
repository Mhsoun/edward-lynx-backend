<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Alfa6661\Firebase\FirebaseChannel;
use Alfa6661\Firebase\FirebaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     * @return  Alfa6661\Firebase\FirebaseMessage
     */
    public function toFirebase($notifiable)
    {
        return FirebaseMessage::create()
            ->title($this->title)
            ->body($this->body)
            ->data([
                'id' => 123,
                'type' => 'test'
            ]);
    }
}
