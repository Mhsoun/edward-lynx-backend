<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{

    /**
     * HTTP status code for this exception.
     * 
     * @var int
     */
    protected $statusCode;

    /**
     * Constructor.
     * 
     * @param string  $message
     * @param integer $statusCode
     */
    public function __construct($message, $statusCode = 400)
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    /**
     * Returns the HTTP status code for this exception.
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

}