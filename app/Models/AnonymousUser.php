<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;

class AnonymousUser
{
    use Notifiable;

    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}