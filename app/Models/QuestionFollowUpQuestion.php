<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a follow up question for a quesiton
*/
class QuestionFollowUpQuestion extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'question_follow_up_questions';

    protected $fillable = [];
    public $timestamps = false;

    /**
	* Returns the question
	*/
	public function question()
	{
		return $this->belongsTo('\App\Models\Question', 'questionId');
	}

    /**
	* Returns the follow up question
	*/
	public function followUpQuestion()
	{
		return $this->belongsTo('\App\Models\Question', 'followUpQuestionId');
	}
}
