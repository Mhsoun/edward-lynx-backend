<?php

use App\Sanitizer;

/**
 * Sanitizes a string.
 *
 * @param   string  $str
 * @return  string
 */
function sanitize($str) {
    return Sanitizer::sanitize($str);
}