<?php namespace App;

use Lang;
use App\AnswerType;
use App\SurveyTypes;

/**
* Contains functions for progress survey reports
*/
abstract class SurveyReportProgress
{
    /**
    * Creates data for the givven survey
    */
    public static function create($survey, $toEvaluate)
    {
        $data = SurveyReport360::create($survey, $toEvaluate);

        //Get the question orders
        $questionOrders = [];
        foreach ($data->questions as $question) {
            $questionOrders[$question->id] = $survey->questions()
                ->where('questionId', '=', $question->id)
                ->first()
                ->order;
        }

        //Split questions into roles
        $selfRoleId = \App\SurveyReportHelpers::getSelfRoleId($survey, $toEvaluate);
        $othersRoleName = Lang::get('roles.others');
        $othersRoleId = -1;

        $selfRole = null;
        $otherRoles = [];

        foreach ($data->questionsByRole as $role) {
            if ($selfRoleId == $role->id) {
                $selfRole = clone $role;
            } else {
                array_push($otherRoles, $role);
            }
        }

        $selfAndOtherRoles = [
            \App\SurveyReportHelpers::mergeQuestionRoles($otherRoles, -1, $othersRoleName),
            $selfRole
        ];

        foreach ($selfAndOtherRoles as $role) {
            \App\SurveyReportHelpers::sortQuestionsByOrder($role->questions, $questionOrders);
            $role->questions = \App\SurveyReportHelpers::calculateQuestionsAverage($role->questions);
        }

        $data->selfAndOtherRoles = $selfAndOtherRoles;
        $data->questionOrders = $questionOrders;
        return $data;
    }
}
