<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Reperesents a report file for a survey
*/
class SurveyReportFile extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'survey_report_files';

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
