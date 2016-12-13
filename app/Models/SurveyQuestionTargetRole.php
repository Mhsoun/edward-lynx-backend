<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a target role for a question in a survey
*/
class SurveyQuestionTargetRole extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'survey_question_target_roles';

	protected $fillable = [];
	public $timestamps = false;
}
