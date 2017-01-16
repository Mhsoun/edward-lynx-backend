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
     * The receiving recipient.
     *
     * @var App\Models\InstantFeedbackRecipient
     */
    public $recipient;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(InstantFeedback $instantFeedback, InstantFeedbackRecipient $recipient);
    {
        $this->instantFeedback = $instantFeedback;
        $this->recipient = $recipient;
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
