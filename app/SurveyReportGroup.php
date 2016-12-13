<?php namespace App;

use Lang;
use App\AnswerType;
use App\SurveyTypes;

/**
* Contains functions for group survey reports
*/
abstract class SurveyReportGroup
{
    /**
    * Returns the blind spots for group surveys
    */
    public static function getBlindSpots($questionsByRole)
    {
        //Split into evaluating role and other roles
        $splitRoles = \App\ArrayHelpers::split($questionsByRole, function ($role) {
            return $role->toEvaluate;
        });

        $toEvaluate = $splitRoles[0][0];
        $comparePoint = 70;
        $blindSpots = [];

        foreach ($splitRoles[1] as $role) {
            $overestimatedQuestions = [];
            $underestimatedQuestions = [];

            foreach ($role->questions as $otherQuestion) {
                $selfQuestion = \App\SurveyReportHelpers::findQuestionById(
                    $toEvaluate->questions,
                    $otherQuestion->id);

                if ($selfQuestion != null) {
                    $selfAverage = round($selfQuestion->average * 100);
                    $othersAverage = round($otherQuestion->average * 100);
                    $gap = abs($selfAverage - $othersAverage);

                    if ($gap >= 20) {
                        if ($othersAverage < $comparePoint
                            && $selfAverage > $comparePoint
                            && $selfAverage > $othersAverage) {
                            array_push($overestimatedQuestions, (object)[
                                'id' => $selfQuestion->id,
                                'title' => $selfQuestion->title,
                                'category' => $selfQuestion->category,
                                'answerType' => $selfQuestion->answerType,
                                'self' => $selfAverage,
                                'others' => $othersAverage,
                                'gap' => $gap
                            ]);
                        } else if ($othersAverage > $comparePoint
                                   && $selfAverage < $comparePoint
                                   && $selfAverage < $othersAverage) {
                            array_push($underestimatedQuestions, (object)[
                                'id' => $selfQuestion->id,
                                'title' => $selfQuestion->title,
                                'category' => $selfQuestion->category,
                                'answerType' => $selfQuestion->answerType,
                                'self' => $selfAverage,
                                'others' => $othersAverage,
                                'gap' => $gap
                            ]);
                        }
                    }
                }
            }

            //Sort the by gap, descending.
            $descendingGap = function($first, $second) {
                return $second->gap - $first->gap;
            };

            usort($overestimatedQuestions, $descendingGap);
            usort($underestimatedQuestions, $descendingGap);

            array_push($blindSpots, (object)[
                'id' => $role->id,
                'name' => $role->name,
                'overestimated' => array_slice($overestimatedQuestions, 0, 5),
                'underestimated' => array_slice($underestimatedQuestions, 0, 5),
            ]);
        }

        return $blindSpots;
    }

    /**
    * Creates data for the given survey
    */
    public static function create($survey)
    {
        $questionAndComments = SurveyReportHelpers::getQuestionAnswers($survey, $survey->recipients);
        return SurveyReportHelpers::getData($survey, $questionAndComments);
    }
}
