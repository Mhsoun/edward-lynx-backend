<?php namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
* Represents a link for creating a report for a user
*/
class SurveyUserReport extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'survey_user_reports';

    protected $primaryKey = 'link';
    public $incrementing = false;

    protected $fillable = [];

    public $timestamps = false;

    /**
    * Returns the survey that the recipient belongs to
    */
    public function survey()
    {
        return $this->belongsTo('\App\Models\Survey', 'surveyId');
    }

    /**
    * Returns the recipient object
    */
    public function recipient()
    {
        return $this->belongsTo('\App\Models\Recipient', 'recipientId');
    }

    /**
     * Generates a filename for this user report.
     * 
     * @return  string
     */
    public function filename()
    {
        $date = Carbon::now(Survey::TIMEZONE)->format('Y-m-d H_m_s');
        $fileName = $this->survey->name . ' ' . $date . '.pdf';

        return $fileName;
    }
}
