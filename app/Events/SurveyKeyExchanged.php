<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SurveyKeyExchanged
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
     * The action the user wants to do when he/she exchanged the key.
     * 
     * @var string
     */
    public $action;

    /**
     * Create a new event instance.
     *
     * @param App\Models\User $user
     * @param string $key
     * @param string $action
     * @return void
     */
    public function __construct(User $user, $key, $action)
    {
        $this->user = $user;
        $this->key = $key;
        $this->action = $action;
    }

}
