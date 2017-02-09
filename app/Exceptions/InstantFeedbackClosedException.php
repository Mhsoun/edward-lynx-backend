<?php

namespace App\Exceptions;

class InstantFeedbackClosedException extends ApiException
{
    
    /**
     * Constructor
     * 
     * @param  string   $message
     * @param  integer  $statusCode
     */
    public function __construct($message = 'Instant feedback already closed.', $statusCode = 400)
    {
        parent::__construct($message, $statusCode);
    }

}