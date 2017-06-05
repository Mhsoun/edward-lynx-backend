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

/**
 * Returns a HTTP 201 Created response.
 * 
 * @param  array  $headers
 * @return Illuminate\Http\Response
 */
function createdResponse(array $headers = []) {
    $headers = array_merge($headers, [
        'Content-type' => 'application/json'
    ]);
    return response('', 201, $headers);
}