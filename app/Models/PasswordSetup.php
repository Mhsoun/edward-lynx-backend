<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a password setup model
*/
class PasswordSetup extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'password_setups';

	protected $fillable = [];

	protected $primaryKey = 'token';
    public $incrementing = false;

	//Don't generate the automatic timestamp columns
	public $timestamps = false;

	protected $dates = ['createdAt'];

	/**
	* Returns the user
	*/
	public function user()
	{
		return $this->belongsTo('\App\Models\User', 'userId');
	}
}
