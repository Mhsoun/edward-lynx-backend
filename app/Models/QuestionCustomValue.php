<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a custom value for a question
*/
class QuestionCustomValue extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'question_custom_values';

    protected $fillable = [];
    public $timestamps = false;

    /**
	* Returns the question that the custom value belongs to
	*/
	public function question()
	{
		return $this->belongsTo('\App\Models\Question', 'questionId');
	}
}
