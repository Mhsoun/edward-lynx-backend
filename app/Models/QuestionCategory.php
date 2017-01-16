<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a question category
*/
class QuestionCategory extends Model
{
	protected $fillable = ['title'];
	public $timestamps = false;
    
    protected $visible = ['id', 'title', 'description', 'isSurvey'];

    /**
     * Returns the category for instant feedbacks.
     *
     * @param   App\Models\User     $user
     * @param   string              $lang
     * @return  App\Models\QuestionCategory
     */
    public static function findCategoryForInstantFeedbacks(User $user, $lang)
    {
        $title = 'Instant Feedbacks Category';
        $category = self::where([
            'title'     => $title,
            'lang'      => $lang,
            'ownerId'   => $user->id
        ])->first();
        
        if ($category == null) {
            $category = new self;
            $category->title = $title;
            $category->lang = $lang;
            $category->description = '';
            $category->ownerId = $user->id;
            $category->save();
        }
        
        return $category;
    }

	/**
	* Returns the questions in the category
	*/
	public function questions()
	{
		return $this->hasMany('\App\Models\Question', 'categoryId');
	}

	/**
	* Creates a (database) copy of the current category
	*/
	public function copy($isSurvey = true)
	{
		$copyCategory = new QuestionCategory;
		$copyCategory->title = $this->title;
		$copyCategory->lang = $this->lang;
		$copyCategory->description = $this->description;
		$copyCategory->ownerId = $this->ownerId;
		$copyCategory->parentCategoryId = $this->parentCategoryId;
		$copyCategory->targetSurveyType = $this->targetSurveyType;
		$copyCategory->isSurvey = $isSurvey;
		$copyCategory->save();
		return $copyCategory;
	}

	/**
	* Returns the parent category
	*/
	public function parentCategory()
	{
		return $this->belongsTo('\App\Models\QuestionCategory', 'parentCategoryId');
	}

	/**
	* Returns the child categories
	*/
	public function childCategories()
	{
		return $this->hasMany('\App\Models\QuestionCategory', 'parentCategoryId');
	}

	/**
	* Returns the full title
	*/
	public function fullTitle()
	{
		$title = "";

		$parentCategory = $this->parentCategory;
		if ($parentCategory != null) {
			$title = $parentCategory->fullTitle() + " > ";
		}

		$title = $title . $this->title;

		return $title;
	}

	/**
	* Determiens if a category with the given title exists
	*/
	public static function exists($ownerId, $title, $surveyType, $lang)
	{
		return QuestionCategory::
            where('title', '=', $title)
            ->where('ownerId', '=', $ownerId)
            ->where('lang', '=', $lang)
            ->where('targetSurveyType', '=', $surveyType)
            ->where('isSurvey', '=', false)
            ->count() > 0;
	}
}
