<?php namespace App;
use File;

/**
* Represents a CSV data exporter
*/
class CSVDataExporter implements DataExporter
{
    private $csvData = '';
    private $excelData = null;

    /**
    * Escapes the given text data
    */
    public function escapeText($text)
    {
        return '"' . $text . '"';
    }

    /**
    * Adds the given data line
    */
    public function addDataLine($line)
    {
        \App\CSVParser::appendLine($this->csvData, $line);
    }

    /**
    * Returns the data
    */
    public function getData()
    {
        return $this->csvData;
    }
}
