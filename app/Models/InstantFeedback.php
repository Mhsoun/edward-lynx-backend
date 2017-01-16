<?php

namespace App\Models;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

class InstantFeedback extends Model
{
    
    protected $fillable = ['user_id', 'lang', 'closed', 'anonymous'];
    
    protected $visible = ['id', 'lang', 'closed', 'anonymous'];
    
    /**
     * Scopes instant feedbacks to the ones owned by the current user.
     *
     * @param   Illuminate\Database\Query\Builder   $query
     * @return  Illuminate\Database\Query\Builder
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
     * @param   Illuminate\Database\Query\Builder $query
     * @return  Illuminate\Database\Query\Builder
     */
    public function scopeAnswerable(Builder $query)
    {
        
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
     * Overrides our JSON representation and adds a createdAt field
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        $data['createdAt'] = $this->created_at->toIso8601String();
        $data['questions'] = $this->questions;
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
