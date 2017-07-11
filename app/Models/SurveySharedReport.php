<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SurveySharedReport extends Model
{

    public $timestamps = false;

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
                array_set($recipientsToReports, "{$surveyRecipient->recipient->id}.{$this->survey->id}.{$report->id}", $report);
            }
        }

        $json = [];
        foreach ($recipientsToReports as $id => $surveys) {
            $recipient = Recipient::find($id);
            $recipientJson = [
                'id'        => $recipient->id,
                'name'      => $recipient->name,
                'surveys'   => [],
            ];

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

                $recipientJson['surveys'][] = $surveyJson;
            }

            $json[] = $recipientJson;
        }

        return $json;
    }

}
