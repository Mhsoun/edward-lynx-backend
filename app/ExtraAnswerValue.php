<?php namespace App;
use Lang;

/**
* Represents extra answer value
*/
class ExtraAnswerValue
{
	private $id;
	private $typeId;
	private $label;
	private $options;
	private $isOptional;

	const Text = 0;
	const Date = 1;
	const Options = 2;
	const Hierarchy = 3;

	/**
	* Creates a new extra answer type
	*/
	private function __construct($id, $typeId, $isOptional, $label, $options)
    {
    	$this->id = $id;
        $this->typeId = $typeId;
        $this->isOptional = $isOptional;
        $this->label = $label;
        $this->options = $options;
    }

    /**
    * Returns the id
    */
    public function id()
    {
        return $this->id;
    }

    /**
    * Returns the type
    */
    public function type()
    {
        return $this->typeId;
    }

    /**
    * Returns if the value is optional
    */
    public function isOptional()
    {
        return $this->isOptional;
    }

    /**
    * Returns the label
    */
    public function label()
    {
        return $this->label;
    }

    /**
    * Returns the options to choose (if Options/Hierarchy type)
    */
    public function options()
    {
    	return $this->options;
    }

	/**
	* Flattens all the options to one array (if Hierarchy type)
	*/
	public function flattenOptions()
	{
		$values = [];

		$flattenValues = function ($parentName, $value) use (&$values, &$flattenValues) {
			$newValue = clone $value;
			if ($parentName != '') {
				$newValue->name = $parentName . ' → ' . $value->name;
			}

			unset($newValue->children);
			array_push($values, $newValue);

			foreach ($value->children as $child) {
				$flattenValues($newValue->name, $child);
			}
		};

		foreach ($this->options as $value) {
			$flattenValues('', $value);
		}

		return $values;
	}

    /**
    * Indicates if the given date is valid
    */
    private function isValidDate($date)
	{
	    $d = \DateTime::createFromFormat(\App\Http\Controllers\SurveyController::DATE_FORMAT, $date);
	    return $d && $d->format(\App\Http\Controllers\SurveyController::DATE_FORMAT) == $date;
	}

	/**
	* Indicates if the given value is a valid hierarchy value
	*/
	private function isValidHierarchyValue($currentValue, $value)
	{
		if (count($currentValue->children) == 0 && $currentValue->value == $value) {
			return true;
		}

		foreach ($currentValue->children as $childValue) {
			if ($this->isValidHierarchyValue($childValue, $value)) {
				return true;
			}
		}

		return false;
	}

    /**
    * Indicates if the given value is valid for the current answer
    */
    public function isValidValue($value)
    {
    	switch ($this->typeId) {
    		case ExtraAnswerValue::Text:
    			return true;
    		case ExtraAnswerValue::Date:
    			return $this->isValidDate($value);
    		case ExtraAnswerValue::Options:
    			foreach ($this->options as $id => $option) {
    				if ($id == $value) {
    					return true;
    				}
    			}

    			return false;
			case ExtraAnswerValue::Hierarchy:
				foreach ($this->options as $option) {
					if ($this->isValidHierarchyValue($option, $value)) {
						return true;
					}
				}

				return false;
    	}

    	return false;
    }

    /**
    * Creates a text value
    */
    public static function createTextValue($id, $isOptional, $label)
	{
        return new ExtraAnswerValue($id, ExtraAnswerValue::Text, $isOptional, $label, []);
    }

    /**
    * Creates a options value type
    */
    public static function createOptionsValue($id, $isOptional, $label, $options)
	{
    	return new ExtraAnswerValue($id, ExtraAnswerValue::Options, $isOptional, $label, $options);
    }

    /**
    * Creates a date value
    */
    public static function createDateValue($id, $isOptional, $label)
	{
    	return new ExtraAnswerValue($id, ExtraAnswerValue::Date, $isOptional, $label, []);
    }

	/**
	* Creates a hierarchy value
	*/
	public static function createHierarchyValue($id, $isOptional, $label, $options)
	{
		return new ExtraAnswerValue($id, ExtraAnswerValue::Hierarchy, $isOptional, $label, $options);
	}

	/**
	* Adds children for the given parent
	*/
	private static function addChildren(&$parent, $children, $lang = null)
	{
		foreach ($children as $value) {
			$newValue = (object)[
				'value' => $value->id,
				'name' => $value->name($lang),
				'children' => [],
				'isRoot' => $value->parentValueId == null,
				'parentId' => $value->parentValueId
			];

			array_push($parent, $newValue);
			ExtraAnswerValue::addChildren($newValue->children, $value->children);
		}
	}

    /**
    * Creates an extra answer value from the given database entry
    */
    private static function fromDatabase($extraQuestion, $lang = null)
    {
        $values = [];

		if ($extraQuestion->type != ExtraAnswerValue::Hierarchy) {
	        foreach ($extraQuestion->values as $value) {
	            $values[$value->id] = $value->name($lang);
	        }
		} else {
			ExtraAnswerValue::addChildren(
				$values,
				$extraQuestion->values()->where('parentValueId', '=', null)->get(),
				$lang);
		}

        switch ($extraQuestion->type) {
            case ExtraAnswerValue::Text:
                return ExtraAnswerValue::createTextValue(
                    $extraQuestion->id,
                    $extraQuestion->isOptional,
                    $extraQuestion->name($lang));
            case ExtraAnswerValue::Date:
                return ExtraAnswerValue::createDateValue(
                    $extraQuestion->id,
                    $extraQuestion->isOptional,
                    $extraQuestion->name($lang));
            case ExtraAnswerValue::Options:
                return ExtraAnswerValue::createOptionsValue(
                    $extraQuestion->id,
                    $extraQuestion->isOptional,
                    $extraQuestion->name($lang),
                    $values);
			case ExtraAnswerValue::Hierarchy:
				return ExtraAnswerValue::createHierarchyValue(
					$extraQuestion->id,
					$extraQuestion->isOptional,
					$extraQuestion->name($lang),
					$values);
        }

        return null;
    }

    /**
    * Returns the extra values for the given survey
    */
    public static function valuesForSurvey($survey)
    {
        $extraQuestions = [];

        foreach ($survey->extraQuestions as $extraQuestion) {
            array_push($extraQuestions, ExtraAnswerValue::fromDatabase($extraQuestion->extraQuestion, $survey->lang));
        }

        return $extraQuestions;
    }

    /**
    * Returns the extra values for the given user (includes system)
    */
    public static function valuesForUser($userId)
    {
        $allExtraQuestions = [];

        //Add system
        foreach (\App\Models\ExtraQuestion::whereNull('ownerId')->get() as $extraQuestion) {
            array_push($allExtraQuestions, $extraQuestion);
        }

        //Add user
        foreach (\App\Models\ExtraQuestion::where('ownerId', '=', $userId)->get() as $extraQuestion) {
            array_push($allExtraQuestions, $extraQuestion);
        }

        $extraQuestions = [];

        foreach ($allExtraQuestions as $extraQuestion) {
            array_push($extraQuestions, ExtraAnswerValue::fromDatabase($extraQuestion));
        }

        return $extraQuestions;
    }
}
