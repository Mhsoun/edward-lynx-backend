<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents an answer for a question in a survey
*/
class SurveyAnswer extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'survey_answers';

    protected $fillable = [];
    public $timestamps = false;

    /**
    * Returns the question for the answer
    */
    public function question()
    {
    	return $this->belongsTo('\App\Models\Question', 'questionId');
    }

    /**
    * Returns the survey question for the answer
    */
    public function surveyQuestion()
    {
        return SurveyQuestion::
    		where('surveyId', '=', $this->surveyId)
    		->where('questionId', '=', $this->questionId)
    		->first();
    }

    /**
    * Returns the survey category for the answer
    */
    public function surveyCategory()
    {
        return SurveyQuestionCategory::
    		where('surveyId', '=', $this->surveyId)
    		->where('categoryId', '=', $this->question->categoryId)
    		->first();
    }

    /**
    * Returns the recipient that answered the question
    */
    public function answeredBy()
    {
    	return SurveyRecipient::
    		where('surveyId', '=', $this->surveyId)
    		->where('recipientId', '=', $this->answeredById)
    		->where('invitedById', '=', $this->invitedById)
    		->first();
    }
    
    /**
     * Returns the JSON representation of this model.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $answerType = $this->question->answerTypeObject();
        return [
            'question'  => $this->questionId,
            'answer'    => $this->answerValue ? $this->answerValue : $this->answerText
        ];
    }
}
