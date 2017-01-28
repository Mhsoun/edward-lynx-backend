<?php

namespace App\Events;

use App\Models\InstantFeedback;
use Illuminate\Support\Collection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class InstantFeedbackResultsShared
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The instant feedback.
     *
     * @var App\Models\InstantFeedback
     */
    public $instantFeedback;
    
    /**
     * Collection of users the instant feedback is shared to.
     *
     * @var Illuminate\Support\Collection
     */
    public $users;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(InstantFeedback $instantFeedback, Collection $users)
    {
        $this->instantFeedback = $instantFeedback;
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
