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
    
    protected $fillable = ['userId', 'lang', 'closed'];
    
    protected $visible = ['id', 'lang', 'closed'];
    
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
        $recipients = Recipient::where('mail', $user->email)->get();
        $ids = $recipients->map(function($recipient) {
            return $recipient->id;
        });

        return $query->select('instant_feedbacks.*')
                     ->join('instant_feedback_recipients', 'instant_feedback_recipients.instantFeedbackId', '=', 'instant_feedbacks.id')
                     ->where('instant_feedback_recipients.answered', false)
                     ->whereIn('instant_feedback_recipients.recipientId', $ids);
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
     * @return  Exception
     */
    public function users()
    {
        throw new \Exception('InstantFeedback::users is not supported now.');
    }

    /**
     * Returns "guest" recipients of this instant feedback.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function recipients()
    {
        return $this->belongsToMany(Recipient::class, 'instant_feedback_recipients', 'instantFeedbackId', 'recipientId')
                    ->withPivot('key', 'answered', 'answeredAt');
    }

    /**
     * Returns all registered and guest receivers of this instant feedback.
     * 
     * @return  Illuminate\Support\Collection
     */
    public function receivers()
    {
        throw new \Exception('Use InstantFeedback::recipients instead.');
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
     * @param   App\Models\Recipient    $recipient
     * @return  string|null
     */
    public function answerKeyOf(Recipient $recipient)
    {
        $ifRecipient = $this->recipients()
                            ->where('recipientId', $recipient->id)
                            ->first();
        if ($ifRecipient && !$ifRecipient->pivot->answered) {
            return $ifRecipient->pivot->key;
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
            'invited'   => $this->recipients()->count(),
            'answered'  => $this->recipients()->where('answered', true)->count()
        ];

        // Users
        $author = $this->user;
        $data['author'] = [
            'id'    => $author->id,
            'name'  => $author->name
        ];
        
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
