<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyCandidateReport extends Model
{
    
    public $timestamps = false;

    protected $visible = [];

    /**
     * Returns the associated survey report file record.
     *
     * @return Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function surveyReportFile()
    {
        return $this->hasOne(SurveyReportFile::class, 'id', 'surveyReportFileId');
    }

}
