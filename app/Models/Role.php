<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a role
*/
class Role extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'roles';

	//Don't generate the automatic
	public $timestamps = false;

	/**
	* Returns the name of the role determined by the current locale
	*/
	public function name($lang = null)
	{
		if ($lang == null) {
			$lang = app()->getLocale();
		}

		$name = $this->names()->where('lang', '=', $lang)->first();

		//If the lang was not found, use englisg.
		if ($name == null) {
			$name = $this->names()->where('lang', '=', 'en')->first();
		}

		return $name->name;
	}

	/**
	* Returns the owner of the role
	*/
	public function owner()
	{
		return $this->belongsTo('\App\Models\User', 'ownerId');
	}


	/**
	* The names for the role
	*/
	public function names()
	{
		return $this->hasMany('\App\Models\RoleName', 'roleId');
	}
}
