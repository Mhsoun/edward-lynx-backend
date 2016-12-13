<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a question in a survey
*/
class SurveyQuestion extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'survey_questions';

	protected $fillable = ['text'];
	public $timestamps = false;

	/**
	* Returns the survey that the question belongs to
	*/
	public function survey()
	{
		return $this->belongsTo('\App\Models\Survey', 'surveyId');
	}

	/**
	* Returns the question
	*/
	public function question()
	{
		return $this->belongsTo('\App\Models\Question', 'questionId');
	}

	/**
	* Returns the target roles for the question
	*/
	public function targetRoles()
	{
		return $this->hasMany('\App\Models\SurveyQuestionTargetRole', 'questionId', 'questionId')
			->where('surveyId', '=', $this->surveyId);
	}

	/**
	* Adds the given target roles to the question
	*/
	public function addTargetRoles($targetRoles)
	{
		foreach ($targetRoles as $roleId) {
			$targetRole = new \App\Models\SurveyQuestionTargetRole;
			$targetRole->surveyId = $this->surveyId;
			$targetRole->questionId = $this->questionId;
			$targetRole->roleId = $roleId;
			$this->targetRoles()->save($targetRole);
		}
	}

	/**
	* Creates a new question in the given survey
	*/
	public static function make($survey, $categoryId, $text, $answerTypeId, $optional, $isNA, $customValues = [])
	{
		//Check that the category exists
		$category = $survey->categories()
			->where('categoryId', '=', $categoryId)
			->first();

		if ($category == null) {
			return null;
		}

        $maxOrder = $survey->questions()->max('order');
		$question = \App\Models\Question::make(
			$survey->ownerId,
			$category->category,
			$text,
			$answerTypeId,
			$optional,
			$isNA,
			true,
			$customValues);

        $surveyQuestion = new \App\Models\SurveyQuestion;
        $surveyQuestion->questionId = $question->copy()->id;
        $surveyQuestion->order = $maxOrder + 1;
        $survey->questions()->save($surveyQuestion);

        return $surveyQuestion;
	}

	/**
	* Indicates if the given recipient is the target of the current question
	*/
	public function isTargetOf($surveyRecipient)
	{
		if (\App\SurveyTypes::isGroupLike($this->survey->type)) {
			return $this->targetRoles()
				->where('roleId', '=', $surveyRecipient->roleId)
				->count() > 0;
		} else {
			return true;
		}
	}
}
