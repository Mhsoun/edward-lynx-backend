<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstantFeedback extends Model
{
    
    protected $fillable = ['user_id', 'lang', 'closed', 'anonymous'];
    
    protected $visible = ['id', 'lang', 'closed', 'anonymous'];
    
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
