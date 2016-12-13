<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a group
*/
class Group extends Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'groups';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name'];

	public $timestamps = false;

	/**
	* Returns the fullname including the name of the parent
	*/
	public function fullName()
	{
		$parentGroup = $this->parentGroup;
		if ($parentGroup != null) {
			return $parentGroup->name . " > " . $this->name;
		} else {
			return $this->name;
		}
	}

	/**
	* Returns the owner of the group
	*/
	public function ownerObj()
    {
    	return $this->belongsTo('\App\Models\User', 'ownerId');
	}

	/**
	* Returns the members in the group
	*/
	public function members()
	{
		return $this->hasMany('\App\Models\GroupMember', 'groupId');
	}

	/**
	* Returns the subgroups for the group
	*/
	public function subgroups()
	{
		return $this->hasMany('\App\Models\Group', 'parentGroupId');
	}

	/**
	* Returns the parent group if subgroup
	*/
	public function parentGroup()
	{
		return $this->belongsTo('\App\Models\Group', 'parentGroupId');
	}
}
