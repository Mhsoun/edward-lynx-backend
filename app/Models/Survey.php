<?php namespace App\Models;

use Lang;
use Carbon\Carbon;
use App\Models\User;
use App\Contracts\Routable;
use App\Contracts\JsonHalLinking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
* Represents a survey
*/
class Survey extends Model implements Routable, JsonHalLinking
{
    
	const TIMEZONE = 'Europe/Stockholm';

	/**
	* The database table used by the model
	*/
	protected $table = 'surveys';

	/**
	* The fillable fields
	*/
	protected $fillable = ['name', 'startDate', 'endDate'];

	//Don't generate the automatic timestamp columns
	public $timestamps = false;

	protected $dates = ['startDate', 'endDate', 'autoRemindingDate'];

    /**
     * List of fields whitelisted for serialization to JSON.
     * 
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'type',
        'lang',
        'startDate',
        'endDate',
        'enableAutoReminding',
        'autoRemindingDate',
        'description',
        'thankYouText',
        'questionInfoText'
    ];
    
    /**
     * List of fields hidden when summary serializing to JSON.
     *
    * @var  array
     */
    protected $hiddenWhenSummarized = [
        'enableAutoReminding',
        'autoRemindingDate',
        'thankYouText',
        'questionInfoText'
    ];
    
    /**
     * Returns the API url to this survey.
     *
     * @param   string  $prefix
     * @return  string
     */
    public function url($prefix = '')
    {
        return route('api1-survey', $this);
    }
    
    /**
     * Limits surveys returned to non-expired surveys.
     *
     * @param   Illuminate\Database\Eloquent\Builder
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid(Builder $query)
    {
        return $query->where('endDate', '>', Carbon::now());
    }

	/**
	* Returns name of the type
	*/
	public function typeName()
	{
		return \App\SurveyTypes::name($this->type);
	}

	/**
	* Returns the owner of the survey
	*/
	public function owner()
	{
		return $this->belongsTo('\App\Models\User', 'ownerId');
	}

	/**
	* Returns the original survey, if the current survey is a comparison
	*/
	public function compareAgainstSurvey()
	{
		return $this->hasOne('\App\Models\Survey', 'id', 'compareAgainstSurveyId');
	}

	/**
	* Returns the target group if a group survey
	*/
	public function targetGroup()
	{
		return $this->hasOne('\App\Models\Group', 'id', 'targetGroupId');
	}

	/**
	* Returns the role groups if a group survey
	*/
	public function roleGroups()
	{
		return $this->hasMany('\App\Models\SurveyRoleGroup', 'surveyId');
	}

	/**
	* Returns the role to evaluate if a group survey
	*/
	public function toEvaluateRole()
	{
		$roleId = $this->roleGroups()
			->where('toEvaluate', '=', true)
			->first()
			->roleId;

		return (object)[
			'id' => $roleId,
			'name' => \App\Roles::name($roleId)
		];
	}

	/**
	* Returns the evaluating roles if a group survey
	*/
	public function evaluatingRoles()
	{
		return $this->roleGroups()->where('toEvaluate', '=', false)->get()->map(function($role) {
			return (object)[
				'id' => $role->roleId,
				'name' => \App\Roles::name($role->roleId)
			];
		});
	}

	/**
	* Adds the recipient to the survey
	*/
	public function addRecipient($recipientId, $roleId, $invitedById, $groupId = null, $recipientType = 'recipients')
	{
		$surveyRecipient = new \App\Models\SurveyRecipient;
		$surveyRecipient->recipientId = $recipientId;
		$surveyRecipient->link = str_random(32);
		$surveyRecipient->roleId = $roleId;
		$surveyRecipient->invitedById = $invitedById;
		$surveyRecipient->groupId = $groupId;
        $surveyRecipient->recipientType = $recipientType;
		$this->recipients()->save($surveyRecipient);
		return $surveyRecipient;
	}

	/**
	* Returns the recipients in the survey
	*/
	public function recipients()
	{
		return $this->hasMany('\App\Models\SurveyRecipient', 'surveyId');
	}

	/**
	* Finds the recipient with the given name and email
	*/
	public function findRecipient($name, $email)
	{
		return $this->recipients()
            ->whereRaw(
                'recipientId IN (SELECT recipients.id FROM recipients WHERE name=? AND mail=?)',
                [$name, $email])
            ->first();
	}

	/**
	* Returns the candidates in the survey
	*/
	public function candidates()
	{
		return $this->hasMany('\App\Models\SurveyCandidate', 'surveyId');
	}

	/**
	* Returns the question categories in the survey
	*/
	public function categories()
	{
		return $this->hasMany('\App\Models\SurveyQuestionCategory', 'surveyId');
	}

	/**
	* Returns the questions in the survey
	*/
	public function questions()
	{
		return $this->hasMany('\App\Models\SurveyQuestion', 'surveyId');
	}

	/**
	* Returns the answers in the survey
	*/
	public function answers()
	{
		return $this->hasMany('\App\Models\SurveyAnswer', 'surveyId');
	}

	/**
	* Returns the extra questions in the survey
	*/
	public function extraQuestions()
	{
		return $this->hasMany('\App\Models\SurveyExtraQuestion', 'surveyId');
	}

	/**
	* Returns the extra answers in the survey
	*/
	public function extraAnswers()
	{
		return $this->hasMany('\App\Models\SurveyExtraAnswer', 'surveyId');
	}

	/**
	* Returns the top/worst categories in the survey
	*/
	public function topWorstCategories()
	{
		return $this->hasMany('\App\Models\SurveyTopWorstCategory', 'surveyId');
	}

	/*
	* Returns the invitation text
	*/
	public function invitationText()
	{
		return $this->belongsTo('\App\Models\EmailText', 'invitationTextId');
	}

    /*
    * Returns the candidate invitation text
    */
    public function candidateInvitationText()
    {
        //Create if not exists. This is a dump idea, but there exists survey without this object!
        if (\App\Models\EmailText::where('ownerId', '=', $this->ownerId)
            ->where('id', '=', $this->candidateInvitationTextId)
            ->first() == null) {
            $emailText = new \App\Models\EmailText;
            $emailText->lang = $this->lang;
            $emailText->text = "";
            $emailText->subject = "";
            $emailText->ownerId = $this->ownerId;
            $emailText->save();
            $this->candidateInvitationTextId = $emailText->id;
        }

        return $this->belongsTo('\App\Models\EmailText', 'candidateInvitationTextId');
    }

	/*
	* Returns the evaluated team invitation text
	*/
	public function evaluatedTeamInvitationText()
	{
        //Create if not exists. This is a dump idea, but there exists survey without this object!
        if (\App\Models\EmailText::where('ownerId', '=', $this->ownerId)
            ->where('id', '=', $this->evaluatedTeamInvitationTextId)
            ->first() == null) {
            $emailText = new \App\Models\EmailText;
            $emailText->lang = $this->lang;
            $emailText->text = "";
            $emailText->subject = "";
            $emailText->ownerId = $this->ownerId;
            $emailText->save();
            $this->evaluatedTeamInvitationTextId = $emailText->id;
        }

		return $this->belongsTo('\App\Models\EmailText', 'evaluatedTeamInvitationTextId');
	}

	/*
	* Returns the to evaluate text
	*/
	public function toEvaluateText()
	{
		return $this->belongsTo('\App\Models\EmailText', 'toEvaluateInvitationTextId');
	}

	/*
	* Returns the manual reminding text
	*/
	public function manualRemindingText()
	{
		return $this->belongsTo('\App\Models\EmailText', 'manualRemindingTextId');
	}

	/*
	* Returns the evaluated team invitation text
	*/
	public function userReportText()
	{
        //Create if not exists. This is a dump idea, but there exists survey without this object!
        if (\App\Models\EmailText::where('ownerId', '=', $this->ownerId)
            ->where('id', '=', $this->userReportTextId)
            ->first() == null) {
            $emailText = new \App\Models\EmailText;
            $emailText->lang = $this->lang;
            $emailText->text = "";
            $emailText->subject = "";
            $emailText->ownerId = $this->ownerId;
            $emailText->save();
            $this->userReportTextId = $emailText->id;
        }

		return $this->belongsTo('\App\Models\EmailText', 'userReportTextId');
	}

	/*
	* Returns the evaluated team invitation text
	*/
	public function inviteOthersRemindingText()
	{
        //Create if not exists. This is a dump idea, but there exists survey without this object!
        if (\App\Models\EmailText::where('ownerId', '=', $this->ownerId)
            ->where('id', '=', $this->inviteOthersReminderTextId)
            ->first() == null) {
            $emailText = new \App\Models\EmailText;
            $emailText->lang = $this->lang;
            $emailText->text = Lang::get('surveys.inviteRemindingMailDefaultText', [], $this->lang);
            $emailText->subject = Lang::get('surveys.inviteRemindingMailDefaultSubject', [], $this->lang);
            $emailText->ownerId = $this->ownerId;
            $emailText->save();
            $this->inviteOthersReminderTextId = $emailText->id;
        }

		return $this->belongsTo('\App\Models\EmailText', 'inviteOthersReminderTextId');
	}

	/**
	* Returns the given role with members
	*/
	public function roleWithMembers($roleGroup)
	{
		$members = $this->recipients()
			->where('roleId', '=', $roleGroup->roleId)
			->get();

		$hasAnswered = [];
		$hasNotAnswered = [];

		foreach ($members as $member) {
			if ($member->hasAnswered) {
				array_push($hasAnswered, $member);
			} else {
				array_push($hasNotAnswered, $member);
			}
		}

		return (object)[
			'id' => $roleGroup->roleId,
			'name' => \App\Roles::name($roleGroup->roleId),
			'toEvaluate' => $roleGroup->toEvaluate,
			'members' => $members,
			'hasAnswered' => $hasAnswered,
			'hasNotAnswered' => $hasNotAnswered
		];
	}

	/**
    * Returns the roles with members
    */
    public function rolesWithMembers()
    {
        $roleGroups = [];

        foreach ($this->roleGroups as $roleGroup) {
        	array_push($roleGroups, $this->roleWithMembers($roleGroup));
        }

        //Make sure that the 'toEvaluate' role is first
        array_sort($roleGroups, function($role) {
        	return $role->toEvaluate;
        });

        return $roleGroups;
    }

	/**
    * Returns the active report template
    */
    public function activeReportTemplate()
    {
		if ($this->activeReportTemplateId !== null) {
    		return \App\Models\ReportTemplate::find($this->activeReportTemplateId);
		} else {
			return null;
		}
    }

    /**
    * Returns the saved reports
    */
    public function reports()
    {
    	return $this->hasMany('\App\Models\SurveyReportFile', 'surveyId');
    }

	/**
    * Returns the user reports
    */
    public function userReports()
    {
    	return $this->hasMany('\App\Models\SurveyUserReport', 'surveyId');
    }

	/**
	* Returns the end date for the given recipient
	*/
	public function endDateFor($invitedById = null, $recipientId = null)
	{
		$surveyRecipient = $this->recipients()
			->where('invitedById', '=', $invitedById)
			->where('recipientId', '=', $recipientId)
			->first();

		if ($surveyRecipient != null) {
			if (\App\SurveyTypes::isNewProgress($this)) {
				if ($surveyRecipient->isCandidate()) {
					$endDate = $surveyRecipient->candidate()->endDate;
					if ($endDate != null) {
						return $endDate;
					}
				} else {
					$endDate = $surveyRecipient->invitedByCandidate()->endDateRecipients;
					if ($endDate != null) {
						return $endDate;
					}
				}
			} else if ($this->type == \App\SurveyTypes::Individual) {
				$endDate = $surveyRecipient->invitedByCandidate()->endDate;
				if ($endDate != null) {
					return $endDate;
				}
			}
		}

		return $this->endDate;
	}

    /**
    * Indicates if the end date has passed
    */
    public function endDatePassed($invitedById = null, $recipientId = null)
    {
		$endDate = $this->endDateFor($invitedById, $recipientId);
    	return \Carbon\Carbon::now()->gt($endDate);
    }

    /**
    * Returns the view data for the questions in the given category
    */
    private function questionsViewData($category, $surveyRecipient = null)
    {
    	$questions = [];

        foreach ($category->questions()->orderBy('order', 'asc')->get() as $question) {
            if ($surveyRecipient == null || $question->isTargetOf($surveyRecipient)) {
                array_push($questions, (object)[
                    'id' => $question->questionId,
                    'text' => $question->question->text,
                    'answerType' => $question->question->answerTypeObject(),
                    'tags' => $question->question->tagsList(),
                    'optional' => $question->question->optional,
                    'isNA' => $question->question->isNA,
                    'isSurvey' => $question->question->isSurvey,
					'order' => $question->order,
					'isFollowUpQuestion' => $question->question->isFollowUpQuestion,
					'followUpQuestions' => $question->question->followUpQuestionsList()
                ]);
            }
        }

        return $questions;
    }

    /**
    * Returns the view data for the given category
    */
    private function categoryViewData($category, $surveyRecipient = null)
    {
        return (object)[
        	'id' => $category->category->id,
            'title' => $category->category->title,
            'description' => $category->category->description,
            'questions' => $this->questionsViewData($category, $surveyRecipient),
			'order' => $category->order,
			'isSurvey' => $category->category->isSurvey,
        ];
    }

    /**
    * Returns the categories and questions, ordered correctly
    *
    * @param    bool    $asModels
    * @return   array
    */
    public function categoriesAndQuestions($asModels = false)
    {
    	$categories = [];

        foreach ($this->categories()->orderBy('order', 'asc')->get() as $category) {
            if ($asModels) {
                array_push($categories, $category);
            } else {
                array_push($categories, $this->categoryViewData($category));
            }
        }

        return $categories;
    }

    /**
    * Finds the root categories
    */
    private function findRootCategories($surveyRecipient = null)
    {
		$categories = [];

    	$findRootCategory = function($category) use (&$categories) {
    		if (array_key_exists($category->id, $categories)) {
    			return $categories[$category->id];
    		} else {
	    		$rootCategory = (object)[
	    			'id' => $category->id,
	    			'title' => $category->title,
                    'description' => $category->description,
					'isSurvey' => $category->isSurvey,
	    			'childCategories' => [],
	    			'questions' => []
	    		];

	    		$categories[$category->id] = $rootCategory;
	    		return $rootCategory;
	    	}
    	};

    	//Identifiy the 'root' categories. Root = parent|has no parent
    	foreach ($this->categories as $category) {
    		$rootCategory = null;
    		if ($category->category->parentCategoryId == null) {
    			$rootCategory = $findRootCategory($category->category);
    		} else {
    			$rootCategory = $findRootCategory($category->category->parentCategory);
    		}

    		if ($category->categoryId == $rootCategory->id) {
    			$rootCategory->questions = $this->questionsViewData($category, $surveyRecipient);
    		} else {
    			array_push($rootCategory->childCategories, $this->categoryViewData($category, $surveyRecipient));
    		}
    	}

    	return $categories;
    }

    /**
    * Returns the view data for the categories
    */
    public function categoriesViewData($surveyRecipient = null)
    {
    	$categories = $this->findRootCategories($surveyRecipient);

    	//Order the child categories
    	$rootChildMinOrder = [];
    	foreach ($categories as $rootCategory) {
    		if (count($rootCategory->childCategories) > 0) {
    			$orders = [];
    			$minRootOrder = PHP_INT_MAX;

    			foreach ($rootCategory->childCategories as $category) {
    				$order = $this->categories()
    					->where('categoryId', '=', $category->id)
    					->first()
    					->order;

    				$orders[$category->id] = $order;
    				$minRootOrder = min($minRootOrder, $order);
    			}

    			usort($rootCategory->childCategories, function($x, $y) use (&$orders) {
    				return $orders[$x->id] - $orders[$y->id];
    			});
    		}
    	}

    	//Order the root categories
    	$rootOrders = [];
    	foreach ($categories as $rootCategory) {
    		$rootCategoryData = $this->categories()
				->where('categoryId', '=', $rootCategory->id)
				->first();
			$order = -1;

			if ($rootCategoryData != null) {
				$order = $rootCategoryData->order;
			} else {
				//If the root category is not in the survey, use the min order of it's children
				$order = $minRootOrder;
			}

			$rootOrders[$rootCategory->id] = $order;
    	}

    	usort($categories, function($x, $y) use (&$rootOrders) {
			return $rootOrders[$x->id] - $rootOrders[$y->id];
		});

        //Remove categories that dont have any questions or child categories
        $categories = array_filter($categories, function($category) {
            return count($category->questions) > 0 || count($category->childCategories) > 0;
        });

    	return $categories;
    }

    /**
    * Returns the extra answers for the given recipients. If null, all are choosen.
    */
    public function extraAnswersForRecipients($recipientIds = null)
    {
    	$recipientAnswers = [];

    	if ($recipientIds === null) {
	    	$recipientIds = $this->recipients->lists('recipientId');
	    }

    	foreach ($recipientIds as $recipientId) {
    		$answers = [];
    		$recipientExtraAnswers = $this->extraAnswers()
    			->where('answeredById', '=', $recipientId)
    			->get();

    		foreach ($recipientExtraAnswers as $answer) {
    			$answers[$answer->extraQuestion->id] = (object)[
					'id' => $answer->extraQuestion->id,
    				'title' => $answer->extraQuestion->name(),
    				'value' => $answer->extraQuestion->getValueName($answer->value()),
					'rawValue' => $answer->value()
    			];
    		}

    		array_push($recipientAnswers, (object)[
    			'recipient' => $this->recipients()->where('recipientId', '=', $recipientId)->first(),
    			'answers' => $answers
    		]);
    	}

    	return $recipientAnswers;
    }

    /**
    * Indicates if the given recipient has the given answer for the given extra question
    */
    public function hasExtraQuestionAnswer($recipientId, $extraQuestionId, $answer)
    {
		$extraQuestion = $this->extraQuestions()
			->where('extraQuestionId', '=', $extraQuestionId)
			->first();

		if ($extraQuestion == null) {
			return false;
		}

		$extraQuestion = $extraQuestion->extraQuestion;

		$extraQuestionAnswer = $this->extraAnswers()
			->where('answeredById', '=', $recipientId)
			->where('extraQuestionId', '=', $extraQuestionId)
			->first();

		if ($extraQuestionAnswer != null) {
			if ($extraQuestion->type == \App\ExtraAnswerValue::Hierarchy) {
				$isParentOf = function ($value, $answer) use (&$isParentOf) {
					if ($value == null) {
						return false;
					}

					if ($value->id == $answer) {
						return true;
					}

					if ($value->parentValueId != null) {
						return $isParentOf($value->parentValue, $answer);
					} else {
						return false;
					}
				};

				$currentValue = $extraQuestion->values()
					->where('id', '=', $extraQuestionAnswer->value())
					->first();

				return $isParentOf($currentValue, $answer);
			} else {
	    		return $extraQuestionAnswer->value() == $answer;
			}
		}

    	return false;
    }
    
    /**
     * Returns the answer key of the provided user.
     * Returns NULL if the provided user has already answered the survey.
     *
     * @param   App\Models\User $user
     * @return  string|null
     */
    public function answerKeyOf(User $user)
    {
        $recipient = $this->recipients()
                          ->where([
                              'recipientId'   => $user->id,
                              'recipientType' => 'users',
                              'hasAnswered'   => false
                          ])
                          ->first();
        if ($recipient) {
          return $recipient->link;
        } else {
          return null;
        }
    }
    
    /**
     * Directly override JSON serialization for this model
     * so we can change attributes without overwriting attribute
     * values through accessors.
     *
     * @param   integer $options
     * @return  array
     */
    public function jsonSerialize($options = 0)
    {
        $data = parent::jsonSerialize();
        $currentUser = request()->user();
        
        $data['startDate'] = $this->startDate->toIso8601String();
        $data['endDate'] = $this->endDate->toIso8601String();
        $data['key'] = $this->answerKeyOf($currentUser);
        $data['status'] = SurveyRecipient::surveyStatus($this, $currentUser);
        
        if ($options == 0) {
            $data = $this->getEmailsForJson($data);
        } elseif ($options == 1) {
            foreach ($this->hiddenWhenSummarized as $field) {
                unset($data[$field]);
            }
        }
            
        return $data;
    }
    
    /**
     * Returns email information associated to this survey
     * for serialization to JSON.
     *
     * @param   array   $data
     * @return  array
     */
    protected function getEmailsForJson(array $data)
    {
        $data['emails'] = [];
        $texts = [
            'invitation',
            'manualReminding',
            'toEvaluate',
            'inviteOthersReminding',
            'candidateInvitation'
        ];
        
        foreach ($texts as $text) {
            $method = "{$text}Text";
            $emailText = $this->{$method};
            
            if ($emailText->exists) {
                $data['emails'][$text] = [
                    'subject'   => $emailText->subject,
                    'text'      => $emailText->text
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Returns additional links to be appended to this object's
     * _links JSON-HAL representation.
     *
     * @return  array
     */
    public function jsonHalLinks()
    {
        return [
            'questions' => route('api1-survey-questions', $this),
            'answers'   => route('api1-survey-answers', $this)
        ];
    }
    
    /**
     * Returns frequencies and statistics of this survey's answers.
     *
     * @return  array
     */
    public function calculateAnswers()
    {
        $questions = $this->questions->map(function($q) {
            return $q->question;
        });
        
        $answers = new Collection;
        foreach ($this->recipients as $recipient) {
            // Skip users who didn't finish answering yet.
            if (!$recipient->hasAnswered) {
                continue;
            }
            
            foreach ($recipient->answers as $answer) {
                if (!$answers->has($answer->questionId)) {
                    $answers->put($answer->questionId, new Collection);
                }
                
                $answers->get($answer->questionId)->push($answer);
            }
            
            dd($answers->toArray());
            
            $answers->merge($recipient->answers);
        }
        //return Question::calculateAnswers($questions, $answers);
    }
}