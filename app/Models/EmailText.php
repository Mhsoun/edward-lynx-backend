<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents an email text
*/
class EmailText extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'email_texts';

	protected $fillable = [];

	//Don't generate the automatic timestamp columns
	public $timestamps = false;
}
