<?php

namespace App\Exceptions;

/**
 * Catch all exception for all requests that are not valid operations
 * on an API endpoint. For example: accessing expired surveys.
 */
class InvalidOperationException extends ApiException
{

    public function __construct($message = 'Invalid Operation.')
    {
        parent::__construct($message, 400);
    }

}
