<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a top/worst answer for an category in a survey
*/
class SurveyTopWorstCategory extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'survey_topworst_categories';

    protected $fillable = [];
    public $timestamps = false;
}
