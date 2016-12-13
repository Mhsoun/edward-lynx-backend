<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a tag for a question
*/
class QuestionTag extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'question_tags';

    protected $fillable = [];
    public $timestamps = false;
}
