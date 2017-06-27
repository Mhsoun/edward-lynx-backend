<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class InstantFeedbackKeyExchanged
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The user who exchanged the key.
     * 
     * @var App\Models\User
     */
    public $user;

    /**
     * Answer key.
     * 
     * @var string
     */
    public $key;

    /**
     * Create a new event instance.
     *
     * @param  App\Models\User  $user
     * @param  string           $key
     * @return void
     */
    public function __construct(User $user, $key)
    {
        $this->user = $user;
        $this->key = $key;
    }
    
}
