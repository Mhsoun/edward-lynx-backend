<?php namespace App;
use Lang;

/**
* Represents an answer value
*/
class AnswerValue
{
    public $value;
    public $description;

    /**
    * Creates a new answer value
    */
    public function __construct($value, $description)
    {
        $this->value = $value;
        $this->description = $description;
    }
}

/**
* Represents an answer type
*/
class AnswerType
{
    private $typeId;
    private $values;
    private $descriptionText;
    private $helpText;
    private $isText = false;
    private $isNumeric = false;
    private $valueExplanations = [];

    private static $answerTypes = null;

    const NA_VALUE = -1;
    const YES_OR_NO_TYPE = 3;
    const CUSTOM_SCALE_TYPE = 8;

    /**
    * Creates a new answer type
    */
    public function __construct($typeId, $descriptionText, $helpText, $values, $isNumeric = false)
    {
        $this->typeId = $typeId;
        $this->values = $values;
        $this->descriptionText = $descriptionText;
        $this->helpText = $helpText;
        $this->isNumeric = $isNumeric;
    }

    /**
    * Returns the id of the answer type
    */
    public function id()
    {
        return $this->typeId;
    }

    /**
    * Indicates if the given value is valid for the answer type
    */
    public function isValidValue($value) {
        if ($value == AnswerType::NA_VALUE) {
            return true;
        }

        if ($this->isText()) {
            return true;
        }

        foreach ($this->values as $validValue) {
            if ($validValue->value == $value) {
                return true;
            }
        }

        return false;
    }

    /**
    * Returns a description of the given value
    */
    public function descriptionOfValue($value)
    {
        if ($value == AnswerType::NA_VALUE) {
            return Lang::get('answertypes.nA');
        }

        foreach ($this->values as $validValue) {
            if ($validValue->value == $value) {
                return $validValue->description;
            }
        }

        return '';
    }

    /**
    * Returns the values
    */
    public function values()
    {
        return $this->values;
    }

    /**
    * Returns the maximum value
    */
    public function maxValue()
    {
        $maxValue = 0;

        foreach ($this->values as $value) {
            $maxValue = max($value->value, $maxValue);
        }

        return $maxValue;
    }

    /**
    * Returns the description text
    */
    public function descriptionText()
    {
        return $this->descriptionText;
    }

    /**
    * Returns the help text
    */
    public function helpText()
    {
        return $this->helpText;
    }

    /**
    * Indicates if the input is text
    */
    public function isText()
    {
        return $this->isText;
    }

    /**
    * Indicates if the values are numeric
    */
    public function isNumeric()
    {
        return $this->isNumeric;
    }

    /**
    * Returns the value explanations
    */
    public function valueExplanations()
    {
        return $this->valueExplanations;
    }

    /**
    * Changes the isText property
    */
    public function withIsText($isText)
    {
        $this->isText = $isText;
        return $this;
    }

    /**
    * Changes the valueExplanations property
    */
    public function withValueExplanations($valueExplanations)
    {
        $this->valueExplanations = $valueExplanations;
        return $this;
    }

    /**
    * Creates values in the given ragne
    */
    private static function createValueRange($minValue, $maxValue)
    {
        $values = [];

        for ($i = $minValue; $i <= $maxValue; $i++) {
            array_push($values, new AnswerValue($i, $i));
        }

        return $values;
    }

    /**
    * Returns the defined answer types
    */
    public static function answerTypes()
    {
        if (AnswerType::$answerTypes == null) {
            $answerTypes = [
                new AnswerType(
                    0,
                    Lang::get('answertypes.numericScale') . ' 1-5 ' . Lang::get('answertypes.scale'),
                    Lang::get('answertypes.numeric15ScaleHelpText'),
                    AnswerType::createValueRange(1, 5),
                    true),
                new AnswerType(
                    1,
                    Lang::get('answertypes.numericScale') . ' 1-10 ' . Lang::get('answertypes.scale'),
                    Lang::get('answertypes.numeric110ScaleHelpText'),
                    AnswerType::createValueRange(1, 10),
                    true),
                new AnswerType(
                    2,
                    Lang::get('answertypes.agreementScale'),
                    Lang::get('answertypes.agreementScaleHelpText'),
                    [
                        new AnswerValue(0, Lang::get('answertypes.disagree')),
                        new AnswerValue(1, Lang::get('answertypes.somewhatDisagree')),
                        new AnswerValue(2, Lang::get('answertypes.somewhatAgreee')),
                        new AnswerValue(3, Lang::get('answertypes.agree'))
                    ]),
                new AnswerType(
                    AnswerType::YES_OR_NO_TYPE,
                    Lang::get('answertypes.yesOrNo'),
                    Lang::get('answertypes.yesOrNoScaleHelpText'),
                    [new AnswerValue(0, Lang::get('answertypes.no')), new AnswerValue(1, Lang::get('answertypes.yes'))]),
                new AnswerType(
                    4,
                    Lang::get('answertypes.strongAgreementScale'),
                    Lang::get('answertypes.strongAgreementScaleHelpText'),
                    [
                        new AnswerValue(0, Lang::get('answertypes.stronglyDisagree')),
                        new AnswerValue(1, Lang::get('answertypes.disagree')),
                        new AnswerValue(2, Lang::get('answertypes.agree')),
                        new AnswerValue(3, Lang::get('answertypes.stronglyAgree')),
                    ]),
                (new AnswerType(
                    5,
                    Lang::get('answertypes.text'),
                    Lang::get('answertypes.textScaleHelpText'),
                    [])
                )->withIsText(true),
                new AnswerType(
                    6,
                    Lang::get('answertypes.invertedAgreementScale'),
                    Lang::get('answertypes.invertedAgreementScaleHelpText'),
                    [
                        new AnswerValue(3, Lang::get('answertypes.disagree')),
                        new AnswerValue(2, Lang::get('answertypes.somewhatDisagree')),
                        new AnswerValue(1, Lang::get('answertypes.somewhatAgreee')),
                        new AnswerValue(0, Lang::get('answertypes.agree'))
                    ]),
                (new AnswerType(
                    7,
                    Lang::get('answertypes.1to10WithExplanation'),
                    Lang::get('answertypes.1to10WithExplanationHelpText'),
                    AnswerType::createValueRange(1, 10),
                    true))->withValueExplanations([
                        (object)['lower' => 0.0, 'upper' => 0.2, 'explanation' => Lang::get('answertypes.needsToImproveImmediately')],
                        (object)['lower' => 0.2, 'upper' => 0.4, 'explanation' => Lang::get('answertypes.needsToImprove')],
                        (object)['lower' => 0.4, 'upper' => 0.6, 'explanation' => Lang::get('answertypes.goodEnoughForRole')],
                        (object)['lower' => 0.6, 'upper' => 0.8,'explanation' => Lang::get('answertypes.good')],
                        (object)['lower' => 0.8, 'upper' => 1.0, 'explanation' => Lang::get('answertypes.veryGood')],
                    ]),
                new AnswerType(
                    AnswerType::CUSTOM_SCALE_TYPE,
                    Lang::get('answertypes.customScale'),
                    Lang::get('answertypes.customScaleHelpText'),
                    []),
            ];

            AnswerType::$answerTypes = [];
            foreach ($answerTypes as $id => $answerType) {
                AnswerType::$answerTypes[$id] = $answerType;
            }
        }

        return AnswerType::$answerTypes;
    }

    /**
    * Returns the given answer type
    */
    public static function getType($answerType)
    {
        if (array_key_exists($answerType, AnswerType::answerTypes())) {
            return AnswerType::answerTypes()[$answerType];
        } else {
            return null;
        }
    }

    /**
    * Returns the answer type for the given question
    */
    public static function forQuestion($questionId)
    {
        $question = \App\Models\Question::find($questionId);
        if ($question != null) {
            return $question->answerTypeObject();
        } else {
            return null;
        }
    }

    /**
    * Creates the custom scale valus for the given question
    */
    public static function createCustomScale($question)
    {
        $values = [];

        $i = 1;
        foreach ($question->customValues as $customValue) {
            array_push($values, new AnswerValue($i, $customValue->name));
        }

        return new AnswerType(
            AnswerType::CUSTOM_SCALE_TYPE,
            Lang::get('answertypes.customScale'),
            Lang::get('answertypes.customScaleHelpText'),
            $values);
    }
}
