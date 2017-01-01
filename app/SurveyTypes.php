<?php namespace App;

use Lang;
use InvalidArgumentException;

/**
* The list of survey types
*/
abstract class SurveyTypes
{
    const Individual = 0;
    const Group = 1;
    const Progress = 2;
    const Normal = 3;
    const LTT = 4;

    /**
    * Creates the bit pattern for the given type
    */
    private static function bitPattern($type)
    {
        return 1 << $type;
    }

    /**
    * Indicates if the user can create individual surveys
    */
    static function canCreateIndividual($type)
    {
        return ($type & SurveyTypes::bitPattern(SurveyTypes::Individual)) != 0;
    }

    /**
    * Indicates if the user can create group surveys
    */
    static function canCreateGroup($type)
    {
        return ($type & SurveyTypes::bitPattern(SurveyTypes::Group)) != 0;
    }

    /**
    * Indicates if the user can create progress surveys
    */
    static function canCreateProgress($type)
    {
        return ($type & SurveyTypes::bitPattern(SurveyTypes::Progress)) != 0;
    }

    /**
    * Indicates if the user can create normal surveys
    */
    static function canCreateNormal($type)
    {
        return ($type & SurveyTypes::bitPattern(SurveyTypes::Normal)) != 0;
    }

     /**
    * Indicates if the user can create LTT surveys
    */
    static function canCreateLTT($type)
    {
        return ($type & SurveyTypes::bitPattern(SurveyTypes::LTT)) != 0;
    }

    /**
    * Indicates if the given user can create a survey of the given type
    */
    static function canCreate($userTypes, $type)
    {
        if ($type == SurveyTypes::Individual) {
            return SurveyTypes::canCreateIndividual($userTypes);
        } else if ($type == SurveyTypes::Group) {
            return SurveyTypes::canCreateGroup($userTypes);
        } else if ($type == SurveyTypes::Progress) {
            return SurveyTypes::canCreateProgress($userTypes);
        } else if ($type == SurveyTypes::Normal) {
            return SurveyTypes::canCreateNormal($userTypes);
        } else if ($type == SurveyTypes::LTT) {
            return SurveyTypes::canCreateLTT($userTypes);
        } else {
            return false;
        }
    }

    /**
    * Returns the name of the given type
    */
    static function name($type)
    {
        if ($type == SurveyTypes::Individual) {
            return Lang::get('surveys.individualType');
        } else if ($type == SurveyTypes::Group) {
            return Lang::get('surveys.groupType');
        } else if ($type == SurveyTypes::Progress) {
            return Lang::get('surveys.progressType');
        } else if ($type == SurveyTypes::Normal) {
            return Lang::get('surveys.normalType');
        } else if ($type == SurveyTypes::LTT) {
            return Lang::get('surveys.lttType');
        } else {
            return '';
        }
    }

    /**
    * Indicates if the given type works like an individual survey
    */
    static function isIndividualLike($type)
    {
        return $type === SurveyTypes::Individual || $type === SurveyTypes::Progress;
    }

    /**
    * Indicates if the given type works like a group survey
    */
    static function isGroupLike($type)
    {
        return $type === SurveyTypes::Group || $type === SurveyTypes::LTT;
    }

    /**
    * Indicates if the given survey is the new progress
    */
    static function isNewProgress($survey)
    {
        return $survey->type == SurveyTypes::Progress && $survey->createUserReports;
    }

    /**
    * Creates an integer representation of the given types
    */
    static function createInt($types)
    {
        $value = 0;

        foreach ($types as $type) {
            $value |= SurveyTypes::bitPattern($type);
        }

        return $value;
    }

    /**
    * Returns the types from the given int value
    */
    static function getTypes($value)
    {
        $types = [];

        foreach (SurveyTypes::all() as $type) {
            if (($value & SurveyTypes::bitPattern($type)) != 0) {
                array_push($types, $type);
            }
        }

        return $types;
    }

    /**
    * Returns the names of the given types
    */
    static function names($types)
    {
        return array_map(function($type) {
            return SurveyTypes::name($type);
        }, $types);
    }

    /**
    * Return all survey types
    */
    static function all()
    {
        return [SurveyTypes::Individual, SurveyTypes::Group, SurveyTypes::Progress, SurveyTypes::Normal, SurveyTypes::LTT];
    }

    /**
    * Indicates if the given type is valid
    */
    static function isValidType($id)
    {
        foreach (SurveyTypes::all() as $value) {
            if ($value === $id) {
                return true;
            }
        }

        return false;
    }
	
	/**
	 * 
	 */
	public static function strToInt($type)
	{
        switch ($type) {
            case 'individual':
                return SurveyTypes::Individual;
            case 'group':
                return SurveyTypes::Group;
            case 'progress':
                return SurveyTypes::Progress;
            case 'normal':
                return SurveyTypes::Normal;
            case 'ltt':
                return SurveyTypes::LTT;
            default:
                return -1;
        }
	}
	
	/**
	 * Converts the provided string type into a type code.
	 *
	 * @param string $type
	 * @return integer
	 */
	public static function stringToCode($type)
	{
        switch ($type) {
            case 'individual':
                return self::Individual;
            case 'group':
                return self::Group;
            case 'progress':
                return self::Progress;
            case 'normal':
                return self::Normal;
            case 'ltt':
                return self::LTT;
            default:
                throw new InvalidArgumentException("Unknown survey type '$type'.");
        }
	}
}
