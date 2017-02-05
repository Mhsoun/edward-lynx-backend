<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    
    /**
     * All question categories endpoint.
     *
     * @param   Illuminate\Http\Request $request
     * @return  App\Http\JsonHalResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $categories = QuestionCategory::where([
                'ownerId'   => $user->id,
                'isSurvey'  => true
            ])
            ->orderBy('title', 'asc')
            ->get();
        
        return response()->jsonHal($categories);
    }
    
    /**
     * Returns question category details.
     *
     * @param   App\Models\QuestionCategory
     * @return  App\Http\JsonHalResponse
     */
    public function show(QuestionCategory $category)
    {
        return response()->jsonHal($category);
    }

}
