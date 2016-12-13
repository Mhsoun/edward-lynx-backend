<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a name for value for an extra question
*/
class ExtraQuestionValueName extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'extra_question_value_names';

    protected $fillable = [];
    public $timestamps = false;
}
