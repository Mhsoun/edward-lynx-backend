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
	
	/**
	 * Creates a new EmailText instance.
	 *
	 * @param App\Models\User	$owner
	 * @param string			$subject
	 * @param string			$text
	 * @param string			$lang
	 * @return App\Models\EmailText
	 */
	public static function make(User $owner, $subject, $text, $lang)
	{
        $emailText = new self;
        $emailText->lang = $lang;
        $emailText->subject = $subject;
        $emailText->text = $text;
        $emailText->ownerId = $owner->id;
        $emailText->save();
        return $emailText;
	}
}
