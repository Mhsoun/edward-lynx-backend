<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;

class CustomValidationException extends Exception
{

    /**
     * Custom response.
     *
     * @var Illuminate\Http\Response
     */
    public $response;

    /**
     * Validation error messages.
     *
     * @var Illuminate\Support\MessageBag
     */
    protected $errors;

    /**
     * Create a new exception instance.
     *
     * @param   array                       $errors
     * @param   Illuminate\Http\Response    $response
     */
    public function __construct(array $errors, Response $response = null)
    {
        parent::__construct('The given data failed to pass validation.');
        
        $this->errors($errors);
        $this->response = $response;
    }
    
    /**
     * Gets or sets the error messages.
     *
     * @param   array   $errors
     * @return  Illuminate\Support\MessageBag
     */
    public function errors(array $errors = [])
    {
        if ($errors) {
            $this->errors = new MessageBag($errors);
        }
        return $this->errors;
    }

}