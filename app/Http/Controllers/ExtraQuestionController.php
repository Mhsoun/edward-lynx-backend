<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ExtraQuestion;
use App\Models\ExtraQuestionName;
use App\Models\ExtraQuestionValue;
use App\Models\ExtraQuestionValueName;
use App\ExtraAnswerValue;

/**
* Represents a controller for extra questions
*/
class ExtraQuestionController extends Controller
{
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
    * Returns the id for the given type name
    */
    private function getType($typeName)
    {
    	switch ($typeName) {
    		case "text":
    			return ExtraAnswerValue::Text;
    		case "date":
    			return ExtraAnswerValue::Date;
    		case "options":
    			return ExtraAnswerValue::Options;
    		case "hierarchy":
    			return ExtraAnswerValue::Hierarchy;
    	}

    	return ExtraAnswerValue::Text;
    }

    /**
    * Creates all the values
    */
    private function createAllValues($extraQuestion, $lang, $parentValueId, $data)
    {
        foreach ($data as $value) {
            $questionValue = new ExtraQuestionValue;

            if ($parentValueId != null) {
                $questionValue->parentValueId = $parentValueId;
            }

            $extraQuestion->values()->save($questionValue);

            $questionNameValue = new ExtraQuestionValueName;
            $questionNameValue->name = $value['value'];
            $questionNameValue->lang = $lang;
            $questionValue->names()->save($questionNameValue);

            if (array_key_exists('children', $value)) {
                $this->createAllValues($extraQuestion, $lang, $questionValue->id, $value['children']);
            }
        }
    }

	/**
	* Stores the given extra question
	*/
	public function store(Request $request)
	{
		$this->validate($request, [
			'name' => 'required',
			'type' => 'required',
			'isOptional' => 'required',
			'lang' => 'required',
			'companyId' => 'required|integer'
		]);

		$extraQuestion = new ExtraQuestion;
        $extraQuestion->ownerId = $this->getCompanyId($request);

        $extraQuestion->type = $this->getType($request->type);
        $extraQuestion->isOptional = $request->isOptional == "true";
        $extraQuestion->save();

        //Create the name
        $questionName = new ExtraQuestionName;
        $questionName->name = $request->name;
        $questionName->lang = $request->lang;
        $extraQuestion->names()->save($questionName);

        //Create the values
        if ($request->values != null) {
            if ($extraQuestion->type == ExtraAnswerValue::Options) {
    	        foreach ($request->values as $value) {
    	            $questionValue = new ExtraQuestionValue;
    	            $extraQuestion->values()->save($questionValue);

    	            $questionNameValue = new ExtraQuestionValueName;
    	            $questionNameValue->name = $value;
    	            $questionNameValue->lang = $request->lang;
    	            $questionValue->names()->save($questionNameValue);
    	        }
            } else if ($extraQuestion->type == ExtraAnswerValue::Hierarchy) {
                $this->createAllValues($extraQuestion, $request->lang, null, $request->values);
            }
	    }

        return response()->json([
        	'id' => $extraQuestion->id,
        	'success' => true
        ]);
	}
}
