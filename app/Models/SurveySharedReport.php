<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SurveySharedReport extends Model
{

    public $timestamps = false;

    /**
     * Processes a collection of shared reports and returns
     * the JSON representation of it.
     * 
     * @param   Illuminate\Support\Collection   $sharedReports
     * @return  array
     */
    public static function json($sharedReports)
    {
        $specificCandidates = [];
        $recipientsToSurveys = [];
        foreach ($sharedReports as $sharedReport) {

            // If a survey has been shared to a specific recipient
            if (!is_null($sharedReport->recipientId)) {
                if (isset($recipientsToSurveys[$sharedReport->recipientId])) {
                    $recipientsToSurveys[$sharedReport->recipientId][] = $sharedReport->surveyId;
                } else {
                    $recipientsToSurveys[$sharedReport->recipientId] = [$sharedReport->surveyId];
                }

                if (!in_array($sharedReport->recipientId, $specificCandidates)) {
                    $specificCandidates[] = $sharedReport->recipientId;
                }
            // Otherwise, collect all of the survey's recipients.
            } else {
                foreach ($sharedReport->survey->recipients as $recipient) {
                    if (isset($recipientsToSurveys[$recipient->recipientId])) {
                        $recipientsToSurveys[$recipient->recipientId][] = $sharedReport->surveyId;
                    } else {
                        $recipientsToSurveys[$recipient->recipientId] = [$sharedReport->surveyId];
                    }
                }
            }

        }

        // Start building the response array.
        $recipients = [];
        foreach ($recipientsToSurveys as $recipientId => $surveys) {
            $recipient = Recipient::find($recipientId);
            $recipientJson = [
                'id'        => $recipient->id,
                'name'      => $recipient->name,
                'surveys'   => [],
            ];

            foreach ($surveys as $surveyId) {
                $survey = Survey::find($surveyId);
                $surveyJson = [
                    'id'        => $survey->id,
                    'name'      => $survey->name,
                    'type'      => $survey->type,
                    'reports'   => [],
                ];

                // If this is a specific candidate, fetch the user report for
                // him/her instead of listing all the reports under that survey.
                if (
                    in_array($recipient->id, $specificCandidates) &&
                    $candidate = SurveyCandidate::where([
                        'surveyId'      => $survey->id,
                        'recipientId'   => $recipient->id
                    ])->first()
                   ) {
                    $report = $candidate->userReport();
                    if ($report) {
                        $surveyJson['reports'][] = [
                            'id'    => $report->surveyId . '-' . $report->recipientId,
                            'type'  => 'user-report',
                            'name'  => $report->filename(),
                            'link'  => action('ReportController@showUserReport', [
                                'link'          => $report->link,
                                'autogenerate'  => 1
                            ]),
                        ];
                    }
                } else {
                    foreach ($survey->reports as $report) {
                        $surveyJson['reports'][] = [
                            'id'    => $report->id,
                            'type'  => 'survey-report',
                            'name'  => basename($report->fileName, '.pdf'),
                            'link'  => secure_url('/reports/' . rawurlencode($report->fileName))
                        ];
                    }
                }

                $recipientJson['surveys'][] = $surveyJson;
            }

            $recipients[] = $recipientJson;
        }

        return $recipients;
    }

    /**
     * Returns the survey this shared report is under.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function survey()
    {
        return $this->hasOne(Survey::class, 'id', 'surveyId');
    }

    /**
     * Returns the recipient/candidate record.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function recipient()
    {
        return $this->hasOne(Recipient::class, 'recipientId');
    }

    /**
     * Returns the user this candidate's record has been shared to.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userId');
    }

}
