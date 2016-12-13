<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a role group in a group survey
*/
class SurveyRoleGroup extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'survey_role_groups';

	protected $fillable = [];
	public $timestamps = false;

	/**
	* Returns the survey object
	*/
	public function survey()
	{
		return $this->belongsTo('\App\Models\Survey', 'surveyId');
	}
}
