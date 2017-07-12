<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SurveySharedReport extends Model
{

    public $timestamps = false;

    public static function json($sharedReports)
    {
        $recipientsToSurveys = [];
        foreach ($sharedReports as $sharedReport) {
            foreach ($sharedReport->survey->recipients as $recipient) {
                if (isset($recipientsToSurveys[$recipient->recipientId])) {
                    $recipientsToSurveys[$recipient->recipientId][] = $sharedReport->surveyId;
                } else {
                    $recipientsToSurveys[$recipient->recipientId] = [$sharedReport->surveyId];
                }
            }
        }

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

                foreach ($survey->reports as $report) {
                    $surveyJson['reports'][] = [
                        'id'    => $report->id,
                        'name'  => basename($report->fileName, '.pdf'),
                        // 'link'  => action('ReportController@viewReport', $report->id),
                        'link'  => secure_url('/reports/' . $report->fileName)
                    ];
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

    /**
     * Returns the JSON representation of this class.
     * 
     * @return  array
     */
    public function jsonSerialize()
    {
        $recipientsToReports = [];
        foreach ($this->survey->recipients as $surveyRecipient) {
            $recipient = $surveyRecipient->recipient;
            foreach ($this->survey->reports as $report) {
                array_set($recipientsToReports, "{$surveyRecipient->recipient->id}.{$this->survey->id}.{$report->id}", $report->toArray());
            }
        }

        dd($recipientsToReports);

        $recipients = [];

        $json = [];

        foreach ($recipientsToReports as $id => $surveys) {
            $recipient = Recipient::find($id);

            if (!isset($recipients[$recipient->id])) {
                $recipients[$recipient->id] = [
                    'id'        => $recipient->id,
                    'name'      => $recipient->name,
                    'surveys'   => [],
                ];
            }

            foreach ($surveys as $id => $reports) {
                $survey = Survey::find($id);
                $surveyJson = [
                    'id'        => $survey->id,
                    'name'      => $survey->name,
                    'type'      => $survey->type,
                    'reports'   => []
                ];

                foreach ($reports as $report) {
                    $surveyJson['reports'][] = [
                        'id'    => $report->id,
                        'name'  => basename($report->fileName, '.pdf'),
                        'link'  => action('ReportController@viewReport', $report->id)
                    ];
                }

                $recipientColl[$recipient->id]['surveys'][] = $surveyJson;
            }

            // $json[] = $recipientJson;
        }

        dd($recipientColl);

        return $json;
    }

}
