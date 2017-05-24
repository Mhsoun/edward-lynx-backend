<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
* Represents a question
*/
class Question extends Model
{   
	protected $fillable = ['text'];
	public $timestamps = false;
	private $cachedCustomScaleObject = null;
    protected $visible = ['id', 'text', 'optional', 'isNA', 'isFollowUpQuestion', 'answer'];
    protected $appends = ['answer'];
    
    /**
     * Returns answer frequencies and statistics.
     *
     * @param   Illuminate\Database\Eloquent\Collection $questions
     * @param   Illuminate\Database\Eloquent\Collection $answers
     * @return  array
     */
    public static function calculateAnswers(Collection $questions, Collection $answers)
    {
        $texts = [5, 8]; // Question types with text answers
        $results = [
            'frequencies'   => [],
            'totalAnswers'  => 0
        ];
        
        foreach ($questions as $question) {
            $questionAnswers = $answers->get($question->id);
            $answerObj = $question->answerTypeObject();
            $isText = in_array($question->answerType, $texts);
            
            // Append frequencies to results
            $results['frequencies'][$question->id] = [];
            
            // Process custom input questions
            if ($question->answerType == 5) {

                // Continue if we have no tokens to process
                if (!$questionAnswers) {
                    continue;
                }
                
                // Calculate frequencies for every custom input answer
                $counts = [];
                foreach ($questionAnswers as $answer) {
                    $key = mb_strtolower($answer->answerText);
                    if (isset($counts[$key])) {
                        $counts[$key]['count'] += 1;
                    } else {
                        $counts[$key] = [
                            'value'     => $answer->answerText,
                            'count'     => 1
                        ];
                    }
                }
                
                // Merge frequencies to our results
                foreach ($counts as $key => $count) {
                    $results['frequencies'][$question->id][] = [
                        'value' => $key,
                        'count' => $count['count']
                    ];
                }
    
            // Questions with fixed values
            } else {
                $possibleValues = $answerObj->valuesFlat();
                $counts = [];
   
                // If the question allows a N/A option, add a -1 value
                if ($question->isNA) {
                   $counts['-1'] = 0;
                }

                // Initialize possible values to zero
                foreach ($possibleValues as $val) {
                   $key = strval($val);
                   $counts[$key] = 0;
                }

                // Calculate frequencies of each question value
                if ($questionAnswers) {
                    foreach ($questionAnswers as $answer) {
                       $key = $isText ? $answer->answerText : strval($answer->answerValue);
                       if (isset($counts[$key])) {
                           $counts[$key] += 1;
                       }
                    }
                }

                // Build a proper result array
                foreach ($counts as $key => $count) {
                    $desc = strval($answerObj->descriptionOfValue($key));
                    $results['frequencies'][$question->id][] = [
                       'value'          => $key,
                       'description'    => $desc,
                       'count'          => $count
                   ];
                }
            }
            
            $results['totalAnswers'] += count($questionAnswers);
        }
       
        return $results;
    }

	/**
	* Returns the category that the question belongs to
	*/
	public function category()
	{
		return $this->belongsTo('\App\Models\QuestionCategory', 'categoryId');
	}

	/**
	* Returns the tags for the question
	*/
	public function tags()
	{
		return $this->hasMany('\App\Models\QuestionTag', 'questionId');
	}

	/**
	* Returna list of the tags.
	*/
	public function tagsList()
	{
		return $this->tags()->pluck('tag');
	}

	/**
	* Returns the custom values for the question
	*/
	public function customValues()
	{
		return $this->hasMany('\App\Models\QuestionCustomValue', 'questionId');
	}

	/**
	* Returns the follow up questions
	*/
	public function followUpQuestions()
	{
		return $this->hasMany('\App\Models\QuestionFollowUpQuestion', 'questionId');
	}

	/**
	* Returns the list of follow up questions
	*/
	public function followUpQuestionsList()
	{
		$followUpQuestions = [];

		foreach ($this->followUpQuestions as $followUpQuestion) {
			array_push($followUpQuestions, (object)[
				'followUpValue' => $followUpQuestion->followUpValue,
				'id' => $followUpQuestion->followUpQuestionId,
			]);
		}

		return $followUpQuestions;
	}

	/**
	* Returns the answer type object for the current question
	*/
	public function answerTypeObject()
	{
		if ($this->answerType == \App\AnswerType::CUSTOM_SCALE_TYPE) {
			if ($this->cachedCustomScaleObject == null) {
				$this->cachedCustomScaleObject = \App\AnswerType::createCustomScale($this);
			}

			return $this->cachedCustomScaleObject;
		} else {
			return \App\AnswerType::getType($this->answerType);
		}
	}

	/**
	* Creates a new question
	*/
	public static function make($ownerId, $category, $text, $answerTypeId, $optional, $isNA, $isSurvey = false, $customValues = [])
	{
		$answerType = \App\AnswerType::getType($answerTypeId);

        if ($answerType == null) {
            $answerTypeId = 0;
        }

		$question = new \App\Models\Question;
        $question->text = $text;
        $question->ownerId = $ownerId;
        $question->answerType = $answerTypeId;
        $question->optional = $optional;
		$question->isSurvey = $isSurvey;
		$question->isNA = $isNA;
        $category->questions()->save($question);

		foreach ($customValues as $customValue) {
			$newValue = new \App\Models\QuestionCustomValue;
			$newValue->name = $customValue;
			$question->customValues()->save($newValue);
		}

		return $question;
	}

	/**
	* Creates a (database) copy of the current question
	*/
	public function copy($isSurvey = true, $categoryId = null)
	{
		$copyQuestion = new Question;
		$copyQuestion->text = $this->text;
		$copyQuestion->ownerId = $this->ownerId;

		if ($categoryId == null) {
			$copyQuestion->categoryId = $this->categoryId;
		} else {
			$copyQuestion->categoryId = $categoryId;
		}

		$copyQuestion->answerType = $this->answerType;
		$copyQuestion->optional = $this->optional;
		$copyQuestion->isNA = $this->isNA;
		$copyQuestion->isSurvey = $isSurvey;
		$copyQuestion->save();

		//Copy tags
		foreach ($this->tags as $tag) {
			$newTag = new \App\Models\QuestionTag;
			$newTag->tag = $tag->tag;
			$copyQuestion->tags()->save($newTag);
		}

		//Copy custom values
		foreach ($this->customValues as $value) {
			$newValue = new \App\Models\QuestionCustomValue;
			$newValue->name = $value->name;
			$copyQuestion->customValues()->save($newValue);
		}

		return $copyQuestion;
	}
    
    /**
     * Returns an array of possible answers to this question.
     *
     * @return  array
     */
    public function getAnswerAttribute()
    {
        return $this->answerTypeObject()->jsonSerialize();
    }
}
