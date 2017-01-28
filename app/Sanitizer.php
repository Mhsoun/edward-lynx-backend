<?php

namespace App;

class Sanitizer
{
    
    /**
     * Cleans up user input by encoding HTML characters.
     *
     * @param   string  $str
     * @return  string
     */
    public static function sanitize($str)
    {
        $str = trim($str);
        $str = htmlspecialchars($str);
        return $str;
    }
    
}