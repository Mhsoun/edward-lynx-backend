<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a question category in a survey
*/
class SurveyQuestionCategory extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'survey_question_categories';

	protected $fillable = ['title'];
	public $timestamps = false;

	/**
	* Returns the category object
	*/
	public function category()
	{
		return $this->belongsTo('\App\Models\QuestionCategory', 'categoryId');
	}

	/**
	* Returns the questions in the category
	*/
	public function questions()
	{
		return
			$this->hasMany('\App\Models\SurveyQuestion', 'surveyId', 'surveyId')
			->whereRaw('questionId IN (SELECT questions.id FROM questions WHERE questions.categoryId=?)', [$this->categoryId]);
	}
    
    /**
     * Returns the order and child questions when serializing to JSON.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $attrs = parent::jsonSerialize();
        $category = $this->category->jsonSerialize();
        $questions = $this->questions->jsonSerialize();
        
        return array_merge($category, [
            'order'     => $this->order,
            'questions' => $questions
        ]);
    }
}
