<?php namespace App;
use Lang;

/**
* Contains text helpers
*/
abstract class TextHelpers
{
    /**
    * Splits the given string into segments of the given length
    */
    static function splitLength($text, $segmentLength)
    {
        $segments = [];

        $length = strlen($text);
        $currentLength = 0;
        $segment = '';

        for ($i = 0; $i < $length; $i++) {
            $segment .= $text[$i];
            $currentLength++;

            if ($currentLength >= $segmentLength) {
                array_push($segments, $segment);
                $segment = '';
                $currentLength = 0;
            }
        }

        array_push($segments, $segment);
        return $segments;
    }

    /**
    * Splits the given string by words into segments of the given length
    */
    static function splitByWordLength($text, $segmentLength)
    {
        //Get the words
        $words = explode(' ', $text);

        $segments = [];
        $currentLength = 0;
        $segment = '';

        foreach ($words as $word) {
            $newLength = strlen($segment) + strlen($word);
            if ($newLength > $segmentLength) {
                array_push($segments, $segment);
                $currentLength = 0;
                $segment = '';
            }

            $segment .= (strlen($segment) > 0 ? ' ' : '') . $word;
            $currentLength += strlen($word);
        }

        array_push($segments, $segment);
        return $segments;
    }
}
