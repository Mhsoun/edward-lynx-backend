<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
* Represents a recipient
*/
class Recipient extends Model
{
    use Notifiable;

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
	public static function make($ownerId, $name, $email, $position)
	{
		$recipient = \App\Models\Recipient::
			where('ownerId', '=', $ownerId)
			->where('mail', '=', $email)
			->first();
            
        if ($recipient == null) {
            $recipient = new \App\Models\Recipient;
            $recipient->name = $name;
            $recipient->mail = $email;
            $recipient->ownerId = $ownerId;
            $recipient->position = $position;
            $recipient->save();
        }  else {
			//The position might have changed, so update it.
			$recipient->position = $position;
			$recipient->save();
		}

		return $recipient;
	}

    /**
     * Allow `mail` to be accessed through `email`.
     * 
     * @return  string 
     */
    public function getEmailAttribute()
    {
        return $this->attributes['mail'];
    }
}
