<?php

namespace App\Models;

use Carbon\Carbon;
use App\Contracts\Routable;
use App\Contracts\JsonHalLinking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InstantFeedback extends Model implements Routable, JsonHalLinking
{
    
    protected $fillable = ['user_id', 'lang', 'closed', 'anonymous'];
    
    protected $visible = ['id', 'lang', 'closed', 'anonymous'];
    
    /**
     * Returns the URL to this instant feedback.
     *
     * @param   string  $prefix
     * @return  array
     */
    public function url()
    {
        return route('api1-instant-feedback', $this);
    }
    
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
                     ->where([
                         'instant_feedback_recipients.user_id'  => $user_id,
                         'instant_feedback_recipients.answered' => 0
                     ]);
    }
    
    /**
     * Returns the owner of this instant feedback.
     *
     * @return  App\Models\User
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
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
        return $this->belongsToMany('App\Models\User', 'instant_feedback_recipients', 'instant_feedback_id', 'user_id')
                    ->withPivot('key', 'answered', 'answered_at');
    }
    
    /**
     * Returns the answers to this instant feedback.
     *
     * @return  Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->hasMany('App\Models\InstantFeedbackAnswer');
    }
    
    /**
     * Returns the users this instant feedback has been shared to.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function shares()
    {
        return $this->belongsToMany('App\Models\User', 'instant_feedback_shares', 'instant_feedback_id', 'user_id');
    }
    
    /**
     * Returns statistics and frequencies of this instant feedback's answers.
     *
     * @return  array
     */
    public function calculateAnswers()
    {
        $question = $this->questions[0];
        $answers = $this->answers;
        return InstantFeedbackAnswer::calculate($question, $answers);
    }
    
    /**
     * Returns TRUE if this instant feedback is shared to the provided user.
     *
     * @param   App\Models\User $user
     * @return  boolean
     */
    public function isSharedTo(User $user)
    {
        return InstantFeedbackShare::isShared($this, $user);
    }
    
    /**
     * Returns the answer key of the provided user. Returns NULL
     * if the user hasn't been invited or the invite has been
     * answered already.
     *
     * @param   App\Models\User $user
     * @return  string|null
     */
    public function answerKeyOf(User $user)
    {
        $invite = $this->recipients()
                       ->where('user_id', $user->id)
                       ->first();
        if ($invite && !$invite->pivot->answered) {
            return $invite->pivot->key;
        } else {
            return null;
        }
    }
    
    /**
     * Makes the instant feedback as closed for answers.
     *
     * @return  this
     */
    public function close()
    {
        $this->closed = true;
        $this->closed_at = Carbon::now();
        return $this;
    }
    
    /**
     * Makes the instant feedback as open for answers.
     *
     * @return  this
     */
    public function open()
    {
        $this->closed = false;
        $this->closed_at = null;
        return $this;
    }
    
    /**
     * Overrides our JSON representation and adds a createdAt field
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        $data['createdAt'] = $this->created_at->toIso8601String();
        
        $data['questions'] = $this->questions;
        $data['shares'] = $this->shares->map(function($user) {
            return $user->id;
        });
        
        return $data;
    }
    
    /**
     * Adds additional links to our JSON-HAL representation.
     *
     * @return array
     */
    public function jsonHalLinks()
    {
        return [
            'answers'   => ['href' => route('api1-instant-feedback-answers', $this)]
        ];
    }
}
