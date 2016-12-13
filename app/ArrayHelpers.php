<?php namespace App;

/**
* Contain array helpers
*/
abstract class ArrayHelpers
{
    /**
    * Splits the given array.
    * The elements that satisfies the predicate will be in the first part.
    */
    public static function split($array, $splitFn)
    {
        $part1 = [];
        $part2 = [];

        foreach ($array as $element) {
            if ($splitFn($element)) {
                array_push($part1, $element);
            } else {
                array_push($part2, $element);
            }
        }

        return [$part1, $part2];
    }

    /**
    * Shuffles the given array
    */
    public static function shuffle($array)
    {
        $copy = $array;
        shuffle($copy);
        return $copy;
    }
}
