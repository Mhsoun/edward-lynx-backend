<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents an extra question for a survey
*/
class SurveyExtraQuestion extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'survey_extra_questions';

    protected $fillable = [];
    public $timestamps = false;

    /*
	* Returns the extra question
	*/
	public function extraQuestion()
	{
		return $this->belongsTo('\App\Models\ExtraQuestion', 'extraQuestionId');
	}
}
