<?php

namespace App\Exceptions;

use Exception;
use JsonSerializable;

class SurveyException extends Exception implements JsonSerializable
{
    
    public function jsonSerialize()
    {
        return $this->message;
    }
    
}