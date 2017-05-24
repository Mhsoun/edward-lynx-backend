<?php

namespace App\Models;

use Carbon\Carbon;
use App\Contracts\Routable;
use App\Contracts\JsonHalLinking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InstantFeedback extends Model implements Routable, JsonHalLinking
{
    
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $fillable = ['userId', 'lang', 'closed', 'anonymous'];
    
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
        $id = request()->user()->id;
        return $query->where('userId', $id);
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
        $id = $user->id;
        return $query->select('instant_feedbacks.*')
                     ->join('instant_feedback_recipients', 'instant_feedback_recipients.instantFeedbackId', '=', 'instant_feedbacks.id')
                     ->where([
                         'instant_feedback_recipients.userId'  => $id,
                         'instant_feedback_recipients.answered' => 0,
                         'instant_feedback_recipients.user_type' => 'users'
                     ]);
    }
    
    /**
     * Returns the owner of this instant feedback.
     *
     * @return  App\Models\User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * Returns registered recipient users of this instant feedback.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users()
    {
        return $this->morphedByMany(
            User::class,
            'user',
            'instant_feedback_recipients',
            'instantFeedbackId',
            'userId'
        )->withPivot('key', 'answered', 'answeredAt');
    }

    /**
     * Returns "guest" recipients of this instant feedback.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function recipients()
    {
        return $this->morphedByMany(
            Recipient::class,
            'user',
            'instant_feedback_recipients',
            'instantFeedbackId',
            'userId'
        )->withPivot('key', 'answered', 'answeredAt');
    }

    /**
     * Returns all registered and guest receivers of this instant feedback.
     * 
     * @return  Illuminate\Support\Collection
     */
    public function receivers()
    {
        $users = $this->users->toArray();
        $recipients = $this->recipients->toArray();
        $receivers = collect(array_merge($users, $recipients));
        return $receivers;
    }
    
    /**
     * Returns this instant feedback's questions.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'instant_feedback_questions', 'instantFeedbackId', 'questionId');
    }
    
    /**
     * Returns the answers to this instant feedback.
     *
     * @return  Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->hasMany(InstantFeedbackAnswer::class, 'instantFeedbackId');
    }
    
    /**
     * Returns the users this instant feedback has been shared to.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function shares()
    {
        return $this->belongsToMany(User::class, 'instant_feedback_shares', 'instantFeedbackId', 'userId');
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
        $invite = $this->users()
                       ->where('userId', $user->id)
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
        $this->closedAt = Carbon::now();
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
        $this->closedAt = null;
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
        $data['createdAt'] = $this->createdAt->toIso8601String();
        
        $data['questions'] = $this->questions;
        $data['shares'] = $this->shares->map(function($user) {
            return $user->id;
        });

        $data['stats'] = [
            'invited'   => $this->receivers()->count(),
            'answered'  => $this->recipients()->where('answered', true)->count() + $this->users()->where('answered', true)->count()
        ];

        // Users
        $author = $this->user;
        $data['author'] = [
            'id'    => $author->id,
            'name'  => $author->name
        ];

        // Build recipients array
        $data['recipients'] = [];
        foreach ($this->recipients as $recipient) {
            $data['recipients'][] = [
                'id'    => $recipient->id,
                'name'  => $recipient->name,
                'email' => $recipient->email,
                'isUser'=> false
            ];
        }
        foreach ($this->users as $recipient) {
            $data['recipients'][] = [
                'id'    => $recipient->id,
                'name'  => $recipient->name,
                'email' => $recipient->email,
                'isUser'=> true
            ];
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
        return [
            'answers'   => ['href' => route('api1-instant-feedback-answers', $this)]
        ];
    }
}
