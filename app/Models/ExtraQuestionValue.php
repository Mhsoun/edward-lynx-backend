<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lang;

/**
* Represents a value for an extra question
*/
class ExtraQuestionValue extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'extra_question_values';

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

        //If the lang was not found, use any language.
        if ($name == null) {
            $name = $this->names()->where('lang', '=', 'en')->first();
        }

        if ($name == null) {
            return Lang::get('surveys.noName');
        }

        return $name->name;
    }

    /**
    * Returns the full name
    */
    public function fullName($lang = null, $seperator = " → ")
    {
        if ($this->parentValue != null) {
            return $this->parentValue->fullName($lang, $seperator) . $seperator . $this->name($lang);
        } else {
            return $this->name($lang);
        }
    }

    /**
	* Returns the parent value
	*/
	public function parentValue()
	{
		return $this->belongsTo('\App\Models\ExtraQuestionValue', 'parentValueId');
	}

    /**
    * Returns the children
    */
    public function children()
    {
        return $this->hasMany('\App\Models\ExtraQuestionValue', 'parentValueId');
    }

    /**
    * Returns the names for the value
    */
    public function names()
    {
        return $this->hasMany('\App\Models\ExtraQuestionValueName', 'valueId');
    }
}
