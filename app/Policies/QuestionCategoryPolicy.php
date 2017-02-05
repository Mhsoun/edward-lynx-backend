<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QuestionCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuestionCategoryPolicy
{
    use HandlesAuthorization;
    
    /**
     * Before hook. Superadmins can do everything.
     * 
     * @param   App\Models\User     $user
     * @return  boolean
     */
    public function before(User $user)
    {
        if ($user->isA('superadmin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the question category.
     *
     * @param   App\Models\User             $user
     * @param   App\Models\QuestionCategory $category
     * @return  boolean
     */
    public function view(User $user, QuestionCategory $category)
    {
        return $category->ownerId == $user->id;
    }
}
