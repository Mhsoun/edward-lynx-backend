<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\ExtraAnswerValue;

/**
* Represents an answer for a extra question in a survey
*/
class SurveyExtraAnswer extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'survey_extra_answers';

    protected $fillable = [];
    public $timestamps = false;

    /**
    * Returns the recipient that answered the question
    */
    public function answeredBy()
    {
    	return SurveyRecipient::
    		where('surveyId', '=', $this->surveyId)
    		->where('recipientId', '=', $this->answeredById)
    		->first();
    }

    /**
    * Returns the extra question
    */
    public function extraQuestion()
    {
        return $this->belongsTo('\App\Models\ExtraQuestion', 'extraQuestionId');
    }

    /**
    * Returns the value
    */
    public function value()
    {
        switch ($this->extraQuestion->type) {
            case ExtraAnswerValue::Text:
                return $this->textValue;
            case ExtraAnswerValue::Date:
                return $this->dateValue;
            case ExtraAnswerValue::Options:
                return $this->numericValue;
            case ExtraAnswerValue::Hierarchy:
                return $this->numericValue;
        }
    }
}
