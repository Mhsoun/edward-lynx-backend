<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a member in a group
*/
class GroupMember extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'group_members';

	public $timestamps = false;

	/**
	* Returns the group
	* Remarks: As the 'group' name is already taken, I use groupObj as the name at the moment.
	* @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	*/
	public function groupObj()
    {
    	return $this->belongsTo('\App\Models\Group', 'groupId');
	}

	/**
	* Returns the recipient object for the member.
	* @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	*/
	public function recipient()
    {
    	return $this->belongsTo('\App\Models\Recipient', 'memberId');
	}
}
