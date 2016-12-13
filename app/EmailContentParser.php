<?php namespace App;
use Lang;

/**
* Parses content for emails
*/
class EmailContentParser
{
    /**
    * Replaces the content
    */
    private static function replace($content, $key, $replacement)
    {
        $content = str_replace('%' . $key, $replacement, $content);
        $content = str_replace('#' . $key, $replacement, $content);
        return $content;
    }

    /**
    * Parses the given content using the given data
    */
    public static function parse($content, $data, $replaceOnly = false, $stripTags = false)
    {
        $link = $data['surveyLink'];

        $content = EmailContentParser::replace($content, 'survey_name', $data['surveyName']);
        $content = EmailContentParser::replace($content, 'link', '<a href="' . $link . '">' . $link . '</a>');
        $content = EmailContentParser::replace($content, 'end_date', $data['surveyEndDate']);
        $content = EmailContentParser::replace($content, 'company_name', $data['companyName']);
        $content = EmailContentParser::replace($content, 'date', \Carbon\Carbon::now()->format('Y-m-d'));
        $content = EmailContentParser::replace($content, 'year', \Carbon\Carbon::now()->format('Y'));

        if (array_key_exists('toEvaluateName', $data)) {
            $content = EmailContentParser::replace($content, 'to_evaluate_name', $data['toEvaluateName']);
        }

        if (array_key_exists('toEvaluateGroupName', $data)) {
            $content = EmailContentParser::replace($content, 'to_evaluate_group_name', $data['toEvaluateGroupName']);
        }

        if (array_key_exists('toEvaluateRoleName', $data)) {
            $content = EmailContentParser::replace($content, 'to_evaluate_role_name', $data['toEvaluateRoleName']);
        }

        if (array_key_exists('mainTitle', $data)) {
            $content = EmailContentParser::replace($content, 'main_title', $data['mainTitle']);
        }

        $content = EmailContentParser::replace($content, 'recipient_name', $data['recipientName']);

        if (!$replaceOnly) {
            $content = str_replace("&#10;", "\n", $content);
            $content = str_replace("&#013;", "\n", $content);

            $content = str_replace("\r\n", "<br>", $content);
            $content = str_replace("\n", "<br>", $content);
        }

        if ($stripTags) {
            $content = strip_tags($content);
        }

        return $content;
    }

    /**
    * Returns a textarea version of the given content
    */
    public static function textarea($content)
    {
        $content = str_replace('\r\n', '&#10;', $content);
        $content = str_replace('\n', '&#10;', $content);
        return $content;
    }

    /**
    * Creates parser data for the given survey and recipient
    */
    public static function createParserData($survey, $surveyRecipient)
    {
        $data = [
            'surveyName' => $survey->name,
            'recipientName' => $surveyRecipient->recipient->name,
            'surveyLink' => '',
            'surveyEndDate' => $survey->endDate->format('Y-m-d H:i'),
            'companyName' => $survey->owner->name
        ];

        if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
            $data['toEvaluateName'] = $survey->candidates()
                ->where('recipientId', '=', $surveyRecipient->invitedById)
                ->first()
                ->recipient->name;
        } else if (\App\SurveyTypes::isGroupLike($survey->type)) {
            $data['toEvaluateGroupName'] = $survey->targetGroup->name;
            $data['toEvaluateRoleName'] = $survey->toEvaluateRole()->name;
        }

        return $data;
    }

    //Creates the parser data for reports
    public static function createReportParserData($survey, $toEvaluate, $includeInGroupReport = null)
    {
        $data = [
            'surveyName' => $survey->name,
            'recipientName' => Lang::get('report.recipientName'),
            'surveyLink' => '',
            'surveyEndDate' => $survey->endDate->format('Y-m-d H:i'),
            'companyName' => $survey->owner->name
        ];

        if (\App\SurveyTypes::isIndividualLike($survey->type)) {
            $candidateName = "";

            if ($toEvaluate != null) {
                $candidateName = $toEvaluate->recipient->name;
            } else {
                $candidateName = implode(", ", $survey->candidates->filter(function ($candidate) use (&$includeInGroupReport) {
                    if ($includeInGroupReport != null) {
                        foreach ($includeInGroupReport as $candidateId) {
                            if ($candidateId === $candidate->recipientId) {
                                return true;
                            }
                        }

                        return false;
                    } else {
                        return true;
                    }
                })->map(function($candidate) {
                    return $candidate->recipient->name;
                })->toArray());
            }

            $data['toEvaluateName'] = $candidateName;
        } else if (\App\SurveyTypes::isGroupLike($survey->type)) {
            $data['toEvaluateGroupName'] = $survey->targetGroup->name;
            $data['toEvaluateRoleName'] = $survey->toEvaluateRole()->name;
        }

        return $data;
    }
}
