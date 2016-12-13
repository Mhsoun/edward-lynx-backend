<?php namespace App;

/**
* Represents a data exporter
*/
interface DataExporter
{
    /**
    * Escapes the given text data
    */
    public function escapeText($text);

    /**
    * Adds the given data line
    */
    public function addDataLine($line);

    /**
    * Returns the data
    */
    public function getData();
}
