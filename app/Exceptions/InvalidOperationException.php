<?php

namespace App\Exceptions;

class InvalidOperationException extends ApiException
{

    public function __construct($message = 'Invalid Operation.')
    {
        parent::__construct($message, 403);
    }

}