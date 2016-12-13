<?php namespace App;

use Lang;
use App\AnswerType;
use App\SurveyTypes;

/**
* Contains functions for 360 survey reports
*/
abstract class SurveyReport360
{
    /**
    * Returns the blind spots
    */
    public static function getBlindSpots($selfAndOthersQuestions)
    {
        $overestimatedQuestions = [];
        $underestimatedQuestions = [];
        $gapThreshold = 20;

        foreach ($selfAndOthersQuestions->self as $selfQuestion) {
            $otherQuestion = \App\SurveyReportHelpers::findQuestionById($selfAndOthersQuestions->others, $selfQuestion->id);
            if ($otherQuestion != null) {
                $selfAverage = round($selfQuestion->average * 100);
                $othersAverage = round($otherQuestion->average * 100);
                $gap = abs($selfAverage - $othersAverage);

                if ($selfAverage > $othersAverage && $gap >= $gapThreshold) {
                    array_push($overestimatedQuestions, (object)[
                        'id' => $selfQuestion->id,
                        'title' => $selfQuestion->title,
                        'category' => $selfQuestion->category,
                        'answerType' => $selfQuestion->answerType,
                        'self' => $selfAverage,
                        'others' => $othersAverage,
                        'gap' => $gap
                    ]);
                } else if ($othersAverage > $selfAverage && $gap >= $gapThreshold) {
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


        //Sort the by gap, descending.
        $descendingGap = function($first, $second) {
            return $second->gap - $first->gap;
        };

        usort($overestimatedQuestions, $descendingGap);
        usort($underestimatedQuestions, $descendingGap);

        return (object)[
            'overestimated' => array_slice($overestimatedQuestions, 0, 5),
            'underestimated' => array_slice($underestimatedQuestions, 0, 5)
        ];
    }

    /**
    * Merges the given roles
    */
    private static function mergeRoles($survey, $roleNames, $answeredRecipientIds)
    {
        $type = $survey->type;

        $managerId = \App\Roles::getRoleIdByName(Lang::get('roles.manager'), $type);
        $managerCount = 0;

        $rolesToMerge = [];
        foreach (\App\Roles::get360() as $role) {
            if ($role->id != $managerId) {
                array_push($rolesToMerge, $role->id);
            }
        }

        $mergeLimit = 3;
        $rolesToMergeCount = [];
        $totalCount = 0;
        $totalToMerge = 0;

        //Compute the count for the roles to merge
        foreach ($rolesToMerge as $roleId) {
            $count = 0;

            foreach ($answeredRecipientIds as $recipient) {
                if ($roleId == $recipient->roleId) {
                    $count++;
                }
            }

            $rolesToMergeCount[$roleId] = $count;
            $totalCount += $count;
        }

        if ($totalCount == 2) {
            foreach ($answeredRecipientIds as $recipient) {
                if ($recipient->roleId == $managerId) {
                    $managerCount++;
                }
            }
        }

        //Get the roles to actually merge
        $toMerge = [];
        foreach ($rolesToMergeCount as $roleId => $count) {
            if ($count < $mergeLimit && $count > 0) {
                array_push($toMerge, $roleId);
                $totalToMerge += $count;
            }
        }

        //If there just are one role to merge or total to merge less than limit,
        //add the one choose least number of recipients to also be merged
        if (count($toMerge) == 1 || ($totalToMerge > 0 && $totalToMerge < $mergeLimit)) {
            $lowestCount = PHP_INT_MAX;
            $lowestRoleId = 0;

            $validRole = function ($roleId) use (&$toMerge) {
                foreach ($toMerge as $role) {
                    if ($role == $roleId) {
                        return false;
                    }
                }

                return true;
            };

            foreach ($rolesToMergeCount as $roleId => $count) {
                if ($validRole($roleId)) {
                    if ($count < $lowestCount && $count > 0) {
                        $lowestCount = $count;
                        $lowestRoleId = $roleId;
                    }
                }
            }

            if ($lowestCount != PHP_INT_MAX) {
                array_push($toMerge, $lowestRoleId);
            }
        } else if ($totalToMerge > 0 && $totalToMerge < $mergeLimit) {

        }

        //Special case for manager
        if ($managerCount > 0 && $totalCount == 2) {
            array_push($toMerge, $managerId);
        }

        $mergedRoleIdsMapping = [];

        //Merge the roles
        if (count($toMerge) > 0) {
            $mergedRoleName = '';
            $mergedRoleId = -2;

            foreach ($toMerge as $roleId) {
                $mergedRoleIdsMapping[$roleId] = $mergedRoleId;
            }

            if (count($toMerge) >= 3) {
                $mergedRoleName = Lang::get('roles.others');
            } else {
                $mergedRoleName = implode(' & ', array_map(function ($roleId) use (&$roleNames) {
                    return $roleNames[$roleId];
                }, $toMerge));
            }

            $roleNames[$mergedRoleId] = $mergedRoleName;
        }

        //Update the recipients
        $answeredRecipientIdsMapping = [];
        foreach ($answeredRecipientIds as $recipient) {
            if (array_key_exists($recipient->roleId, $mergedRoleIdsMapping)) {
                $recipient->roleId = $mergedRoleIdsMapping[$recipient->roleId];
            }

            $answeredRecipientIdsMapping[$recipient->recipientId . ':' . $recipient->invitedById] = $recipient;
        }

        return (object)[
            'answeredRecipientIds' => $answeredRecipientIds,
            'roleNames' => $roleNames,
        ];
    }

    /**
    * Creates data for the givven survey
    */
    public static function create($survey, $toEvaluate, $includeInGroupReport = null)
    {
        $isValidFn = function($answer) use (&$toEvaluate, &$includeInGroupReport) {
            if ($toEvaluate != null) {
                return $answer->invitedById === $toEvaluate->recipientId;
            } else {
                if ($includeInGroupReport != null) {
                    foreach ($includeInGroupReport as $invitedById) {
                        if ($answer->invitedById === $invitedById) {
                            return true;
                        }
                    }

                    return false;
                } else {
                    return true;
                }
            }
        };

        $getAnswersFn = function ($survey, $questionId) use (&$toEvaluate) {
            if ($toEvaluate != null) {
                return $survey->answers()
                    ->where('questionId', '=', $questionId)
                    ->where('invitedById', '=', $toEvaluate->recipientId)
                    ->get();
            } else {
                return $survey->answers()
                    ->where('questionId', '=', $questionId)
                    ->get();
            }
        };

        $mergeRolesFn = function ($roleNames, $answeredRecipientIds) use (&$survey) {
            return SurveyReport360::mergeRoles($survey, $roleNames, $answeredRecipientIds);
        };

        $questionAndComments = SurveyReportHelpers::getQuestionAnswers(
            $survey,
            $survey->recipients,
            $isValidFn,
            $getAnswersFn,
            $mergeRolesFn);

        return SurveyReportHelpers::getData($survey, $questionAndComments, $toEvaluate);
    }
}
