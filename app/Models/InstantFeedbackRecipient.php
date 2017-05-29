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
        $ifRecipient = self::where([
            'instantFeedbackId' => $instantFeedback->id,
            'recipientId'       => $recipient->id
        ])->first();

        if (!$ifRecipient) {
            $key = str_random(32);
            $ifRecipient = new self;
            $ifRecipient->instantFeedbackId = $instantFeedback->id;
            $ifRecipient->recipientId = $recipient->id;
            $ifRecipient->key = $key;
            $ifRecipient->save();
        }

        return $ifRecipient;
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
