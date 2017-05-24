<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InstantFeedbackRecipient extends Model
{
    
    /**
     * Disable timestamps for this model.
     *
     * @var boolean
     */
    public $timestamps = false;
    
    protected $fillable = [];
    
    /**
     * Attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['answeredAt'];
    
    /**
     * Creates a new recipient record for a recipient.
     *
     * @param   App\Models\InstantFeedback              $instantFeedback
     * @param   App\Models\Recipient                    $recipient
     * @return  App\Models\InstantFeedbackRecipient
     */
    public static function make(InstantFeedback $instantFeedback, $recipient)
    {
        $recipient = self::where([
            'instantFeedbackId' => $instantFeedback->id,
            'recipientId'       => $recipient->id
        ])->first();

        if (!$recipient) {
            $key = str_random(32);
            $recipient = new self;
            $recipient->instantFeedbackId = $instantFeedback->id;
            $recipient->recipientId = $recipient->id;
            $recipient->key = $key;
            $recipient->user_type = $type;
            $recipient->save();
        }

        return $recipient;
    }
    
    /**
     * Returns the recipient record.
     *
     * @return  App\Models\Recipient
     */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class, 'recipientId');
    }
    
    /**
     * Returns the instant feedback.
     *
     * @param   App\Models\InstantFeedback
     */
    public function instantFeedback()
    {
        return $this->belongsTo(InstantFeedback::class, 'instantFeedbackId');
    }
    
    /**
     * Marks this recipient as answered.
     * NOTE: This doesn't save this model!
     *
     * @return  void
     */
    public function markAnswered()
    {
        $this->answered = 1;
        $this->answeredAt = Carbon::now();
    }
    
}
