<?php namespace App;
use Lang;

/**
* Defines the valid Languages
*/
abstract class Languages
{
    private static $validLangugaes = ['sv' => true, 'en' => true, 'fi' => true];

    /**
    * Returns the valid languages
    */
    public static function all()
    {
        return array_keys(Languages::$validLangugaes);
    }

    /**
    * Indicates if the given language is valid
    */
    public static function isValid($lang)
    {
        return array_key_exists($lang, Languages::$validLangugaes);
    }

    /**
    * Returns the name of the given language
    */
    public static function name($id)
    {
        switch ($id) {
            case 'en':
                return Lang::get('surveys.languageEnglish');
            case 'sv':
                return Lang::get('surveys.languageSwedish');
            case 'fi':
                return Lang::get('surveys.languageFinnish');
        }

        return '';
    }
}
