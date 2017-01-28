<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class SurveyMissingAnswersException extends SurveyException
{
    
    /**
     * Error messages.
     *
     * @var Illuminate\Support\MessageBag
     */
    protected $errors;
    
    /**
     * Create a new exception instance.
     *
     * @param   array   $errors
     */
    public function __construct(array $errors)
    {
        $this->errors($errors);
    }
    
    
    /**
     * Gets or sets the error messages.
     *
     * @param   array   $errors
     * @return  array
     */
    public function errors(array $errors)
    {   
        if ($errors) {
            $this->errors = $errors;
        }
        return $this->errors;
    }
    
    /**
     * Returns an array that is used as a response.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $validationErrors = [];
        foreach ($this->errors as $error) {
            $key = sprintf('answers.question.%d', $error['question']);
            $validationErrors[$key][] = 'Missing an answer for this question.';
        }
        
        return [
            'error'             => 'validation_error',
            'validation_errors' => $validationErrors
        ];
    }
    
}