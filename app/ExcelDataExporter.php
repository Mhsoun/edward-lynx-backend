<?php namespace App;
use File;

/**
* Represents an excel data exporter
*/
class ExcelDataExporter implements DataExporter
{
    private $excelData = null;
    private $lineNum = 1;

    public function __construct()
    {
        $this->excelData = new \PHPExcel();
    }

    /**
    * Escapes the given text data
    */
    public function escapeText($text)
    {
        return $text;
    }

    /**
    * Adds the given data line
    */
    public function addDataLine($line)
    {
        $this->excelData->getActiveSheet()->fromArray($line, null, 'A' . ($this->lineNum++));
    }

    /**
    * Returns the data
    */
    public function getData()
    {
        //Save to temp file
        $tempFileName = tempnam(sys_get_temp_dir(), 'Export');

        $writer = new \PHPExcel_Writer_Excel2007($this->excelData);
        $writer->save($tempFileName);

        //Return raw data
        return File::get($tempFileName);
    }
}
