<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lang;

/**
* Represents an extra question
*/
class ExtraQuestion extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'extra_questions';

    protected $fillable = [];
    public $timestamps = false;

    /**
    * Returns the name determined by the current locale
    */
    public function name($lang = null)
    {
        if ($lang == null) {
            $lang = app()->getLocale();
        }

        $name = $this->names()->where('lang', '=', $lang)->first();

        //If the lang was not found, use english.
        if ($name == null) {
            $name = $this->names()->where('lang', '=', 'en')->first();
        }

        if ($name == null) {
            return Lang::get('surveys.noName');
        }

        return $name->name;
    }

    /**
    * Returns the languages that the extra question is in
    */
    public function languages()
    {
        return $this->names()->lists("lang");
    }

    /**
    * Returns the names for the question
    */
    public function names()
    {
        return $this->hasMany('\App\Models\ExtraQuestionName', 'extraQuestionId');
    }

    /**
    * Returns the values for the question
    */
    public function values()
    {
        return $this->hasMany('\App\Models\ExtraQuestionValue', 'extraQuestionId');
    }

    /**
    * Returns the name of the numeric value.
    * If there is no name, returns the input.
    */
    public function getValueName($numericValue)
    {
        $value = $this->values()
            ->where('id', '=', $numericValue)
            ->first();

        if ($value != null) {
            return $value->fullName();
        }

        return $numericValue;
    }

    /**
    * Returns the extra questions for the given user
    */
    public static function forUser($userId)
    {
        $allExtraQuestions = [];

        foreach (\App\Models\ExtraQuestion::whereNull('ownerId')->get() as $extraQuestion) {
            array_push($allExtraQuestions, $extraQuestion);
        }

        //Add user
        foreach (\App\Models\ExtraQuestion::where('ownerId', '=', $userId)->get() as $extraQuestion) {
            array_push($allExtraQuestions, $extraQuestion);
        }

        return $allExtraQuestions;
    }
}
