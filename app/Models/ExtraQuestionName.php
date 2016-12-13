<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents an name for an extra question
*/
class ExtraQuestionName extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'extra_question_names';

    protected $fillable = [];
    public $timestamps = false;
}
