<?php

use App\Sanitizer;
use Carbon\Carbon;

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

/**
 * Returns a properly parsed ISO8601 string in a Carbon instance.
 * 
 * @param   string   $str
 * @return  Carbon\Carbon
 */
function dateFromIso8601String($str) {
    if (!$str) {
        return null;
    }

    $dt = DateTime::createFromFormat(DateTime::RFC3339, $str);
    $carbon = Carbon::instance($dt);
    $carbon->tz(config('app.timezone'));

    return $carbon;
}