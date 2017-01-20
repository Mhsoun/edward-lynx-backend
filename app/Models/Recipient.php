<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a recipient
*/
class Recipient extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'recipients';

	protected $fillable = ['name', 'mail'];

	public $timestamps = false;

	/**
	* Finds the given recipient for the given owner
	*/
	public static function findForOwner($ownerId, $email)
	{
		return Recipient::whereRaw(
			'ownerId=? AND mail=?', [$ownerId, $email])
			->first();
	}

	/**
	* Creates the given recipient. If the recipient already exists, that one is returned.
	*/
	public static function make($ownerId, $name, $email, $position, $userId = null)
	{
        if ($userId) {
            $recipient = self::where([
                'ownerId'   => $ownerId,
                'user_id'   => $userId
            ])->first();
        } else {
    		$recipient = \App\Models\Recipient::
    			where('ownerId', '=', $ownerId)
    			->where('mail', '=', $email)
    			->first();
        }
            
        if ($recipient == null) {
            $recipient = new \App\Models\Recipient;
            $recipient->name = $name;
            $recipient->mail = $email;
            $recipient->ownerId = $ownerId;
            $recipient->position = $position;
            $recipient->user_id = $userId ? $userId : null;
            $recipient->save();
        }  else {
			//The position might have changed, so update it.
			$recipient->position = $position;
			$recipient->save();
		}

		return $recipient;
	}
}
