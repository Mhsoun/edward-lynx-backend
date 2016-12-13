<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a role name
*/
class RoleName extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'role_names';

	//Don't generate the automatic
	public $timestamps = false;

	/**
	* The fillable fields
	*/
	protected $fillable = ['name', 'lang'];

	/**
	* Returns the role
	*/
	public function role()
	{
		return $this->belongsTo('\App\Models\Role', 'roleId');
	}
}
