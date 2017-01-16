<?php

namespace App\Models;

use App\Http\HalResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InstantFeedback extends Model
{
    
    protected $fillable = ['user_id', 'lang', 'closed', 'anonymous'];
    
    protected $visible = ['id', 'lang', 'closed', 'anonymous'];
    
    /**
     * Scopes instant feedbacks to the ones owned by the current user.
     *
     * @param   Illuminate\Database\Eloquent\Builder   $query
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeMine(Builder $query)
    {
        $user_id = request()->user()->id;
        return $query->where('user_id', $user_id);
    }
    
    /**
     * Scopes instant feedbacks to the ones that should be answered
     * by the current user.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $query
     * @param   App\Models\User                         $user
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeAnswerableBy(Builder $query, User $user)
    {
        $user_id = $user->id;
        return $query->select('instant_feedbacks.*')
                     ->join('instant_feedback_recipients', 'instant_feedback_recipients.instant_feedback_id', '=', 'instant_feedbacks.id')
                     ->where('instant_feedback_recipients.user_id', $user_id);
    }
    
    /**
     * Returns this instant feedback's questions.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function questions()
    {
        return $this->belongsToMany('App\Models\Question', 'instant_feedback_questions');
    }
    
    /**
     * Returns the recipients of this instant feedback.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function recipients()
    {
        return $this->belongsToMany('App\Models\User', 'instant_feedback_recipients', 'instant_feedback_id', 'user_id');
    }
    
    /**
     * Returns the answer key of the provided user for this instant feedback.
     *
     * @param   App\Models\User $user
     * @return  string|null
     */
    public function answerKeyOf(User $user)
    {
        $recipient = InstantFeedbackRecipient::where([
            'instant_feedback_id'   => $this->id,
            'user_id'               => $user->id
        ])->first();
        if ($recipient) {
            return $recipient->key;
        } else {
            return null;
        }
    }
    
    /**
     * Overrides our JSON representation and adds a createdAt field
     *
     * @param   integer $options
     * @return  array
     */
    public function jsonSerialize($options = 0)
    {
        $data = parent::jsonSerialize();
        $data['createdAt'] = $this->created_at->toIso8601String();
        
        if ($options == HalResponse::SERIALIZE_FULL) {
            $data['questions'] = $this->questions;
        }
        
        return $data;
    }
    
    /**
     * Adds additional links to our JSON-HAL representation.
     *
     * @return array
     */
    public function jsonHalLinks()
    {
        return [];
    }
}
