<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\SurveyTypes;
use App\Models\Survey;
use App\SurveyReportHelpers;

/**
* Represents a controller for managing questions and categories.
* All actions in this controller are AJAX calls.
*/
class QuestionController extends Controller
{
	/**
     * Indicates if the auth user can edit the given category
     */
    private function canEditCategory($category)
    {
        if (Auth::user()->isAdmin) {
            return true;
        } else {
            return $category->ownerId == Auth::user()->id;
        }
    }

    /**
     * Indicates if the auth user can edit the given question
     */
    private function canEditQuestion($question)
    {
        if (Auth::user()->isAdmin) {
            return true;
        } else {
            return $question->ownerId == Auth::user()->id;
        }
    }

    /**
    * Returns the company id for the given request
    */
    private function getCompanyId(Request $request)
    {
       if (Auth::user()->isAdmin && $request->companyId != null) {
            return $request->companyId;
        } else {
            return Auth::user()->id;
        }
    }

    /**
     * Creates a new category
     */
    public function createCategory(Request $request)
    {
        $this->validate($request, [
            'categoryTitle' => 'required',
            'categoryLanguage' => 'required'
        ]);

        $ownerId = $this->getCompanyId($request);
        $targetSurveyType = intval($request->targetSurveyType);

        //Check if a category with the given name exists
        if (\App\Models\QuestionCategory::exists($ownerId, $request->categoryTitle, $targetSurveyType, $request->categoryLanguage)) {
            return response()->json([
                'success' => false,
                'message' => Lang::get('surveys.categoryExists')
            ]);
        }

        $questionCategory = new \App\Models\QuestionCategory;
        $questionCategory->title = $request->categoryTitle;
        $questionCategory->description = $request->categoryDescription ?: "";
        $questionCategory->ownerId = $ownerId;
        $questionCategory->lang = $request->categoryLanguage;
        $questionCategory->isSurvey = false;

        if ($request->targetSurveyType != null && \App\SurveyTypes::isValidType($targetSurveyType)) {
            $questionCategory->targetSurveyType = $targetSurveyType;
        }

        $questionCategory->save();

        return response()->json([
            'success' => true,
            'id' => $questionCategory->id
        ]);
    }

    /**
    * Creates tags for the given question
    */
    private function createTags($question, $tags)
    {
        foreach ($tags as $tag) {
            $questionTag = new \App\Models\QuestionTag;
            $questionTag->tag = $tag;
            $question->tags()->save($questionTag);
        }
    }

    /**
     * Creates a new question
     */
    public function createQuestion(Request $request)
    {
        $this->validate($request, [
            'categoryId' => 'required|integer',
            'questionText' => 'required',
            'answerType' => 'required|integer',
            'optional' => 'required',
            'isNA' => 'required',
        ]);

        $category = \App\Models\QuestionCategory::find($request->categoryId);

        //Check that the category exists and owned by the user
        if ($category == null || !$this->canEditCategory($category)) {
            return response()->json(['success' => false, 'errorMessage' => 'The category does not exist.']);
        }

        $question = \App\Models\Question::make(
            $this->getCompanyId($request),
            $category,
            $request->questionText,
            $request->answerType,
            $request->optional == "true",
            $request->isNA == "true",
            false,
            $request->customValues ?: []);

        if ($request->tags != null) {
            $this->createTags($question, $request->tags);
        }

        return response()->json([
            'success' => true,
            'id' => $question->id,
            'answerType' => $question->answerType,
            'optional' => $question->optional,
            'isNA' => $question->isNA
        ]);
    }

    /**
    * Indicates that a category does not exist
    */
    private function categoryNotExist()
    {
        return response()->json([
            'success' => false,
            'errorMessage' => 'The category does not exist.'
        ]);
    }

    /**
     * Updates a category
     */
    public function updateCategory(Request $request)
    {
        $this->validate($request, [
            'categoryId' => 'required|integer'
        ]);

        $category = \App\Models\QuestionCategory::find($request->categoryId);

        //Check that the category exists and owned by the user
        if ($category == null || !$this->canEditCategory($category)) {
            return $this->categoryNotExist();
        }

        if ($request->title != null) {
            $category->title = $request->title;
        }

        if ($request->description != null) {
            $category->description = $request->description;
        }

        if ($request->parentCategoryId != null) {
            if ($request->parentCategoryId != -1) {
                $parentCategory = \App\Models\QuestionCategory::find($request->parentCategoryId);

                //Check that the category exists and owned by the user
                if ($parentCategory == null || !$this->canEditCategory($parentCategory)) {
                    return $this->categoryNotExist();
                }

                //To avoid parent category recursion.
                if ($parentCategory->parentCategoryId != null) {
                    return response()->json([
                        'success' => true
                    ]);
                }

                $category->parentCategoryId = $request->parentCategoryId;
            } else {
                $category->parentCategoryId = null;
            }
        }

        $category->save();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Deletes a category
     */
    public function destroyCategory(Request $request)
    {
        $this->validate($request, [
            'categoryId' => 'required|integer'
        ]);

        $category = \App\Models\QuestionCategory::find($request->categoryId);

        //Check that the category exists and owned by the user
        if ($category == null || !$this->canEditCategory($category)) {
            return response()->json(['success' => false, 'errorMessage' => 'The category does not exist.']);
        }

        $category->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Updates a question
     */
    public function updateQuestion(Request $request)
    {
        $this->validate($request, [
            'questionId' => 'required|integer'
        ]);

        $question = \App\Models\Question::find($request->questionId);

        //Check that the question exists and owned by the user
        if ($question == null || !$this->canEditQuestion($question)) {
            return response()->json(['success' => false, 'errorMessage' => 'The question does not exist.']);
        }

        if ($request->questionText != null) {
            $question->text = $request->questionText;
        }

        if ($request->answerType != null) {
            $question->answerType = $request->answerType;
        }

        if ($request->optional != null) {
            $question->optional = $request->optional == "true";
        }

        if ($request->isNA != null) {
            $question->isNA = $request->isNA == "true";
        }

        if ($request->tags != null) {
            //Remove the existing tags
            $question->tags()->delete();

            //Add the new one
            $this->createTags($question, $request->tags);
        }

		if ($question->answerType == \App\AnswerType::CUSTOM_SCALE_TYPE) {
			foreach ($request->customValues as $newValue) {
				$customValue = $question->customValues()->find($newValue['id']);
				if ($customValue != null) {
					$customValue->name = $newValue['name'];
					$customValue->save();
				}
			}
		}

        $question->save();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Deletes a question
     */
    public function destroyQuestion(Request $request)
    {
        $this->validate($request, [
            'questionId' => 'required|integer'
        ]);

        $question = \App\Models\Question::find($request->questionId);

        //Check that the question exists and owned by the user
        if ($question == null || !$this->canEditQuestion($question)) {
            return response()->json(['success' => false, 'errorMessage' => 'The question does not exist.']);
        }

        $question->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
