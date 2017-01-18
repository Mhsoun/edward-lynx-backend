<?php

namespace App\Events;

use App\Models\InstantFeedback;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Models\InstantFeedbackRecipient;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class InstantFeedbackCreated
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The instant feedback.
     *
     * @var App\Models\InstantFeedback
     */
    public $instantFeedback;
    
    
    /**
     * Target recipients.
     *
     * @var array
     */
    public $recipients;

    /**
     * Create a new event instance.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   array                       $recipients
     * @return  void
     */
    public function __construct(InstantFeedback $instantFeedback, array $recipients = []);
    {
        $this->instantFeedback = $instantFeedback;
        $this->recipients = $recipients;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
