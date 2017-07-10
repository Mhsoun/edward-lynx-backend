<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SurveyCandidateSharedReport extends Model
{

    public $timestamps = false;

    /**
     * Returns the survey this shared report is under.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function survey()
    {
        return $this->hasOne(Survey::class, 'surveyId');
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
        return $this->hasOne(User::class, 'userId');
    }

}
