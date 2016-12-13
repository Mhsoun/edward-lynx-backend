<?php namespace App;

/**
* Represents a CSV parser
*/
abstract class CSVParser
{
    const SEPERATOR = ";";

	/**
    * Parses the given data
    */
    public static function parse($data, $numCols = 0)
    {
        //Split into lines
        $lines = preg_split("/(\n)|(\r\n)/", $data);

        $lineSpliter = function($line) {
            return preg_split("/" . CSVParser::SEPERATOR . "/", $line);
        };

        if (count($lines) > 0) {
            $output = [];

            if ($numCols == 0) {
                $numCols = count($lineSpliter($lines[0]));
            }

            foreach ($lines as $line) {
                $cols = $lineSpliter($line);

                //Check that the number of columns match
                if (count($cols) == $numCols) {
                    array_push($output, $cols);
                }
            }

            return $output;
        } else {
            return [];
        }
    }

    /**
    * Converts the given array to CSV
    */
    public static function toCSV($lines)
    {
        $output = "";

        foreach ($lines as $line) {
            $output .= implode(CSVParser::SEPERATOR, $line) . "\n";
        }

        return $output;
    }

    /**
    * Appends the given line to the given CSV data
    */
    public static function appendLine(&$csvData, $line)
    {
        $csvData .= implode(CSVParser::SEPERATOR, $line) . "\n";
    }
}
